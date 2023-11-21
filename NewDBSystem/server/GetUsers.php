<?php
	session_start();
	
	$config = parse_ini_file("config/serverconfig.ini");
	
	$servername = $config['databaseservername'];
	$dbusername = $config['databaseusername'];
	$dbpassword = $config['databasepassword'];

	$dbname = "vms";

	$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
	if ($conn->connect_error) {die("Connection failed: " . $conn->connect_error); }
	
	//session id needs to be a double quoted string to work with MySQL
	$sql = "SELECT nickname, status FROM sessions";
	$result = $conn->query($sql);

	echo '{"Users":[';
	for($i = 0; $i < $result->num_rows; $i++){
		$row = $result->fetch_array(MYSQLI_NUM);
		$nick = $row[0];
		$status = $row[1];
		echo '{"nick":"' . $nick . '", "status":"' . $status . '"}';
		if ($i != $result->num_rows - 1){ echo ","; }
	}

	echo ']}';
	
	
	$conn->close();
?>