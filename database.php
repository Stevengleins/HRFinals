<?php
$HOST = 'localhost';
$USERNAME = 'root';
$PASSWORD = '';
$DBNAME = 'workforcedb';

$mysql = new mysqli($HOST, $USERNAME, $PASSWORD, $DBNAME);
if ($mysql->connect_error) {
    die("Connection failed: " . $mysql->connect_error);
}
?>