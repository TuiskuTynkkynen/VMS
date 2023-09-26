<?php
	session_start();
	
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "cardgame";
	
	$conn = new mysqli($servername, $username, $password, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	//session id needs to be turned into double quoted string to work with MySQL
	$x = '"' . session_id() . '"';
	$y = time();
	$account = $_POST["account"];
	$PIN = $_POST["PIN"];
	$action = $_POST["action"];
	
	$stmt = $conn->prepare("SELECT id, PIN FROM leaderboard WHERE name = ?");
	if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
	$stmt->bind_param("s", $account);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	$row = $result -> fetch_array(MYSQLI_NUM);
	
	if ($result->num_rows == 0){
		echo 1; 
		$conn->close();
		exit();
	}
	
	$lbid = $row[0];

	if ($row[1] != $PIN){
		echo 2; 
		$conn->close();
		exit();
	}
	
	$now = time();
	$sql = "UPDATE gamestates SET lastupdated = $now";
	if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
	echo 0;

	switch ($action) {
	case "1":
		ChangeNick($conn, $lbid, $x, $y);
		break;
	}

	$conn->close();

	function ChangeNick($conn, $lbid, $x, $y){
		$name = $_POST["name"];

		$sql = "SELECT id, session_id FROM sessions WHERE session_id=$x";
		$result = $conn->query($sql);
		$row = $result -> fetch_array(MYSQLI_NUM);
	
		if ($result->num_rows == 0){
		
			$stmt = $conn->prepare("INSERT INTO sessions (session_id, lbid, nickname, last_seen) VALUES ($x, $lbid, ?, $y)");
			if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
			$stmt->bind_param("s", $name);
			$stmt->execute();
			$stmt->close();
	
			$sql = "SELECT id, session_id FROM sessions WHERE session_id=$x";
			$result = $conn->query($sql)->fetch_array(MYSQLI_NUM);
			$universalid = $result[0];
		}
		else {
			$universalid = $row[0];

			$stmt = $conn->prepare("UPDATE sessions SET nickname = ?, last_seen=$y WHERE id=$universalid");
			if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
			$stmt->bind_param("s", $name);
			$stmt->execute();
			$stmt->close();

		}	
	}
?>