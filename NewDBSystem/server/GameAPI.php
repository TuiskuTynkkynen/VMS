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
			$playerinfo = GetPlayerInfo($servername, $dbusername, $dbpassword);
			Charge($playerinfo, $servername, $dbusername, $dbpassword);
			if ($_POST["owncharge"] == "0"){
				Draw($playerinfo, $servername, $dbusername, $dbpassword);
			}
			break;
		case "2":
			$playerinfo = GetPlayerInfo($servername, $dbusername, $dbpassword);
			KillCard($playerinfo, $servername, $dbusername, $dbpassword);
			break;
		case "3":
			$playerinfo = GetPlayerInfo($servername, $dbusername, $dbpassword);
			KillField($playerinfo, $servername, $dbusername, $dbpassword);
			Draw($playerinfo, $servername, $dbusername, $dbpassword);
			break;
		case "4":
			$playerinfo = GetPlayerInfo($servername, $dbusername, $dbpassword);
			ChangeTrump($playerinfo, $servername, $dbusername, $dbpassword);
			break;
	}

	function GetGameInfo($playerinfo, $servername, $dbusername, $dbpassword){
		$sessionid = $playerinfo[0];
		$ingame = $playerinfo[1];
		$lobbyid = $playerinfo[2];
		$isadmin = $playerinfo[3];
		$PID = $playerinfo[4];
	
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
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

		$sql = "SELECT card0, card1, id FROM hands WHERE playerid = $PID";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$result = $m_conn->query($sql);
		$handcount = $result->num_rows;

		if (($playercount <= 1 || $handcount <= 0) && $ingame == 1 && $deckleft <= 0){
			$ingame = 0;
			$winstatus = GameOver($playerinfo, $servername, $dbusername, $dbpassword);
			
			echo '"gameover":{"ingame":' . $ingame . ', "winstatus":' . $winstatus . '}, ';
		}

		echo '"hand":[';
		for ($i = 0; $i < $handcount; $i++){
			$row = $result->fetch_array(MYSQLI_NUM);
			echo '[' . $row[0] . ', ' . $row[1] . ', ' . $row[2] . ']';
			if ($i < $handcount-1) {echo ', '; }
		}
		echo '], ';
		
		$sql = "SELECT card0, card1, state, killsid FROM field";
		$result = $m_conn->query($sql);
		$fieldcount = $result->num_rows;

		echo '"field":[';
		for ($i = 0; $i < $fieldcount; $i++){
			$row = $result->fetch_array(MYSQLI_NUM);
			$row[3] = ($row[3] == null) ? -1 : $row[3];
			echo '[' . $row[0] . ', ' . $row[1] . ', ' . $row[2] . ', ' . $row[3] . ']';
			if ($i < $fieldcount-1) {echo ', '; }
		}
		echo ']}';
	}
	
	function Charge($playerinfo, $servername, $dbusername, $dbpassword){
		$lobbyid = $playerinfo[2];
		$PID = $playerinfo[4];
		$CardsJSON = $_REQUEST["Cards"];
		$isSupporting = $_REQUEST["Support"];
		$ChargeTurn = $_REQUEST["ChargeTurn"];
		$CID = $_REQUEST["CID"];
		$Cards = json_decode($CardsJSON)->{'cards'};
		$cardcount = count($Cards);
		$now = time();

		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "SELECT ischargeturn, chargerid, playercount FROM gamestates";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);

		if($ChargeTurn != $result[0] || $CID != $result[1]){
			echo 1;
			$m_conn->close();
			exit();
		}

		$playercount = $result[2];
		$opponent = $CID;
		if ($isSupporting == 0) { 
			$opponent++;
			$opponent = ($opponent >= $playercount) ? 0 : $opponent;
		}

		$sql = "SELECT COUNT(*) FROM hands WHERE playerid = $opponent";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$hand = $m_conn->query($sql)->fetch_array(MYSQLI_NUM)[0];
		
		$sql = "SELECT COUNT(*) FROM field WHERE state = 0";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$field = $m_conn->query($sql)->fetch_array(MYSQLI_NUM)[0];

		$total = $field + $cardcount;

		if($hand < $total){
			echo 2;
			exit();
		}
		
		if ($isSupporting == 0){
			echo "main";
			for ($i = 0; $i < $cardcount; $i++) { 
				$y = $Cards[$i][0];
				$z= $Cards[$i][1];
				$sql = "INSERT INTO field (id, card0, card1, state) VALUES ($i, $y, $z, 0)";
				if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
			}

			$newCID = $CID + 1;
			$newCID = ($newCID >= $playercount) ? 0 : $newCID;
			
			$sql = "UPDATE gamestates SET ischargeturn = 0, chargerid = $newCID";
			if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		} else {
			echo "support";
			$sql = "SELECT COUNT(*) FROM field";
			if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
			$x = $m_conn->query($sql)->fetch_array(MYSQLI_NUM)[0];
	
			for ($i = 0; $i < $cardcount; $i++) {
				$y = $Cards[$i][0];
				$z = $Cards[$i][1];
				$sql = "INSERT INTO field (id, card0, card1, state)
				VALUES ($x + $i, $y, $z, 0)";
				if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
			}
		}
		
		for ($i = 0; $i < $cardcount; $i++) {
			$y = $Cards[$i][0];
			$z= $Cards[$i][1];
			$sql = "DELETE FROM hands WHERE card0 = $y AND card1 = $z AND playerid = $PID LIMIT 1";
			if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
		}
		
		$m_conn->close();

		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "UPDATE lobbies SET lastupdated = $now WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $sql . "<br>" . $m_conn->error; }
		
		$m_conn->close();
	}

	function KillCard($playerinfo, $servername, $dbusername, $dbpassword){
		$lobbyid = $playerinfo[2];
		$PID = $playerinfo[4];
		$CardJSON = $_REQUEST["Card"];
		$KillsId = $_REQUEST["KillsId"];
		$Card = json_decode($CardJSON)->{'card'};
		$card0 = $Card[0][0];
		$card1 = $Card[0][1];
		$now = time();

		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "SELECT COUNT(*) FROM field WHERE id = $KillsId";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$killcount = $m_conn->query($sql)->fetch_array(MYSQLI_NUM)[0];
		
		$sql = "SELECT ischargeturn, chargerid FROM gamestates";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
		
		if($killcount == 0 || $result[0] != 0 || $result[1] != $PID){
			echo 1;
			$m_conn->close();
			exit();
		}

		$sql = "SELECT COUNT(*) FROM field";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$id =  $m_conn->query($sql)->fetch_array(MYSQLI_NUM)[0];
		
		$sql = "DELETE FROM hands WHERE card0 = $card0 AND card1 = $card1 AND playerid = $PID LIMIT 1";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error; }
	
		$sql = "INSERT INTO field (id, card0, card1, state, killsid)
		VALUES ($id, $card0, $card1,  2, $KillsId)";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error; }
	
		$sql = "UPDATE field SET state = 1 WHERE id = $KillsId";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $sql . "<br>" . $m_conn->error; }
		
		$m_conn->close();

		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "UPDATE lobbies SET lastupdated = $now WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $sql . "<br>" . $m_conn->error; }

		$m_conn->close();
		echo 0;
	}

	function KillField($playerinfo, $servername, $dbusername, $dbpassword){
		$lobbyid = $playerinfo[2];
		$PID = $playerinfo[4];
		$now = time();

		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "SELECT ischargeturn, chargerid, playercount FROM gamestates";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
		
		if($result[0] != 0 || $result[1] != $PID){
			echo 1;
			$m_conn->close();
			exit();
		}
		
		$newCID = $result[1] + 1;
		$newCID = ($newCID >= $result[2]) ? 0 : $newCID;

		
		$sql = "SELECT card0, card1 FROM field WHERE state=0";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$result = $m_conn->query($sql);
		
		$alivecount = $result->num_rows;
		$newCID = ($alivecount == 0) ? $PID : $newCID;

		for($i = 0; $i < $alivecount; $i++){
			$row = $result->fetch_array(MYSQLI_NUM);
			$y = $row[0];
			$z = $row[1];
			$sql = "INSERT INTO hands (playerid, card0, card1) VALUES ($PID, $y, $z)";
			if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
		}

		$sql = "UPDATE gamestates SET ischargeturn=1, chargerid=$newCID";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }

		$sql = "DELETE FROM field";
		if ($m_conn->query($sql) === FALSE) { echo "Error deleting record: " . $sql . "<br>" . $m_conn->error; }

		$m_conn->close();

		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "UPDATE lobbies SET lastupdated = $now WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $sql . "<br>" . $m_conn->error; }

		$m_conn->close();
		echo 0;
	}

	function ChangeTrump($playerinfo, $servername, $dbusername, $dbpassword){
		$lobbyid = $playerinfo[2];
		$PID = $playerinfo[4];
		$now = time();

		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "SELECT trumpcard0, trumpcard1 FROM gamestates";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}

		$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
		$tcard0 = $result[0];
		$tcard1 = $result[1];

		$sql = "UPDATE hands SET card1 = $tcard1 WHERE card0=$tcard0 AND card1=2 AND playerid = $PID";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}

		$sql = "UPDATE gamestates SET trumpcard1 = 2 WHERE id = 1";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}

		$sql = "UPDATE players SET canchangetrump = 0 WHERE playerid = $PID";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
		
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "UPDATE lobbies SET lastupdated = $now WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $sql . "<br>" . $m_conn->error; }

		$m_conn->close();
		echo 0;
	}

	function GetPlayerInfo($servername, $dbusername, $dbpassword){
		$sessionid = $_POST["SID"];
		$ingame = 0;

		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }

		$sql = "SELECT COUNT(*), status, lobbyid FROM sessions WHERE id=$sessionid";
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
		
		$sql = "SELECT COUNT(*), adminid FROM lobbies WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
		
		if ($result[0] == 0){
			echo -1;
			$m_conn->close();
			exit();
		}
		
		$isadmin = ($result[1] == $sessionid) ? 1 : 0;

		$m_conn->close();
		
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$sql = "SELECT COUNT(*), playerid FROM players WHERE id = $sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
		$PID = $result[1];
		
		if ($result[0] == 0 || $PID == null){
			echo -1;
			$m_conn->close();
			exit();
		}

		$m_conn->close();
		
		$playerinfo = array();
		$playerinfo[0] = $sessionid;
		$playerinfo[1] = $ingame;
		$playerinfo[2] = $lobbyid;
		$playerinfo[3] = $isadmin;
		$playerinfo[4] = $PID;
		
		return $playerinfo;
	}

	function Draw($playerinfo, $servername, $dbusername, $dbpassword){
		$lobbyid = $playerinfo[2];
		$PID = $playerinfo[4];

		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
	
		$sql = "SELECT COUNT(*) FROM hands WHERE playerid = $PID";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$handcount = $m_conn->query($sql)->fetch_array(MYSQLI_NUM)[0];

		$sql = "SELECT deckleft, handsize FROM gamestates";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $m_conn->error; }
		$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
		$deckleft = $result[0];
		$drawcount = $result[1] - $handcount;

		for ($i = 0; $i < $drawcount; $i++) {
			$deckleft--;
			if ($deckleft < 0) { 
				$deckleft = 0;
				break;
			}
			
			$sql = "SELECT deck0, deck1 FROM deck WHERE id = $deckleft";	
			if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}

			$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
			$y = $result[0];
			$z= $result[1];
			echo $y .",". $z . "/";
	
			$sql = "INSERT INTO hands (playerid, card0, card1) VALUES ($PID, $y, $z)";	
			if ($m_conn->query($sql) === FALSE) {
				echo "Error: " . $sql . "<br>" . $m_conn->error;
				$deckleft++;
			} else {
				$sql = "DELETE FROM deck WHERE id=$deckleft";	
				if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
			}
		}

		$sql = "UPDATE gamestates SET deckleft = $deckleft";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
	}

	function GameOver($playerinfo, $servername, $dbusername, $dbpassword){
		$sessionid = $playerinfo[0];
		$lobbyid = $playerinfo[2];
		$isadmin = $playerinfo[3];
		$PID = $playerinfo[4];
		
		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
		
		$now = time();
		$sql[0] = "UPDATE players SET playerid = NULL WHERE playerid = $PID";
		$sql[1] = "UPDATE players SET playerid = playerid-1 WHERE playerid > $PID";
		$sql[2] = "DELETE FROM hands WHERE playerid = $PID";
		$sql[3] = "UPDATE hands SET playerid = playerid-1 WHERE playerid > $PID";
		$sql[4] = "UPDATE gamestates SET playercount = playercount-1 WHERE playercount != 0";
		$sql[5] = "UPDATE gamestates SET isactive = 0 WHERE playercount = 0";
		$sql[6] = "UPDATE gamestates SET winnerid = $sessionid WHERE winnerid IS NULL";
		for ($i = 0; $i < count($sql); $i++){
			if ($m_conn->query($sql[$i]) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
		}
		
		$sql = "SELECT winnerid, isactive, playercount FROM gamestates";
		$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
	
		$winstatus = 0;
		if($result[0] == $sessionid){
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
		
		$sql = "SELECT userid FROM sessions WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
		$userid = $m_conn->query($sql)->fetch_array(MYSQLI_NUM)[0];
		
		$sql = array();
		$sql[0] = "UPDATE lobbies SET lastupdated = $now WHERE id = $lobbyid";
		$sql[1] = "UPDATE sessions SET status = 1, last_seen = $now WHERE id = $sessionid";
		$sql[2] = "UPDATE users SET games = games+1 WHERE id = $userid";
		
		if($winstatus == 1){
			$sql[3] = "UPDATE users SET wins = wins+1 WHERE id = $userid";
		} else if($winstatus == -1){
			$sql[3] = "UPDATE users SET losses = losses+1 WHERE id = $userid";
		}
		
		for ($i = 0; $i < count($sql); $i++){
			if ($m_conn->query($sql[$i]) === FALSE) {echo "Error: " . $sql . "<br>" . $m_conn->error;}
		}

		return $winstatus;
	}
?>