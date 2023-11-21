<?php
	session_start();
	$config = parse_ini_file("config/serverconfig.ini");
	
	$servername = $config['databaseservername'];
	$dbusername = $config['databaseusername'];
	$dbpassword = $config['databasepassword'];
	
	$dbname = "vms";
	
	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	//session id needs to be turned into double quoted string to work with MySQL
	$phpsessionid = '"' . session_id() . '"';
	$time = time();
	$action = $_POST["action"];
	
	switch ($action) {
		case "0":
			GetAccount($conn);
			break;
		case "1":
			//Create session or change nickname
			AddSession($conn, GetAccount($conn), $phpsessionid, $time);
			break;
		case "2":
			ChangeAccName($conn, GetAccount($conn));
			break;
		case "3":
			ChangeAccPassword($conn, GetAccount($conn));
			break;
		case "4":
			LogOut($conn, $phpsessionid, $time);
			break;
		case "5":
			CreateAccount($conn);
			AddSession($conn, GetAccount($conn), $phpsessionid, $time);
			break;
	}

	$sql = "SELECT id, php_session_id FROM sessions WHERE php_session_id=$phpsessionid";
	$result = $conn->query($sql);
	$row = $result -> fetch_array(MYSQLI_NUM);
	
	if ($result->num_rows > 0){
		$sessionid = $row[0];

		$sql = "UPDATE sessions SET last_seen=$time WHERE id=$sessionid";
		if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
	}

	$conn->close();
	exit();
	
	function GetAccount($conn){
		$accountname = $_POST["account"];
		$accountpassword = $_POST["password"];
	
		$stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $accountname);
		$stmt->execute();
		$result = $stmt->get_result();
		$stmt->close();
		$row = $result -> fetch_array(MYSQLI_NUM);
	
		$userexists = $result->num_rows == 1;
	
		if($userexists){
			$userid = $row[0];
			$passwordhash = $row[1];

			if(password_verify($accountpassword, $passwordhash)){
				$algorithm = PASSWORD_BCRYPT;
				$options = ['cost' => 12];
			
				if (password_needs_rehash($passwordhash, $algorithm, $options)) {
					$rehashedpassword = password_hash($passwordhash, $algorithm, $options);
		
					$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = $userid");
					if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
					$stmt->bind_param("s", $rehashedpassword);
					$stmt->execute();
					$stmt->close();
				}
				echo 0;
				
				return $userid;
			}
		} 

		echo 1; 
		$conn->close();
		exit();
	}

	function ChangeAccName($conn, $userid){
		$accname = $_POST["accname"];

		//Tests if other account already has that username
		$stmt = $conn->prepare("SELECT Count(*) FROM users WHERE username = ?");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $accname);
		$stmt->execute();
		$result = $stmt->get_result()->fetch_array(MYSQLI_NUM);
		$stmt->close();

		if ($result[0] == 0){
			$stmt = $conn->prepare("UPDATE users SET username = ? WHERE id=$userid");
			if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
			$stmt->bind_param("s", $accname);
			$stmt->execute();
			$stmt->close();
		} else { echo 1; }
	}
	
	function ChangeAccPassword($conn, $userid){
		$accpassword = $_POST["accpassword"];
		$cost = 12;
		$hashedpassword = password_hash($accpassword, PASSWORD_BCRYPT, ["cost" => 12]);

		$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id=$userid");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $hashedpassword);
		$stmt->execute();
		$stmt->close();
	}

	function LogOut($conn, $phpsessionid, $time){
		$sql = "SELECT id, php_session_id FROM sessions WHERE php_session_id=$phpsessionid";
		$result = $conn->query($sql);
		$row = $result -> fetch_array(MYSQLI_NUM);
	
		if ($result->num_rows > 0){
			$sessionid = $row[0];

			$sql = "DELETE FROM sessions WHERE id=$sessionid";
			if ($conn->query($sql) === FALSE) { echo "Error updating record: " . $conn->error; }
		}

		$conn->close();
		echo 0;
		exit();
	}

	function CreateAccount($conn){
		$accountname = $_POST["account"];
		$accountpassword = $_POST["password"];
	
		$stmt = $conn->prepare("SELECT Count(*) FROM users WHERE username = ?");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $accountname);
		$stmt->execute();
		$result = $stmt->get_result()->fetch_array(MYSQLI_NUM);
		$stmt->close();

		if ($result[0] != 0){
			echo 1;
			$conn->close();
			exit();
		}
	
		$cost = 12;
		$hashedpassword = password_hash($accountpassword, PASSWORD_BCRYPT, ["cost" => 12]);

		$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("ss", $accountname, $hashedpassword);
		$stmt->execute();
		$stmt->close();
	}
	
	function AddSession($conn, $userid, $phpsessionid, $time){
		$nickname = $_POST["name"];
		$status = 0;

		$sql = "SELECT Count(*), id FROM sessions WHERE php_session_id=$phpsessionid";
		if ($conn->query($sql) === FALSE) { echo "Error: " . $conn->error; }
		$result = $conn->query($sql) -> fetch_array(MYSQLI_NUM);
		
		if ($result[0] == 0){
			$stmt = $conn->prepare("INSERT INTO sessions (php_session_id, userid, nickname, last_seen) VALUES ($phpsessionid, $userid, ?, $time)");
		} else {
			$sessionid = $result[1];
			$stmt = $conn->prepare("UPDATE sessions SET nickname = ?, last_seen=$time WHERE id=$sessionid");
		}
		if ($stmt === FALSE) { echo "Error: " . $stmt . "<br>" . $conn->error; }
		$stmt->bind_param("s", $nickname);
		$stmt->execute();
		$stmt->close();
		
		$conn->close();
		exit();
	}
?>