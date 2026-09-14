<?php

$counter = 0;
$sleep = 5;
$timeout = 60;
$connected = false;
$db_host = getenv('DB_MYSQL_HOST');
$db_port = getenv('DB_MYSQL_PORT');
$db_name = getenv('DB_MYSQL_NAME');
$db_user = getenv('DB_MYSQL_USER');
$db_pass = getenv('DB_MYSQL_PASSWORD');

while (!$connected) {
    echo "Checking database connection....";
    $connect_errno = 0;
    $connect_error = '';
    try {
        $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
        $connect_errno = $mysqli->connect_errno;
        $connect_error = $mysqli->connect_error;
    } catch (mysqli_sql_exception $e) {
        $connect_errno = $e->getCode();
        $connect_error = $e->getMessage();
    }
    if (empty($connect_errno)) {
        echo "OK\n";
        $connected = true;
    } else {
        $counter ++;
        echo 'Failed (attempt ' . $counter . ")\n";
        if ($counter * $sleep > $timeout) {
            echo 'Giving up... (' . $connect_errno . ') ' . $connect_error;
            exit(1);
        }

        sleep($sleep);
    }
}

exit(0);
