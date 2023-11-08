<?php
	session_start();

	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "VMS";

	$conn = new mysqli($servername, $username, $password, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	//session id needs to be a double quoted string to work with MySQL
	$phpsessionid = '"' . session_id() . '"';
	$sql = "SELECT id, status, last_seen FROM sessions WHERE php_session_id=$phpsessionid";
	$result = $conn->query($sql);
	$row = $result->fetch_array(MYSQLI_NUM);
	
	$SID = null;
	$status = -1;
	$canchangetrump = null;
	$lgexpiration = null;
	if ($result->num_rows != 0){
		$SID = $row[0];
		$status = $row[1];
		$lgexpiration = $row[2] + 900 - time();
	}
	if ($status == "2"){
		//TODO make this get from room->players
		$sql = "SELECT canchangetrump FROM players WHERE id=$SID";
		$result = $conn->query($sql)->fetch_array(MYSQLI_NUM);
		$canchangetrump = $result[0];
	}
	
	echo '{"SID":"' . $SID . '", "status":"' . $status . '", "lgexp":"' . $lgexpiration . '", "canchangetrump":"' . $canchangetrump . '"}';

	$conn->close();
?>