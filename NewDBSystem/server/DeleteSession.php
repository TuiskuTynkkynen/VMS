<?php

	$sessionid = $argv[1];
	$status = $argv[2];
	$lobbyid = $argv[3];

	$config = parse_ini_file("config/serverconfig.ini");
	
	$servername = $config['databaseservername'];
	$dbusername = $config['databaseusername'];
	$dbpassword = $config['databasepassword'];

	$dbname = "vms";
	$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);

	if ($status != 0 && $lobbyid != -1){
		$isadmin = IsAdmin($sessionid, $lobbyid, $mysqli);
		LeaveLobby($sessionid, $isadmin,  $lobbyid, $servername, $dbusername, $dbpassword);
	}

	$sql = "DELETE FROM sessions WHERE id = $sessionid";
	if ($mysqli->query($sql) === FALSE) { echo "Error updating record: " . $mysqlir->error; }
	
	$mysqli->close();

	function IsAdmin($sessionid, $lobbyid, $m_conn){
		$sql = "SELECT Count(*), adminid FROM lobbies WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);

		if ($result[0] == 0){
			echo -1;
			exit();
		} else if ($result[1] != $sessionid){
			return 0;
		} else {
			return 1;
		}
	}
	
	function LeaveLobby($sessionid, $isadmin, $lobbyid, $servername, $dbusername, $dbpassword){
		$now = time();
		$newadminid = -1;

		$dbname = "lobby" . $lobbyid;
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		$sql = "SELECT Count(*) FROM players";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
		$playercount = $result[0];
		
		$sql = "SELECT Count(*) FROM players WHERE id=$sessionid";
		if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
		$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
		
		if ($result[0] == 0){
			echo -1;
			exit();
		} else {
			$sql = "DELETE FROM players WHERE id=$sessionid";
			if ($m_conn->query($sql) === FALSE) { echo "Error deleting record: " . $m_conn->error; }
			
			if($isadmin == 1){
				$sql = "SELECT id FROM players LIMIT 1";
				if ($m_conn->query($sql) === FALSE) { echo "Error: " . $m_conn->error; }
				$result = $m_conn->query($sql) -> fetch_array(MYSQLI_NUM);
				
				if ($playercount > 1){
					$newadminid = $result[0];
				}
			}
		}

		$m_conn->close();

		$dbname = "vms";
		$m_conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
		if ($m_conn->connect_error) {die("New connection failed: " . $m_conn->connect_error); }
		
		if ($playercount == 1){
			$sql = "DROP DATABASE IF EXISTS lobby" . $lobbyid;
			if ($m_conn->query($sql) === FALSE) { echo "Error deleting database: " . $m_conn->error; }

			$sql = "DELETE FROM lobbies WHERE id = $lobbyid";
			if ($m_conn->query($sql) === FALSE) { echo "Error deleting record: " . $m_conn->error; }
			
			$m_conn->close();
			return;
		}
		
		if ($newadminid != -1){
			$sql = "UPDATE lobbies SET adminid = $newadminid WHERE id = $lobbyid";
			if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		}
		
		$sql = "UPDATE lobbies SET playercount = $playercount-1, lastupdated = $now WHERE id = $lobbyid";
		if ($m_conn->query($sql) === FALSE) { echo "Error updating record: " . $m_conn->error; }
		
		$m_conn->close();
	}
?>