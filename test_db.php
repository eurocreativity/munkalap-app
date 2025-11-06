<?php
// MySQL kapcsolat tesztelése
$host = 'localhost';
$username = 'root';
$password = ''; // XAMPP-ban alapértelmezetten üres

echo "<h2>MySQL Kapcsolat Teszt</h2>";

try {
    // Először próbáljuk a munkalap_db adatbázist
    $dbname = 'munkalap_db';
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color: green;'>✓ Sikeres kapcsolat a '$dbname' adatbázishoz!</p>";
    } catch (PDOException $e) {
        // Ha nincs még létrehozva, akkor a test adatbázist használjuk
        $dbname = 'test';
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color: orange;'>⚠ Csatlakozás a '$dbname' adatbázishoz (a 'munkalap_db' még nincs létrehozva)</p>";
        echo "<p><em>Futtasd le az install.php fájlt a telepítéshez!</em></p>";
    }
    
    // MySQL verzió lekérése
    $mysql_version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "<p><strong>MySQL verzió:</strong> $mysql_version</p>";
    
    // Ha a munkalap_db-ben vagyunk, listázzuk a táblákat
    if ($dbname === 'munkalap_db') {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (count($tables) > 0) {
            echo "<p><strong>Táblák az adatbázisban:</strong> " . implode(', ', $tables) . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Az adatbázis üres. Futtasd le az install.php fájlt!</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Nem sikerült kapcsolódni a MySQL-hez!</p>";
    echo "<p><strong>Hibaüzenet:</strong> " . $e->getMessage() . "</p>";
}

// PHP verzió kiírása
echo "<p><strong>PHP verzió:</strong> " . phpversion() . "</p>";
?>

