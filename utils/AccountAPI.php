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
	$action = $_POST["action"];

	switch ($action) {
		case "0":
			GetAccount($conn);
			break;
		case "1":
			ChangeNick($conn, GetAccount($conn), $x, $y);
			break;
		case "2":
			ChangeAccName($conn, GetAccount($conn));
			break;
		case "3":
			ChangeAccPIN($conn, GetAccount($conn));
			break;
		case "4":
			LogOut($conn, $x, $y);
			break;
	}

	$sql = "UPDATE gamestates SET lastupdated = $y";
	if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }

	$sql = "SELECT id, session_id FROM sessions WHERE session_id=$x";
	$result = $conn->query($sql);
	$row = $result -> fetch_array(MYSQLI_NUM);
	
	if ($result->num_rows > 0){
		$universalid = $row[0];

		$sql = "UPDATE sessions SET last_seen=$y WHERE id=$universalid";
		if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
	}
	$conn->close();
	
	function GetAccount($conn){
		$account = $_POST["account"];
		$PIN = $_POST["PIN"];
	
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
	
		echo 0;
		return $lbid;
	}

	function ChangeNick($conn, $lbid, $x, $y){
		$nickname = $_POST["nickname"];

		$sql = "SELECT id, session_id FROM sessions WHERE session_id=$x";
		$result = $conn->query($sql);
		$row = $result -> fetch_array(MYSQLI_NUM);
	
		if ($result->num_rows == 0){
		
			$stmt = $conn->prepare("INSERT INTO sessions (session_id, lbid, nickname, last_seen) VALUES ($x, $lbid, ?, $y)");
			if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
			$stmt->bind_param("s", $nickname);
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
			$stmt->bind_param("s", $nickname);
			$stmt->execute();
			$stmt->close();
		}	
	}

	function ChangeAccName($conn, $lbid){
		$accname = $_POST["accname"];

		$stmt = $conn->prepare("SELECT id FROM leaderboard WHERE name = ?");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $accname);
		$stmt->execute();
		$result = $stmt->get_result();
		$stmt->close();

		if ($result->num_rows == 0){
			$stmt = $conn->prepare("UPDATE leaderboard SET name = ? WHERE id=$lbid");
			if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
			$stmt->bind_param("s", $accname);
			$stmt->execute();
			$stmt->close();
		} else { echo 1; }
	}
	
	function ChangeAccPIN($conn, $lbid){
		$accpin = $_POST["accpin"];

		$stmt = $conn->prepare("UPDATE leaderboard SET PIN = ? WHERE id=$lbid");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $accpin);
		$stmt->execute();
		$stmt->close();
	}

	function LogOut($conn, $x, $y){
		$sql = "SELECT id, session_id FROM sessions WHERE session_id=$x";
		$result = $conn->query($sql);
		$row = $result -> fetch_array(MYSQLI_NUM);
	
		if ($result->num_rows > 0){
			$universalid = $row[0];

			$sql = "DELETE FROM sessions WHERE id=$universalid";
			if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
		}

		$sql = "UPDATE gamestates SET lastupdated = $y";
		if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
		
		$conn->close();
		echo 0;
		exit();
	}
?>