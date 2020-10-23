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
    $sql = "UPDATE streamers SET isAdmin = 1 where user = '".$user."'";
            
    $insert_row = sqlDAL::writeSql($sql);
    if($insert_row){
        echo "Your user {$userName} is admin now";
        echo "\n";
        die();
    }
}
echo "Bye";
echo "\n";
die();




