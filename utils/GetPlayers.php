<?php
	session_start();

	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "cardgame";

	$conn = new mysqli($servername, $username, $password, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	//session id needs to be a double quoted string to work with MySQL
	$sql = "SELECT id, playerid, nickname FROM players WHERE playerid IS NOT NULL";
	if ($conn->query($sql) === FALSE) { echo "Error reading record: " . $conn->error; }
	$result = $conn->query($sql);

	echo '{"Players":[';
	for($i = 0; $i < $result->num_rows; $i++){
		$row = $result->fetch_array(MYSQLI_NUM);
		$UID = $row[0];
		$PID = $row[1];
		$nick = $row[2];
		echo '{"UID":"' . $UID . '", "PID":"' . $PID . '", "nick":"' . $nick . '"}';
		if ($i != $result->num_rows - 1){ echo ","; }
	}

	echo ']}';
	
	
	$conn->close();
?>