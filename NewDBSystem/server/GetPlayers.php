<?php
	session_start();
	//session id needs to be turned into double quoted string to work with MySQL
	$phpsessionid = '"' . session_id() . '"';
	
	$config = parse_ini_file("config/serverconfig.ini");
	
	$servername = $config['databaseservername'];
	$dbusername = $config['databaseusername'];
	$dbpassword = $config['databasepassword'];

	$dbname = "lobby" . $_REQUEST['lobbyid'];

	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	$sql = "SELECT id, playerid, nickname FROM players WHERE playerid IS NOT NULL";
	if ($conn->query($sql) === FALSE) { echo "Error reading record: " . $conn->error; }
	$result = $conn->query($sql);

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
	
	$conn->close();
?>