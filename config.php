<?php
/**
 * Munkalap App - Konfigurációs fájl
 */

// Adatbázis konfiguráció
define('DB_HOST', 'localhost');
define('DB_NAME', 'munkalap_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session indítás
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Alapvető helper függvények

/**
 * Ellenőrzi, hogy a felhasználó be van-e jelentkezve
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Átirányít egy oldalra
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * XSS védelem - HTML karakterek escape-elése
 */
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Flash üzenet beállítása
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Flash üzenet lekérése és törlése
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Felhasználó adatok lekérése session-ből
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'email' => $_SESSION['email'] ?? null
        ];
    }
    return null;
}
?>


