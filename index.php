<?php
require_once 'config.php';

// Ha be van jelentkezve, irányítsuk a dashboard-ra, különben a login-ra
if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
exit();
?>

