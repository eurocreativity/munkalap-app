<?php
/**
 * Munkalap App - Konfigurációs fájl
 */

// Adatbázis konfiguráció
define('DB_HOST', 'localhost');
define('DB_NAME', 'munkalap_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Session biztonsági beállítások és indítás
if (session_status() === PHP_SESSION_NONE) {
    // Session timeout: 1 óra (3600 másodperc)
    ini_set('session.gc_maxlifetime', 3600);

    // Session cookie csak böngésző bezárásig
    ini_set('session.cookie_lifetime', 0);

    // HttpOnly flag - JavaScript nem férhet hozzá (XSS védelem)
    ini_set('session.cookie_httponly', 1);

    // Secure flag - csak HTTPS-en (production-ban)
    // Development-ben (localhost) kikapcsolva, production-ban bekapcsolva
    if (!in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1'])) {
        ini_set('session.cookie_secure', 1);
    }

    // SameSite Strict - CSRF védelem
    ini_set('session.cookie_samesite', 'Strict');

    session_start();
}

// Alapvető helper függvények

// ============================================
// CSRF Token védelem
// ============================================

/**
 * CSRF token generálása
 * Ha még nem létezik token a session-ben, generál egy új 32 byte-os random tokent
 *
 * @return string A CSRF token
 * @throws Exception Ha a session nincs elindítva
 */
function generateCsrfToken() {
    // Ellenőrzés: session elindult-e
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new Exception('Session not started. CSRF token cannot be generated.');
    }

    // Ha nincs token, generálunk egyet
    if (!isset($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Fallback ha a random_bytes nem működik
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }

    return $_SESSION['csrf_token'];
}

/**
 * CSRF token validálása
 * Biztonságosan összehasonlítja a beküldött tokent a session-ben tárolt tokennel
 *
 * @param string $token A validálandó token
 * @return bool True ha a token érvényes, false egyébként
 */
function validateCsrfToken($token) {
    // Ellenőrzés: létezik-e token a session-ben
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    // Ellenőrzés: a beküldött token nem üres-e
    if (empty($token)) {
        return false;
    }

    // Timing attack biztos összehasonlítás
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF token lekérése
 * Alias a generateCsrfToken() függvényre
 *
 * @return string A CSRF token
 */
function getCsrfToken() {
    return generateCsrfToken();
}

// ============================================
// Felhasználói session kezelés
// ============================================

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


