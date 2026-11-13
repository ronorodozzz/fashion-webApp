<?php
/**
 * FREUDIAN FASHION SHOP - SESSION DESTRUCTION SCRIPT
 * Terminates authentication session and redirects back to index.php
 */
require_once __DIR__ . '/db.php';

// Unset all session data
$_SESSION = [];

// Destroy session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header('Location: index.php?msg=logged_out');
exit;
