<?php
/**
 * Session Check Middleware
 * Wstaw ten plik na początku każdej chronionej strony (main.html, admin.php, etc.)
 *
 * Funkcje:
 * - Sprawdza czy użytkownik ma aktywną sesję w Redis
 * - Zapisuje obecny URL jako ostatni odwiedzony
 * - Przekierowuje do logowania jeśli sesja wygasła
 * - Przedłuża sesję przy każdej aktywności
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    // Konfiguracja cookie sesyjnego
    session_set_cookie_params([
        'lifetime' => 3600,        // 1 godzina
        'path' => '/',
        'domain' => '',            // Obecna domena
        'secure' => false,         // HTTP OK (dla local dev)
        'httponly' => true,        // Blokada JavaScript
        'samesite' => 'Lax'        // CSRF protection (Lax pozwala na POST z tej samej domeny)
    ]);

    session_start();

    // Debug: loguj session ID dla diagnostyki
    if (isset($_POST) && !empty($_POST)) {
        error_log("POST request to " . $_SERVER['REQUEST_URI'] . " with session ID: " . session_id());
    }
}

require_once __DIR__ . '/session_helper.php';

$sessionHelper = new SessionHelper();

// Pobierz obecny pełny URL
$currentUrl = $_SERVER['REQUEST_URI'];

// Sprawdź czy użytkownik ma aktywną sesję w Redis
$userData = $sessionHelper->getUserSession();

if ($userData === null) {
    // Sesja wygasła lub nie istnieje
    // Wyczyść PHP session
    session_unset();
    session_destroy();

    // Przekieruj do logowania z parametrem "return_to"
    $loginUrl = '/scripts/login.php?return_to=' . urlencode($currentUrl);
    header("Location: " . $loginUrl);
    exit;
}

// Sesja aktywna - aktualizuj dane
// Zapisz obecny email, user_id i rolę do PHP session (dla kompatybilności)
$_SESSION['user_email'] = $userData['email'];
$_SESSION['user_id'] = $userData['user_id'] ?? null;
$_SESSION['is_admin'] = $userData['is_admin'];

// Aktualizuj ostatni URL i przedłuż sesję
$sessionHelper->updateLastUrl($currentUrl);

// Opcjonalnie: ustaw zmienne globalne dla łatwiejszego dostępu
$GLOBALS['current_user_email'] = $userData['email'];
$GLOBALS['current_user_id'] = $userData['user_id'] ?? null;
$GLOBALS['current_user_is_admin'] = $userData['is_admin'];
$GLOBALS['session_login_time'] = $userData['login_time'];
$GLOBALS['session_last_activity'] = $userData['last_activity'];
