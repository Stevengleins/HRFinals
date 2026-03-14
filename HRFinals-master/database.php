<?php
$HOST = 'localhost';
$USERNAME = 'root';
$PASSWORD = '';
$DBNAME = 'workforcedb';

// Tell mysqli to throw exceptions instead of silent warnings (Best Practice)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysql = new mysqli($HOST, $USERNAME, $PASSWORD, $DBNAME);
    
    // Set the character set to handle all modern characters safely
    $mysql->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // In a real system, you would log $e->getMessage() to a hidden file here
    
    // Show a safe, generic message to the user so no system details are leaked
    die("The system is currently undergoing maintenance. Please check back later.");
}
?>