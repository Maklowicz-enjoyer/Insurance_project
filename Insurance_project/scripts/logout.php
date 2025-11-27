<?php
/**
 * Logout Script
 * Wylogowuje użytkownika przez usunięcie sesji z Redis i PHP
 */

session_start();

require_once __DIR__ . '/session_helper.php';

$sessionHelper = new SessionHelper();

// Usuń rozszerzone dane sesji z Redis
$sessionHelper->destroyUserSession();

// Wyczyść PHP session
$_SESSION = [];

// Usuń session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Zniszcz sesję PHP
session_destroy();

// Przekieruj do strony głównej
header("Location: ../index.html");
exit;
