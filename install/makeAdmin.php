<?php
//streamer config
require_once '../videos/configuration.php';

if(!isCommandLineInterface()){
    return die('Command Line only');
}
echo "Enter the username or press enter to skip:";
echo "\n";
ob_flush();
$userName = trim(readline(""));

if(!empty($userName)){
    $sql = "UPDATE streamers SET isAdmin = 1 where user = '".$userName."'";
    echo $sql.PHP_EOL;         
    $insert_row = $global['mysqli']->query($sql);
            
    if ($insert_row) {
        echo "User created".PHP_EOL;
    } else {
        die($sql . ' Error : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
    }
}
echo "Bye";
echo "\n";
die();




