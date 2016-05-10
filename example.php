<?php
    include "ring.php";
    
    $username = 'YOUR EMAIL ADDRESS HERE';
    $password = 'YOUR RING.COM PASSWORD HERE';
    
    include "../password.php";
    
    $bell = new Ring();
    print "Authenticating...\n";
    $bell->authenticate($username, $password);

    print "My devices:\n";
    var_dump($bell->devices());

    print "Start polling for motion or dings...\n";
    while(1) {
        $states = $bell->poll();
        if ($states) {
            foreach($states as $state) {
                if ($state['is_ding']) {
                    print "Somebody pushed the button!\n";
                }
                
                if ($state['is_motion']) {
                    print "There's motion in the ocean!\n";
                }
            }
        }
        sleep(5);
    }
