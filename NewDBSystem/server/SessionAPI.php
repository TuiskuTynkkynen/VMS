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
?>