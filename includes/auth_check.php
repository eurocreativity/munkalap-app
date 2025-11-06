<?php
/**
 * Munkalap App - Bejelentkezés ellenőrzése
 */
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    setFlashMessage('warning', 'Kérlek jelentkezz be az oldal eléréséhez!');
    redirect('login.php');
    exit();
}
?>


