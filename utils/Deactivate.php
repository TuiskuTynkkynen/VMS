<?php
	$ip_server = $_SERVER['SERVER_ADDR'];
	$ip_client = $_SERVER['REMOTE_ADDR'];

	if ($ip_client != $ip_server){
        exit("Insufficient credentials");
	}
	
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "cardgame";

	$conn = new mysqli($servername, $username, $password, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }

	$now = time();

	$sql = "UPDATE gamestates
	SET isactive=0, playercount=0, handsize=0, ischargeturn=0, chargerid=0, trumpcard0=0, trumpcard1=0, deckleft = 0, winnerid = NULL, lastupdated = $now
	WHERE id=1";

	if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }

	$sql = "UPDATE sessions SET status = 0 WHERE status=2";

	if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
?>