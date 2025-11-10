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

// Session timeout ellenőrzés
if (isLoggedIn()) {
    // Ha már volt last_activity és lejárt (1 óra = 3600 sec)
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > 3600)) {
        // Session lejárt - flash message ELŐBB, mielőtt destroy-oljuk
        setFlashMessage('warning', 'A munkamenet lejárt biztonsági okokból. Kérjük, jelentkezz be újra!');

        // Session megsemmisítése
        session_unset();
        session_destroy();

        // Új session indítása a flash message számára
        session_start();
        setFlashMessage('warning', 'A munkamenet lejárt biztonsági okokból. Kérjük, jelentkezz be újra!');

        redirect('login.php');
        exit();
    }

    // Frissítjük a last_activity időt
    $_SESSION['last_activity'] = time();
}
?>


