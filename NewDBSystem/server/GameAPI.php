<?php
	session_start();

	$config = parse_ini_file("config/serverconfig.ini");
	
	$servername = $config['databaseservername'];
	$dbusername = $config['databaseusername'];
	$dbpassword = $config['databasepassword'];
	
	$action = $_POST["action"];
	switch ($action) {
		case "0":
			$playerinfo = GetPlayerInfo($servername, $dbusername, $dbpassword);
			GetGameInfo($playerinfo, $servername, $dbusername, $dbpassword);
			break;
		case "1":
			break;
	}

	function GetGameInfo($playerinfo, $servername, $dbusername, $dbpassword){
		$sessionid = $playerinfo[0];
		$ingame = $playerinfo[1];
		$lobbyid = $playerinfo[2];
		$isadmin = $playerinfo[3];
	
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "SELECT playerid FROM players WHERE id = $sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
		$PID = $result[0];
		
		if ($PID == null){
			echo 1;
			$m_conn->close();
			exit();
		}

		echo '{"PID":' . $PID . ', ';

		$sql = "SELECT isactive, playercount, ischargeturn, chargerid, trumpcard0, trumpcard1, deckleft FROM gamestates";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);

		if($result[0] == 0){
			$ingame = 0;
		}

		$playercount = $result[1];
		$ischargeturn = $result[2];
		$CID = $result[3];
		$nextCID = ($CID < $playercount) ? $CID +1 : 0;
		$deckleft = $result[6];
		
		echo '"ischargeturn":' .$ischargeturn . ', "chargerid":' . $result[3] . ', ';
		echo '"trumpcard":[' . $result[4] . ', ' . $result[5] . '], ';
		echo '"deckleft":' . $deckleft . ', ';

		$sql = "SELECT playerid, nickname FROM players WHERE playerid IS NOT NULL";
		$result = $m_conn->query($sql);

		echo '"players":[';
		for ($i = 0; $i < $playercount; $i++){
			$row = $result->fetch_array(MYSQLI_NUM);
			echo '[' . $row[0] . ', "' . $row[1] . '"]';
			if ($i < $playercount-1) {echo ', '; }
		}
		echo '], ';
		
		if($ischargeturn == 1){
			$sql = "SELECT COUNT(*) FROM hands WHERE playerid = $nextCID";
		} else {
			$sql = "SELECT COUNT(*) FROM hands WHERE playerid = $CID";
		}
		echo '"opponent":' . $m_conn->query($sql)->fetch_array(MYSQLI_NUM)[0] . ', ';

		$sql = "SELECT card0, card1 FROM hands WHERE playerid = $PID";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$result = $m_conn->query($sql);
		$handcount = $result->num_rows;

		if (($playercount <= 0 || $handcount <= 0) && $ingame == 1 && $deckleft <= 0){
			$ingame = 0;
			$winstatus = GameOver($playerinfo, $servername, $dbusername, $dbpassword);
			
			echo '"gameover":["ingame":"' . $ingame . ', winstatus":' . $winstatus . '], ';
		}
		echo '"handcount":' .$handcount . ', ';
		echo '"hand":[';
		for ($i = 0; $i < $handcount; $i++){
			$row = $result>fetch_array(MYSQLI_NUM);
			echo '[' . $row[0] . ', ' . $row[1] . ']';
			if ($i < $handcount-1) {echo ', '; }
		}
		echo '], ';
		
		$sql = "SELECT card0, card1, state, killsid FROM field";
		$result = $m_conn->query($sql);
		$fieldcount = $result->num_rows;

		echo '"field":[';
		for ($i = 0; $i < $fieldcount; $i++){
			$row = $result->fetch_array(MYSQLI_NUM);
			echo '[' . $row[0] . ', ' . $row[1] . ', ' . $row[2] . ', ' . $row[3] . ']';
			if ($i < $fieldcount-1) {echo ', '; }
		}
		echo ']}';
	}
	
	function GetPlayerInfo($servername, $dbusername, $dbpassword){
		$sessionid = $_POST["SID"];
		$ingame = 0;

		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }

		$sql = "SELECT Count(*), status, lobbyid FROM sessions WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
	
		if ($result[0] == 0 || $result[1] == 0){
			echo -1;
			$m_conn->close();
			exit();
		} 
		
		if ($result[1] == 2){
			$ingame = 1;
		}

		$lobbyid = $result[2];
		
		$sql = "SELECT Count(*), adminid FROM lobbies WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
	
		$m_conn->close();

		if ($result[0] == 0){
			echo -1;
			exit();
		}
		
		$playerinfo = array();
		$playerinfo[0] = $sessionid;
		$playerinfo[1] = $ingame;
		$playerinfo[2] = $lobbyid;
		$playerinfo[3] = ($result[1] == $sessionid) ? 1 : 0;
		
		return $playerinfo;
	}

	function GameOver($playerinfo, $servername, $dbusername, $dbpassword){
		$sessionid = $playerinfo[0];
		$lobbyid = $playerinfo[2];
		$isadmin = $playerinfo[3];
		
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$now = time();
		$sql = "UPDATE players SET playerid = NULL WHERE playerid = $PID;
				UPDATE players SET playerid = playerid - 1 WHERE playerid > $PID;
				DELETE FROM hands WHERE playerid = $PID;
				UPDATE hands SET playerid = playerid - 1 WHERE playerid > $PI;
				UPDATE gamestates SET playercount = playercount-1 WHERE playercount != 0;
				UPDATE gamestates SET isactive = 0 WHERE playercount = 0;
				UPDATE gamestates SET winnerid = $sessionid WHERE winnerid IS NULL;
				UPDATE gamestates SET lastupdated = $now;";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
		
		$sql = "SELECT winnerid, isactive, playercount FROM gamestates";
		$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
	
		$winstatus = 0;
		if($result[0] == $UID){
			$winstatus = 1;
		} else if($result[1] == 0){
			$winstatus = -1;
		}

		$playercount = $result[2];

		if($playercount - 1 >= $PID){
			$sql = "UPDATE gamestates SET chargerid = 0";
			if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		}

		$m_conn->close();
		
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "UPDATE sessions SET status = 1, last_seen = $now WHERE id = $sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
	
		$sql = "SELECT userid FROM sessions WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
		$userid = $m_conn->query($sql)->fetch_array(MYSQLI_NUM)[0];
	
		$sql = "UPDATE users SET games = games+1 WHERE id = $userid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		
		if($winstatus == 1){
			$sql = "UPDATE users SET wins = wins+1 WHERE id = $userid";
			if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		} else if($winstatus == -1){
			$sql = "UPDATE users SET losses = losses+1 WHERE id = $userid";
			if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		}

		return $winstatus;
	}
?>