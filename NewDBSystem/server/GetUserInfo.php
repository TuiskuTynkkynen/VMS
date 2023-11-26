<?php
	session_start();

	$config = parse_ini_file("config/serverconfig.ini");
	
	$servername = $config['databaseservername'];
	$dbusername = $config['databaseusername'];
	$dbpassword = $config['databasepassword'];
	
	$dbname = "vms";

	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	//session id needs to be a double quoted string to work with MySQL
	$phpsessionid = '"' . session_id() . '"';
	$sql = "SELECT id, status, lobbyid, last_seen FROM sessions WHERE php_session_id=$phpsessionid";
	if ($conn->query($sql) === FALSE) {echo "Error: " . $conn->error; }
	$result = $conn->query($sql);
	$row = $result->fetch_array(MYSQLI_NUM);
	
	$SID = null;
	$status = -1;
	$lobby = -1;
	$canchangetrump = null;
	$lgexpiration = null;
	if ($result->num_rows != 0){
		$SID = $row[0];
		$status = $row[1];
		$lobby =$row[2];
		$lgexpiration = $row[3] + 1700 - time();
	}
	if ($status == "2"){
		//TODO make this get from lobby->players
		$sql = "SELECT canchangetrump FROM players WHERE id=$SID";
		$result = $conn->query($sql)->fetch_array(MYSQLI_NUM);
		$canchangetrump = $result[0];
	}

	$isadmin = 0;
	if ($lobby != "" && $lobby != -1){
		$sql = "SELECT adminid FROM lobbies WHERE id=$lobby";
		if ($conn->query($sql) === FALSE) {echo "Error: " . $conn->error; }
		$result = $conn->query($sql)->fetch_array(MYSQLI_NUM);
		if($result != null){
			$isadmin = ($SID == $result[0]);
		}
	}

	echo '{"SID":"' . $SID . '", "status":"' . $status . '", "lobby":"' . $lobby . '", "isadmin":"' . $isadmin . '", "lgexp":"' . $lgexpiration . '", "canchangetrump":"' . $canchangetrump . '"}';
	
	$now = time();
	
	if ($SID != null){
		$sql = "UPDATE sessions SET updaterequired = 0, last_seen = $now WHERE id = $SID";
		if ($conn->query($sql) === FALSE) { echo $sql . "      "; echo "Error updating record: " . $conn->error; }
	}
	
	$conn->close();
?>