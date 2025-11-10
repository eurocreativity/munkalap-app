<?php
/**
 * Session Timeout Teszt
 *
 * Ez a teszt fájl ellenőrzi:
 * 1. Session security flag-ek beállítását
 * 2. Session timeout működését (csökkentett időzítéssel teszteléshez)
 * 3. last_activity frissülését
 */

require_once 'config.php';

// Teszt kimenet
echo "<h1>Session Timeout és Security Flag-ek Teszt</h1>";
echo "<hr>";

// 1. Session Configuration ellenőrzése
echo "<h2>1. Session Configuration</h2>";
echo "<ul>";
echo "<li><strong>Session GC MaxLifetime:</strong> " . ini_get('session.gc_maxlifetime') . " sec (elvárás: 3600)</li>";
echo "<li><strong>Session Cookie Lifetime:</strong> " . ini_get('session.cookie_lifetime') . " sec (elvárás: 0)</li>";
echo "<li><strong>Session Cookie HttpOnly:</strong> " . (ini_get('session.cookie_httponly') ? 'Enabled' : 'Disabled') . " (elvárás: Enabled)</li>";
echo "<li><strong>Session Cookie Secure:</strong> " . (ini_get('session.cookie_secure') ? 'Enabled' : 'Disabled') . " (localhost-on: Disabled, production-ban: Enabled)</li>";
echo "<li><strong>Session Cookie SameSite:</strong> " . ini_get('session.cookie_samesite') . " (elvárás: Strict)</li>";
echo "</ul>";

// 2. Session információk
echo "<h2>2. Current Session Information</h2>";
echo "<ul>";
echo "<li><strong>Session ID:</strong> " . session_id() . "</li>";
echo "<li><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</li>";
echo "<li><strong>Logged In:</strong> " . (isLoggedIn() ? 'Yes' : 'No') . "</li>";

if (isLoggedIn()) {
    echo "<li><strong>User ID:</strong> " . $_SESSION['user_id'] . "</li>";
    echo "<li><strong>Username:</strong> " . $_SESSION['username'] . "</li>";
    echo "<li><strong>Last Activity:</strong> " . (isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : 'Not set') . "</li>";

    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        echo "<li><strong>Time Since Last Activity:</strong> " . $elapsed . " sec</li>";
        echo "<li><strong>Time Until Timeout:</strong> " . (3600 - $elapsed) . " sec</li>";
    }
}
echo "</ul>";

// 3. Tesztelési instrukciók
echo "<h2>3. Tesztelési Útmutató</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<h3>A) Normál Timeout Teszt (1 óra)</h3>";
echo "<ol>";
echo "<li>Jelentkezz be az alkalmazásba</li>";
echo "<li>Frissítsd ezt az oldalt - látni fogod a 'Last Activity' időt</li>";
echo "<li>Várj 1 órát (vagy hagyd nyitva a böngészőt)</li>";
echo "<li>Próbálj meg egy védett oldalt meglátogatni</li>";
echo "<li>Át kell irányítson a login oldalra a 'A munkamenet lejárt...' üzenettel</li>";
echo "</ol>";

echo "<h3>B) Gyors Teszt (60 másodperc)</h3>";
echo "<p><strong>config.php módosítás:</strong> Ideiglenes teszteléshez állítsd át az auth_check.php-ben:</p>";
echo "<pre style='background: #fff; padding: 10px; border: 1px solid #ccc;'>";
echo "// Átmenetileg 60 sec-re állítva teszteléshez\n";
echo "if (isset(\$_SESSION['last_activity']) && \n";
echo "    (time() - \$_SESSION['last_activity'] > 60)) { // 60 helyett 3600\n";
echo "</pre>";
echo "<ol>";
echo "<li>Módosítsd a timeout-ot 60 sec-re</li>";
echo "<li>Jelentkezz be</li>";
echo "<li>Várj 61 másodpercet</li>";
echo "<li>Próbálj meg egy védett oldalt meglátogatni</li>";
echo "<li>Át kell irányítson a login oldalra</li>";
echo "<li>NE FELEJTSD EL visszaállítani 3600-ra!</li>";
echo "</ol>";
echo "</div>";

// 4. Security Flag-ek ellenőrzése
echo "<h2>4. Cookie Security Flags (Browser Developer Tools)</h2>";
echo "<div style='background: #fffacd; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Ellenőrzés Developer Tools-szal:</strong></p>";
echo "<ol>";
echo "<li>Nyisd meg a Developer Tools-t (F12)</li>";
echo "<li>Menj az Application/Storage > Cookies fülre</li>";
echo "<li>Keresd meg a PHPSESSID cookie-t</li>";
echo "<li>Ellenőrizd a következő flag-eket:</li>";
echo "<ul>";
echo "<li><strong>HttpOnly:</strong> ✓ (be kell legyen pipálva)</li>";
echo "<li><strong>Secure:</strong> " . (ini_get('session.cookie_secure') ? '✓' : '✗') . " (localhost-on nem, production-ban igen)</li>";
echo "<li><strong>SameSite:</strong> Strict</li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

// 5. Session Cookie információk
echo "<h2>5. Session Cookie Info</h2>";
if (isset($_COOKIE[session_name()])) {
    echo "<p><strong>Session Cookie Name:</strong> " . session_name() . "</p>";
    echo "<p><strong>Session Cookie Value:</strong> " . substr($_COOKIE[session_name()], 0, 20) . "...</p>";
} else {
    echo "<p style='color: red;'>No session cookie found!</p>";
}

echo "<hr>";
echo "<h2>Teszt Linkek</h2>";
echo "<ul>";
echo "<li><a href='login.php'>Login Page</a></li>";
echo "<li><a href='dashboard.php'>Dashboard (védett)</a></li>";
echo "<li><a href='worksheets/list.php'>Worksheets (védett)</a></li>";
echo "<li><a href='logout.php'>Logout</a></li>";
echo "<li><a href='test_session_timeout.php' style='font-weight: bold;'>Refresh This Page</a></li>";
echo "</ul>";

// Footer
echo "<hr>";
echo "<p style='color: #666; font-size: 0.9em;'>";
echo "Test file: test_session_timeout.php | ";
echo "Current time: " . date('Y-m-d H:i:s') . " | ";
echo "PHP Version: " . PHP_VERSION;
echo "</p>";
?>
