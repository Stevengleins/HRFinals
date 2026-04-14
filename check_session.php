<?php
session_start();

// 1. Force the browser to NEVER cache this API response
header('Content-Type: application/json');
header("Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// 2. Check if the user has a valid role. If not, their session is dead.
if (!isset($_SESSION['role'])) {
    echo json_encode(['status' => 'logged_out']);
} else {
    echo json_encode(['status' => 'logged_in']);
}
?>