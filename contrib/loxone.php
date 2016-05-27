<?php
/*
 * This script sends an UDP trigger to for example a Loxone controller.
 * 
 * Copy ring.php into the same directory as this file.
 */

include "ring.php";

/*
 * Config
 */
$ringUser     = '';
$ringPassword = '';

$udpPort    = 9000;
$udpHost    = '192.168.0.1'
$udpCommand = 't';


/*
 * Function to send UDP packages
 */
function sendCommand($host, $port, $data) {
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

    $len = strlen($data);

    socket_sendto($sock, $data, $len, 0, $host, $port);
    socket_close($sock);
}

/*
 * Main code
 */

$bell = new Ring();
print "Authenticating...";
$bell->authenticate($ringUser, $ringPassword);

while(1) {
    $states = $bell->poll();
    if ($states) {
	foreach($states as $state) {
	        var_dump($state);
	        if ($state['is_ding']) {
	            print "Somebody pushed the button!\n";
                sendCommand($udpHost, $udpPort, $udpCommand);
                sleep(30);
        	}

	        if ($state['is_motion']) {
        	    print "There's motion in the ocean!\n";
	        }
    	}
    }
    sleep(5);
}
