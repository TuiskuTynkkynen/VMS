<?php
//php -q VMS/server/VMSsocket.php

$address = $argv[1];
$port = 8080;
$clients = array();

// Create WebSocket.
$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($server, $address, $port);
socket_listen($server);
socket_set_nonblock($server);

$config = parse_ini_file("config/serverconfig.ini");
	
$servername = $config['databaseservername'];
$dbusername = $config['databaseusername'];
$dbpassword = $config['databasepassword'];

$dbname = "vms";
$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$serverdirectory = $config['serverdirectory'];
$sessionid = $status = $lobbyid = "-1 ";
$cmd = "php -q " . $serverdirectory . "/server/DeleteSession.php";

$minute = 60;
//TODO set $halfhour value to 30 * 60 instead of dev value
$halfhour = 24 * 3600;

$old_time_stamps = array();
$new_time_stamps = array();
$new_time_stamps[-1] = 0;
$updated_lobbies = array();

$last_io_message = -1;
$last_io_count = 0;

for($i = 0; $i < $config['logosize']; $i++){
    usleep(100);
    echo $config['logo' . $i] . "\r\n";
}

echo "VMS websocket opened\n";
// Send messages into WebSocket in a loop.
while (true) {
    
    if(($newc = socket_accept($server)) !== false)
    {
        if ($last_io_message != 1){
            echo "\r\nNew client has connected";
            $last_io_message = $last_io_count = 1;
        } else {
            echo ", X" . ++$last_io_count;
        }

        $clients[] = $newc;
        // Send WebSocket handshake headers.
        $request = socket_read($newc, 5000);
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
        $key = base64_encode(pack(
          'H*',
         sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
        ));
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
        socket_write($newc, $headers, strlen($headers));
    
        $content = '{"connection":"establised"}';
        $response = chr(129) . chr(strlen($content)) . $content;
        if (socket_write($newc, $response) == false){ $last_io_message = -1; };
    }
    
    sleep(1);
    
    $now = time();

    $sql = "SELECT id, status, lobbyid FROM sessions WHERE (updaterequired = 1 AND last_seen < $now-$minute) or last_seen < $now-$halfhour";
    $result = $mysqli->query($sql);
    $deletedsessioncount = $result->num_rows;

    for($i = 0; $i < $deletedsessioncount; $i++){
        $row = $result->fetch_array(MYSQLI_NUM);
        $sessionid = $row[0] . " ";
        $status = $row[1] . " ";
    
        $lobbyid = "-1 ";
        if ($row[2] != null){
            $lobbyid = $row[2] . " ";
        }

        $arguments = " " . $sessionid . $status . $lobbyid;
        
        //run DeleteUser.php in background with platform specific command
        if (substr(php_uname(), 0, 7) == "Windows"){
            pclose( popen("start /b ". $cmd . $arguments, "r" ) );
        } else {
            exec($cmd . $arguments .  " > /dev/null &"); 
        }

        
        if ($last_io_message != 2){
            echo "\r\nDeleted session with id = " . $sessionid;
            $last_io_message = 2;
        } else {
            echo  ", " . $sessionid;
        }
    }

    $sql = "SELECT id, lastupdated FROM lobbies";
    $result = $mysqli->query($sql);
    $lobbycount = $result->num_rows;

    $old_time_stamps = $new_time_stamps;
    
    unset($new_time_stamps);
    unset($updated_lobbies);

    $new_time_stamps[-1] = 0;
    
    for ($i = 0; $i < $lobbycount; $i++){
        $row = $result->fetch_array(MYSQLI_NUM);
        $lobbyid = $row[0];

        $new_time_stamps[$lobbyid] = $row[1];
        
        if(!array_key_exists($lobbyid, $old_time_stamps)){
            $updated_lobbies[] = $lobbyid;
            continue;
        } 
        
        if($new_time_stamps[$lobbyid] > $old_time_stamps[$lobbyid]){
            $updated_lobbies[] = $lobbyid;
        }

        unset($old_time_stamps[$lobbyid]);
    }

    while(!empty($old_time_stamps)){
        $updated_lobbies[] = array_key_last($old_time_stamps);
        array_pop($old_time_stamps);
    }


    if (count($updated_lobbies) > 1){
        $content = '{"connection":"open", "updatedlobbies":[';
        $updated_lobby_count = count($updated_lobbies);
        
        for($i = 0; $i < $updated_lobby_count; $i++){
            $id = array_pop($updated_lobbies);
			$content .= $id;
			if ($i != $updated_lobby_count - 1) {$content .= ', '; }
        }
        
        $content .= ']}';
        echo "\r\nMessage = " . $content;
        $last_io_message = 3;
        
        $x = 0;
        $client_count = count($clients);
        echo "\r\nMessage sent to clients: ";
        for ($i = 0; $i < $client_count; $i++){
            $response = chr(129) . chr(strlen($content)) . $content;
            if (socket_write($clients[$i], $response) != false){
                echo $x , ", ";
            } else {
                echo "\r\nClosing client " . $x;
                socket_shutdown($clients[$i]);
                socket_close($clients[$i]);
                array_splice($clients, $i, 1);
                echo "\r\nMessage sent to clients: ";
                $i--;
                $client_count--;
            }
            $x++;
        }
    }
}

$content = '{"connection":"closed"}';
$response = chr(129) . chr(strlen($content)) . $content;
for ($i = 0; $i < count($clients); $i++){
    socket_write($clients[$i], $response);
}

socket_close($server);
?>