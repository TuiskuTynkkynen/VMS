<?php
	session_start();
	
	$config = parse_ini_file("config/serverconfig.ini");
	
	$servername = $config['databaseservername'];
	$dbusername = $config['databaseusername'];
	$dbpassword = $config['databasepassword'];

	//session id needs to be turned into double quoted string to work with MySQL
	$phpsessionid = '"' . session_id() . '"';

	$action = $_POST["action"];
	
	switch ($action) {
		case "0":
			GetUserInfo($servername, $dbusername, $dbpassword);
			break;
		case "1":
			GetUsers($servername, $dbusername, $dbpassword);
			break;
		case "2":
			GetUserStatus($servername, $dbusername, $dbpassword);
			break;
		case "3":
			GetPlayers($servername, $dbusername, $dbpassword);
			break;
	}

	function GetUserInfo($servername, $dbusername, $dbpassword){
		//session id needs to be a double quoted string to work with MySQL
		$phpsessionid = '"' . session_id() . '"';

		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
	
		$sql = "SELECT id, status, lobbyid, last_seen FROM sessions WHERE php_session_id=$phpsessionid";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql);
		$row = $result->fetch_array(MYSQLI_NUM);
	
		$SID = null;
		$status = -1;
		$lobby = -1;
		$canchangetrump = null;
		$inactivitytimer = null;

		if ($result->num_rows != 0){
			$SID = $row[0];
			$status = $row[1];
			$lobby =$row[2];
			$inactivitytimer = $row[3] + (29*60) - time();
		}
	
		$isadmin = 0;
		if ($lobby != "" && $lobby != -1){
			$sql = "SELECT adminid FROM lobbies WHERE id=$lobby";
			if ($m_conn->query($sql) === FALSE) {echo "Error: " . $m_conn->error; }
			$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
			if($result != null){
				$isadmin = ($SID == $result[0]);
			}
		}

		$now = time();
	
		if ($SID != null){
			$sql = "UPDATE sessions SET updaterequired = 0, last_seen = $now WHERE id = $SID";
			if ($m_conn->query($sql) === FALSE) { echo $sql . "      "; echo "Error updating record: " . $m_conn->error; }
		}

		if ($status == "2"){

			$dbname = "lobby" . $lobby;
			$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
			if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
	
			$sql = "SELECT canchangetrump FROM players WHERE id=$SID";
			$result = $m_conn->query($sql)->fetch_array(MYSQLI_NUM);
			$canchangetrump = $result[0];
		}

		echo '{"SID":"' . $SID . '", "status":"' . $status . '", "lobby":"' . $lobby . '", "isadmin":"' . $isadmin . '", "inactive":"' . $inactivitytimer . '", "canchangetrump":"' . $canchangetrump . '"}';
	
		$m_conn->close();
	}

	function GetUsers($servername, $dbusername, $dbpassword){
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
	
		$sql = "SELECT nickname, status FROM sessions";
		$result = $m_conn->query($sql);

		echo '{"Users":[';
		for($i = 0; $i < $result->num_rows; $i++){
			$row = $result->fetch_array(MYSQLI_NUM);
			$nick = $row[0];
			$status = $row[1];
			echo '{"nick":"' . $nick . '", "status":"' . $status . '"}';
			if ($i != $result->num_rows - 1){ echo ","; }
		}

		echo ']}';
	
		$m_conn->close();
	}

	function GetUserStatus($servername, $dbusername, $dbpassword){
		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
	
		//session id needs to be a double quoted string to work with MySQL
		$phpsessionid = '"' . session_id() . '"';
		$sql = "SELECT status FROM sessions WHERE php_session_id=$phpsessionid";
		if ($m_conn->query($sql) === FALSE) {echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql);
		$row = $result->fetch_array(MYSQLI_NUM);
	
		$status = -1;
	
		if ($result->num_rows != 0){
			$status = $row[0];
		}

		echo $status;

		$m_conn->close();
	}

	function GetPlayers($servername, $dbusername, $dbpassword){
		$dbname = "lobby" . $_POST['lobbyid'];

		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("Connection failed: " . $m_conn->connect_error); }
	
		$sql = "SELECT id, playerid, nickname FROM players WHERE playerid IS NOT NULL";
		if ($m_conn->query($sql) === FALSE) { echo "Error reading record: " . $m_conn->error; }
		$result = $m_conn->query($sql);

		echo '{"Players":[';
		for($i = 0; $i < $result->num_rows; $i++){
			$row = $result->fetch_array(MYSQLI_NUM);
			$SID = $row[0];
			$PID = $row[1];
			$nick = $row[2];
			echo '{"SID":"' . $SID . '", "PID":"' . $PID . '", "nick":"' . $nick . '"}';
			if ($i != $result->num_rows - 1){ echo ","; }
		}

		echo ']}';
	
		$m_conn->close();
	}
?>