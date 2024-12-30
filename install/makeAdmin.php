<?php
//streamer config
require_once '../videos/configuration.php';

if (!isCommandLineInterface()) {
    die('Command Line only');
}

// Check if a username is passed as a command line argument
$userName = isset($argv[1]) ? trim($argv[1]) : '';

if (empty($userName)) {
    echo "Enter the username or press enter to skip:";
    echo "\n";
    ob_flush();
    $userName = trim(readline(""));
}

if (!empty($userName)) {
    $sql = "UPDATE {$global['tablesPrefix']}streamers SET isAdmin = 1 WHERE user = '" . $userName . "'";
    echo $sql . PHP_EOL;

    $insert_row = $global['mysqli']->query($sql);

    if ($insert_row) {
        echo "User updated to admin successfully." . PHP_EOL;
    } else {
        die($sql . ' Error : (' . $global['mysqli']->errno . ') ' . $global['mysqli']->error);
    }
} else {
    echo "No username provided. Exiting." . PHP_EOL;
}

echo "Bye";
?>
