<?php
session_start();

// 1. Clear all session variables from memory
$_SESSION = array();

// 2. Destroy the session cookie inside the user's browser (This stops the "Forward" button bug)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session file on the server
session_destroy();

// 4. Clear the cache so the browser doesn't try to remember the redirect
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 5. Kick the user back to the login page
header("Location: index.php");
exit();
?>