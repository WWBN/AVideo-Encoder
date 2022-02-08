<?php
if(php_sapi_name() !== 'cli'){
  return die('Command Line only');
}

$_POST["webSiteRootURL"] = getenv("SERVER_URL");
$_POST["systemRootPath"] = "/var/www/html/";

$_POST["databaseHost"] = getenv("DB_MYSQL_HOST");
$_POST["databasePort"] = getenv("DB_MYSQL_PORT");
$_POST["databaseName"] = getenv("DB_MYSQL_NAME");
$_POST["databaseUser"] = getenv("DB_MYSQL_USER");
$_POST["databasePass"] = getenv("DB_MYSQL_PASSWORD");
$_POST["createTables"] = 1;

$_POST['siteURL'] = getenv("STREAMER_URL");
$_POST['inputUser'] = getenv("STREAMER_USER");
$_POST['inputPassword'] = getenv("STREAMER_PASSWORD");
$_POST['allowedStreamers'] = getenv("STREAMER_URL");
$_POST['defaultPriority'] = getenv("STREAMER_PRIORITY");

require_once "./checkConfiguration.php";
