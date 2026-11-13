<?php
/**
 * TREND & FASHION BOUTIQUE SHOP - SESSION DESTRUCTION SCRIPT
 * Clears authentication session and safely redirects back to index.php
 */
require_once __DIR__ . '/db.php';

// Preserve existing cart if desired, or clear user authentication credentials
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);
unset($_SESSION['user_role']);

// Destroy session cookies if set
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
