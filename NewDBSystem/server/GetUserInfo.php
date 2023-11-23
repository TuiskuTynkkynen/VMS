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
		$lgexpiration = $row[3] + 900 - time();
	}
	if ($status == "2"){
		//TODO make this get from lobby->players
		$sql = "SELECT canchangetrump FROM players WHERE id=$SID";
		$result = $conn->query($sql)->fetch_array(MYSQLI_NUM);
		$canchangetrump = $result[0];
	}
	
	echo '{"SID":"' . $SID . '", "status":"' . $status . '", "lobby":"' . $lobby . '", "lgexp":"' . $lgexpiration . '", "canchangetrump":"' . $canchangetrump . '"}';

	$conn->close();
?>