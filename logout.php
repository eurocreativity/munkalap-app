<?php
require_once 'config.php';

// Session megsemmisítése
$_SESSION = array();

// Session cookie törlése
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Session megsemmisítése
session_destroy();

// Átirányítás bejelentkezési oldalra
setFlashMessage('info', 'Sikeresen kijelentkeztél.');
redirect('login.php');
exit();
?>


