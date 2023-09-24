<?php
	session_start();

	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "cardgame";

	$conn = new mysqli($servername, $username, $password, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	$UID = $_POST["UID"];
	$status = $_POST["status"];
	$now = time();

	$sql = "UPDATE sessions SET status = $status, last_seen = $now WHERE id = $UID";
	if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }

	$sql = "UPDATE gamestates SET lastupdated = $now";
	if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }

	$conn->close();
?>