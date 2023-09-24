<?php
	session_start();

	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "cardgame";
	
	$conn = new mysqli($servername, $username, $password, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	//Add account to database
	$account = $_POST["account"];
	$PIN = $_POST["PIN"];
	$nick = $_POST["name"];
	
	$sql = "SELECT id FROM leaderboard WHERE name = $account";
	$stmt = $conn->prepare("SELECT id FROM leaderboard WHERE name = ?");
	if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
	$stmt->bind_param("s", $account);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	if ($result->num_rows == 0){
		
		$stmt = $conn->prepare("INSERT INTO leaderboard (name, PIN) VALUES (?, ?)");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("ss", $account, $PIN);
		$stmt->execute();
		$stmt->close();

		echo 0;
	}
	else {
		$conn->close();
		echo 1;
		exit();
	}
	
	//Add new user to sessions

	//session id needs to be turned into double quoted string to work with MySQL
	$x = '"' . session_id() . '"';
	$y = time();
	$status = 0;
	
	$stmt = $conn->prepare("SELECT id FROM leaderboard WHERE name = ?");
	if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
	$stmt->bind_param("s", $account);
	$stmt->execute();
	$result = $stmt->get_result();
	$stmt->close();
	$row = $result -> fetch_array(MYSQLI_NUM);
	
	$lbid = $row[0];

	$sql = "SELECT id, session_id FROM sessions WHERE session_id=$x";
	$result = $conn->query($sql);
	$row = $result -> fetch_array(MYSQLI_NUM);
	
	if ($result->num_rows == 0){
		
		$stmt = $conn->prepare("INSERT INTO sessions (session_id, lbid, nickname, last_seen) VALUES ($x, $lbid, ?, $y)");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $nick);
		$stmt->execute();
		$stmt->close();
	
		$sql = "SELECT id, session_id FROM sessions WHERE session_id=$x";
		$result = $conn->query($sql)->fetch_array(MYSQLI_NUM);
		$universalid = $result[0];
	}
	else {
		$universalid = $row[0];

		$stmt = $conn->prepare("UPDATE sessions SET status = $status, nickname = ?, last_seen=$y WHERE id=$universalid");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $nick);
		$stmt->execute();
		$stmt->close();

	}
	
	$now = time();
	$sql = "UPDATE gamestates SET lastupdated = $now";
	if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }


	$conn->close();
?>