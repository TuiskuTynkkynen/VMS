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
	$phpsessionid = '"' . session_id() . '"';
	$sql = "SELECT status FROM sessions WHERE php_session_id=$phpsessionid";
	if ($conn->query($sql) === FALSE) {echo "Error: " . $conn->error; }
	$result = $conn->query($sql);
	$row = $result->fetch_array(MYSQLI_NUM);
	
	$status = -1;
	
	if ($result->num_rows != 0){
		$status = $row[0];
	}

	echo $status;

	$conn->close();
?>