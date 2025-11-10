<?php
/**
 * CSRF Token Funkciók Teszt
 *
 * Ez a fájl teszteli a config.php-ban implementált CSRF token funkciókat
 */

require_once 'config.php';

echo "<h1>CSRF Token Funkciók Teszt</h1>";
echo "<hr>";

// ============================================
// 1. Token generálás teszt
// ============================================
echo "<h2>1. Token Generálás Teszt</h2>";

try {
    $token1 = generateCsrfToken();
    echo "<p><strong>✓ Token sikeresen generálva:</strong></p>";
    echo "<p><code>" . escape($token1) . "</code></p>";
    echo "<p><strong>Token hossza:</strong> " . strlen($token1) . " karakter (64 várható)</p>";

    // Ellenőrizzük hogy ugyanazt adja vissza
    $token2 = generateCsrfToken();
    if ($token1 === $token2) {
        echo "<p><strong>✓ Token konzisztens:</strong> Ugyanazt a tokent adja vissza újrahívásnál</p>";
    } else {
        echo "<p><strong>✗ HIBA:</strong> Különböző tokent adott vissza!</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>✗ HIBA:</strong> " . escape($e->getMessage()) . "</p>";
}

echo "<hr>";

// ============================================
// 2. getCsrfToken() alias teszt
// ============================================
echo "<h2>2. getCsrfToken() Alias Teszt</h2>";

try {
    $token3 = getCsrfToken();
    echo "<p><strong>✓ getCsrfToken() működik:</strong></p>";
    echo "<p><code>" . escape($token3) . "</code></p>";

    if ($token1 === $token3) {
        echo "<p><strong>✓ Alias helyesen működik:</strong> Ugyanazt a tokent adja vissza</p>";
    } else {
        echo "<p><strong>✗ HIBA:</strong> Az alias különböző tokent adott vissza!</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>✗ HIBA:</strong> " . escape($e->getMessage()) . "</p>";
}

echo "<hr>";

// ============================================
// 3. Token validálás teszt - helyes token
// ============================================
echo "<h2>3. Token Validálás Teszt - Helyes Token</h2>";

$currentToken = getCsrfToken();
$isValid = validateCsrfToken($currentToken);

if ($isValid) {
    echo "<p><strong>✓ Validálás sikeres:</strong> A helyes token elfogadásra került</p>";
} else {
    echo "<p><strong>✗ HIBA:</strong> A helyes tokent elutasította!</p>";
}

echo "<hr>";

// ============================================
// 4. Token validálás teszt - helytelen token
// ============================================
echo "<h2>4. Token Validálás Teszt - Helytelen Token</h2>";

$fakeToken = bin2hex(random_bytes(32));
$isValid = validateCsrfToken($fakeToken);

if (!$isValid) {
    echo "<p><strong>✓ Validálás sikeres:</strong> A hamis token elutasításra került</p>";
    echo "<p><strong>Hamis token:</strong> <code>" . escape($fakeToken) . "</code></p>";
} else {
    echo "<p><strong>✗ HIBA:</strong> A hamis tokent elfogadta!</p>";
}

echo "<hr>";

// ============================================
// 5. Token validálás teszt - üres token
// ============================================
echo "<h2>5. Token Validálás Teszt - Üres Token</h2>";

$isValid = validateCsrfToken('');

if (!$isValid) {
    echo "<p><strong>✓ Validálás sikeres:</strong> Az üres token elutasításra került</p>";
} else {
    echo "<p><strong>✗ HIBA:</strong> Az üres tokent elfogadta!</p>";
}

echo "<hr>";

// ============================================
// 6. Token validálás teszt - módosított token
// ============================================
echo "<h2>6. Token Validálás Teszt - Módosított Token</h2>";

$modifiedToken = substr($currentToken, 0, -1) . 'X'; // Utolsó karakter megváltoztatása
$isValid = validateCsrfToken($modifiedToken);

if (!$isValid) {
    echo "<p><strong>✓ Validálás sikeres:</strong> A módosított token elutasításra került</p>";
    echo "<p><strong>Eredeti:</strong> <code>" . escape($currentToken) . "</code></p>";
    echo "<p><strong>Módosított:</strong> <code>" . escape($modifiedToken) . "</code></p>";
} else {
    echo "<p><strong>✗ HIBA:</strong> A módosított tokent elfogadta!</p>";
}

echo "<hr>";

// ============================================
// 7. Session tartalom megjelenítése
// ============================================
echo "<h2>7. Session Tartalom</h2>";

echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>CSRF Token a session-ben:</strong></p>";
echo "<pre>";
var_dump($_SESSION['csrf_token'] ?? 'Nincs beállítva');
echo "</pre>";

echo "<hr>";

// ============================================
// 8. Használati példa HTML formban
// ============================================
echo "<h2>8. Használati Példa HTML Formban</h2>";

echo '<p>Így kell használni a CSRF tokent egy HTML formban:</p>';
echo '<pre>' . escape('
<form method="POST" action="process.php">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

    <label for="name">Név:</label>
    <input type="text" id="name" name="name" required>

    <button type="submit">Küldés</button>
</form>
') . '</pre>';

echo '<p>És a PHP oldalon így kell validálni:</p>';
echo '<pre>' . escape('
<?php
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF token validálás
    if (!validateCsrfToken($_POST["csrf_token"] ?? "")) {
        die("Érvénytelen CSRF token!");
    }

    // Itt jöhet a biztonságos adatfeldolgozás
    // ...
}
?>
') . '</pre>';

echo "<hr>";
echo "<h2>✓ Teszt Befejezve</h2>";
echo '<p><a href="index.php">Vissza a főoldalra</a></p>';

?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 20px auto;
        padding: 20px;
        background-color: #f5f5f5;
    }
    h1 {
        color: #333;
        border-bottom: 3px solid #4CAF50;
        padding-bottom: 10px;
    }
    h2 {
        color: #666;
        margin-top: 20px;
    }
    code {
        background-color: #fff;
        padding: 2px 6px;
        border: 1px solid #ddd;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
        word-break: break-all;
    }
    pre {
        background-color: #fff;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow-x: auto;
    }
    hr {
        border: none;
        border-top: 1px solid #ddd;
        margin: 30px 0;
    }
    p {
        line-height: 1.6;
    }
</style>
