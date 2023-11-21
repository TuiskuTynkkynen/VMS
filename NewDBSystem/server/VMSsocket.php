<?php
//php -q C:\wamp64\www\php_test/socket_test.php

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

$dbname = "cardgame";

$mysqli = new mysqli($servername, $dbusername, $dbpassword, $dbname);

$now = time();
$y = $now - 15*60;
$sql = "DELETE FROM sessions WHERE last_seen < $y";
if ($mysqli->query($sql) === FALSE) { echo "Error updating record: " . $mysqlir->error; }

$isactive = 0;
$sql = "UPDATE gamestates SET isactive = $isactive";
if ($mysqli->query($sql) === FALSE) { echo "Error: " . $sql . "<br>" . $mysqli->error; }

$sql = "SELECT isactive, lastupdated FROM gamestates";
$old_time_stamp = $mysqli->query($sql)->fetch_array(MYSQLI_NUM)[1];

echo "VMS ws opened\n";
// Send messages into WebSocket in a loop.
while (true) {
    
    if(($newc = socket_accept($server)) !== false)
    {
        echo "New client has connected\n";
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
    
        $content = '"connection":"establised", "isactive":"' . $isactive . '"';
        $response = chr(129) . chr(strlen($content)) . $content;
        socket_write($newc, $response);
    }
    
    sleep(1);
    $isactive = $mysqli->query($sql)->fetch_array(MYSQLI_NUM)[0];
    $new_time_stamp = $mysqli->query($sql)->fetch_array(MYSQLI_NUM)[1];
    if ($new_time_stamp > $old_time_stamp){
        $x = 0;
        for ($i = 0; $i < count($clients); $i++){
            $content = '"timestamp":"' . $new_time_stamp . '", "isactive":"' . $isactive . '"';
            echo $content;
            $response = chr(129) . chr(strlen($content)) . $content;
            if (socket_write($clients[$i], $response)){
            echo "Message sent to client " . $i ,"\r\n";
            } else {
                echo "closing client " . $x ,"\r\n";
                socket_shutdown($clients[$i]);
                socket_close($clients[$i]);
                array_splice($clients, $i, 1);
                echo 'Client disconnected!',"\r\n";
                $i--;
            }
            $x++;
        }
        $old_time_stamp = $new_time_stamp;
    }
}

$content = '"connection":"closed"';
$response = chr(129) . chr(strlen($content)) . $content;
for ($i = 0; $i < count($clients); $i++){
    socket_write($clients[$i], $response);
}

socket_close($server);
?>