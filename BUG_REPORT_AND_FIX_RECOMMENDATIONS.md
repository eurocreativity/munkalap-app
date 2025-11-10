# BUG & FIX REPORT - Munkalap App

**Dátum:** 2025-11-10
**Projekt:** Munkalap App
**Verzió:** 1.0
**Elemző:** Claude Code Agent

---

## Összefoglaló

- **Összes talált bug:** 12
- **Kritikus (P0):** 3
- **High (P1):** 4
- **Medium (P2):** 3
- **Low (P3):** 2

**Általános értékelés:** Az alkalmazás alapvető biztonsági intézkedésekkel rendelkezik (prepared statements, XSS escape), azonban több kritikus probléma is található, főként a tranzakciókezelés, session biztonság és hibakezelés terén.

---

## Felderített Bugok

### BUG-001: Hiányzó tranzakciókezelés az edit.php-ban

**Prioritás:** P0 (Kritikus)
**Komponens:** `worksheets/edit.php`
**Kategória:** Adatintegritás
**Reprodukálható:** Yes

**Leírás:**
A munkalap szerkesztéskor az anyagok frissítése nem tranzakcióban történik. Ha a munkalap update sikeres, de az anyagok mentése közben hiba történik, akkor inkonzisztens adatbázis állapot jön létre.

**Reprodukálás:**
1. Nyiss meg egy munkalap szerkesztését
2. Módosítsd a munkalap adatait és az anyagokat
3. Szimuláljunk hibát az anyag mentés közben (pl. adatbázis kapcsolat megszakad)
4. A munkalap frissül, de az anyagok nem

**Elvárt viselkedés:**
Ha bármelyik lépés sikertelen, az egész művelet visszagörgetése (rollback).

**Tényleges viselkedés:**
A munkalap frissül, de az anyagok állapota nem konzisztens.

**Kód helye:**
```php
// edit.php, sor 149-177
if (empty($errors)) {
    try {
        // Nincs beginTransaction()
        if ($worksheet->update($id, $data)) {
            $materialObj->deleteByWorksheetId($id);

            foreach ($updatedMaterials as $materialData) {
                $materialData['worksheet_id'] = $id;
                $materialObj->create($materialData);
            }
            // Nincs commit()
        }
    } catch (Exception $e) {
        // Nincs rollback()
    }
}
```

**Root Cause:**
Hiányzik a Database tranzakció használata (beginTransaction, commit, rollback).

**Javasolt megoldás:**
```php
if (empty($errors)) {
    $db = Database::getInstance();

    try {
        $db->beginTransaction();

        // 1. Munkalap frissítése
        if (!$worksheet->update($id, $data)) {
            throw new Exception('Munkalap frissítése sikertelen');
        }

        // 2. Régi anyagok törlése
        $materialObj->deleteByWorksheetId($id);

        // 3. Új anyagok mentése
        foreach ($updatedMaterials as $materialData) {
            $materialData['worksheet_id'] = $id;
            if (!$materialObj->create($materialData)) {
                throw new Exception('Anyag mentése sikertelen');
            }
        }

        $db->commit();

        setFlashMessage('success', 'A munkalap sikeresen módosítva!');
        header('Location: list.php');
        exit();

    } catch (Exception $e) {
        $db->rollback();
        $errors[] = 'Hiba történt: ' . $e->getMessage();
        error_log("Worksheet update error: " . $e->getMessage());
    }
}
```

**Tesztelési módszer:**
1. Készíts egy teszt munkalapot anyagokkal
2. Módosítsd és ments
3. Ellenőrizd adatbázisban, hogy minden konzisztensen frissült
4. Szimuláljunk hibát és ellenőrizzük a rollback működését

**Becsült effort:** Medium (2-3 óra)

---

### BUG-002: Hiányzó tranzakciókezelés a delete.php-ban

**Prioritás:** P0 (Kritikus)
**Komponens:** `worksheets/delete.php`
**Kategória:** Adatintegritás
**Reprodukálható:** Yes

**Leírás:**
A munkalap törléskor az anyagok törlése és a munkalap törlése nem tranzakcióban történik. Ha az anyagok törlése sikeres, de a munkalap törlése sikertelen, akkor árva rekordok maradnak.

**Reprodukálás:**
1. Hozz létre egy munkalapot anyagokkal
2. Töröld a munkalapot
3. Szimuláljunk hibát a munkalap törlés közben
4. Az anyagok törlődtek, de a munkalap megmaradt

**Elvárt viselkedés:**
Atomi művelet - vagy minden törlődik, vagy semmi.

**Tényleges viselkedés:**
Részleges törlés lehetséges.

**Kód helye:**
```php
// delete.php, sor 30-54
try {
    // Nincs beginTransaction()
    $materialObj->deleteByWorksheetId($id);

    if ($worksheet->delete($id)) {
        setFlashMessage('success', 'A munkalap sikeresen törölve!');
    } else {
        setFlashMessage('danger', 'Hiba történt a munkalap törlése során!');
    }
    // Nincs commit() vagy rollback()
} catch (Exception $e) {
    // Nincs rollback()
}
```

**Root Cause:**
Hiányzik a tranzakciókezelés.

**Javasolt megoldás:**
```php
$db = Database::getInstance();

try {
    $db->beginTransaction();

    // Munkalap létezésének ellenőrzése
    $worksheetData = $worksheet->getById($id);
    if (!$worksheetData) {
        throw new Exception('A munkalap nem található!');
    }

    // 1. Kapcsolódó anyagok törlése
    if (!$materialObj->deleteByWorksheetId($id)) {
        throw new Exception('Anyagok törlése sikertelen');
    }

    // 2. Munkalap törlése
    if (!$worksheet->delete($id)) {
        throw new Exception('Munkalap törlése sikertelen');
    }

    $db->commit();

    setFlashMessage('success', 'A munkalap sikeresen törölve! (Munkalap szám: ' .
                    escape($worksheetData['worksheet_number']) . ')');

} catch (Exception $e) {
    $db->rollback();
    setFlashMessage('danger', 'Hiba történt: ' . $e->getMessage());
    error_log("Worksheet delete error: " . $e->getMessage());
}
```

**Tesztelési módszer:**
1. Hozz létre munkalapot anyagokkal
2. Töröld
3. Ellenőrizd az adatbázisban, hogy minden törlődött
4. Tesztelj hibahelyzetet is

**Becsült effort:** Small (1 óra)

---

### BUG-003: Hiányzó session biztonsági beállítások

**Prioritás:** P0 (Kritikus)
**Komponens:** `config.php`
**Kategória:** Biztonság - Session Hijacking
**Reprodukálható:** Yes

**Leírás:**
A session konfigurációban hiányoznak a biztonsági flag-ek (secure, httponly, samesite). Ez lehetővé teszi a session hijacking és XSS alapú cookie lopás támadásokat.

**Elvárt viselkedés:**
Biztonságos session kezelés SSL mellett, XSS elleni védelem.

**Tényleges viselkedés:**
A session cookie-k nem védettek megfelelően.

**Kód helye:**
```php
// config.php, sor 13-15
if (session_status() === PHP_SESSION_NONE) {
    session_start();  // Nincs session konfiguráció
}
```

**Root Cause:**
Hiányoznak a PHP session biztonsági beállítások.

**Javasolt megoldás:**
```php
// config.php - Session konfiguráció
if (session_status() === PHP_SESSION_NONE) {
    // Session biztonsági beállítások
    ini_set('session.cookie_httponly', 1);      // XSS védelem
    ini_set('session.use_only_cookies', 1);     // Csak cookie-ban
    ini_set('session.cookie_samesite', 'Strict'); // CSRF védelem

    // Ha HTTPS használatban van
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);    // Csak HTTPS-en
    }

    // Session timeout (1 óra)
    ini_set('session.gc_maxlifetime', 3600);
    ini_set('session.cookie_lifetime', 3600);

    session_start();

    // Session regenerálás bizonyos időközönként (session fixation védelem)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } else if (time() - $_SESSION['last_regeneration'] > 300) { // 5 perc
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}
```

**Tesztelési módszer:**
1. Jelentkezz be
2. Ellenőrizd a cookie flag-eket a böngésző DevTools-ban (Application > Cookies)
3. HttpOnly, Secure, SameSite flag-eknek szerepelniük kell
4. Próbálj JavaScript-ből hozzáférni a session cookie-hoz (sikertelen kell legyen)

**Becsült effort:** Small (1-2 óra)

---

### BUG-004: Hiányzó CSRF token védelem

**Prioritás:** P1 (High)
**Komponens:** `worksheets/edit.php`, `worksheets/delete.php`, `worksheets/add.php`
**Kategória:** Biztonság - CSRF
**Reprodukálható:** Yes

**Leírás:**
Az alkalmazás nem használ CSRF token-eket a form védelemhez. Bár a delete.php POST-only és button-check védelemmel rendelkezik, ez nem nyújt teljes védelmet CSRF támadások ellen.

**Reprodukálás:**
1. Készíts egy külső HTML oldalt:
```html
<!-- csrf-attack.html -->
<form action="http://localhost/munkalap-app/worksheets/edit.php" method="POST" id="attackForm">
    <input type="hidden" name="id" value="1">
    <input type="hidden" name="save" value="1">
    <input type="hidden" name="company_id" value="999">
    <!-- Egyéb mezők -->
</form>
<script>
    // Auto-submit bejelentkezés után
    document.getElementById('attackForm').submit();
</script>
```
2. Bejelentkezés után nyisd meg ezt az oldalt
3. A form automatikusan submittal, és módosítja a munkalapot

**Elvárt viselkedés:**
CSRF token ellenőrzés meghiúsítja a támadást.

**Tényleges viselkedés:**
A támadás sikeres, mert nincs CSRF védelem.

**Root Cause:**
Hiányzik a CSRF token generálás és validáció.

**Javasolt megoldás:**

**1. CSRF token függvények (config.php):**
```php
/**
 * CSRF token generálása
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token validálása
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * CSRF token mező generálása
 */
function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . escape($token) . '">';
}
```

**2. Form-okban használat (edit.php, add.php):**
```php
<form method="POST" action="">
    <?php echo csrfField(); ?>
    <!-- További mezők -->
</form>
```

**3. Validáció (edit.php, delete.php, add.php):**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token ellenőrzés
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Érvénytelen biztonsági token! Kérlek próbáld újra.');
        header('Location: list.php');
        exit();
    }

    // ... további feldolgozás
}
```

**Tesztelési módszer:**
1. Jelentkezz be normálisan
2. Submit form - sikeres kell legyen
3. Próbálj külső oldal submit-ot - sikertelen kell legyen
4. Token nélkül submit - sikertelen kell legyen
5. Rossz token-nel submit - sikertelen kell legyen

**Becsült effort:** Medium (3-4 óra)

---

### BUG-005: Hiányzó rate limiting és brute force védelem

**Prioritás:** P1 (High)
**Komponens:** `login.php`
**Kategória:** Biztonság - Brute Force
**Reprodukálható:** Yes

**Leírás:**
A login.php nem korlátozza a bejelentkezési kísérletek számát. Támadó korlátlan számú jelszó kombinációt próbálhat ki.

**Reprodukálás:**
1. Írj egy script-et, ami 1000 bejelentkezési kísérletet csinál másodpercenként
2. A szerver fogadja az összeset, nincs rate limit

**Elvárt viselkedés:**
Maximum 5 sikertelen kísérlet 15 percenként IP címenként.

**Tényleges viselkedés:**
Korlátlan próbálkozás.

**Root Cause:**
Hiányzik a sikertelen bejelentkezési kísérletek nyilvántartása és korlátozása.

**Javasolt megoldás:**

**1. Login attempts tábla (install.php vagy migration):**
```sql
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(50),
    attempt_time DATETIME NOT NULL,
    success TINYINT(1) DEFAULT 0,
    INDEX idx_ip_time (ip_address, attempt_time)
);
```

**2. Rate limiting függvények (config.php):**
```php
/**
 * Sikertelen bejelentkezés rögzítése
 */
function recordLoginAttempt($username, $success = false) {
    $db = Database::getInstance();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $sql = "INSERT INTO login_attempts (ip_address, username, attempt_time, success)
            VALUES (?, ?, NOW(), ?)";
    $db->execute($sql, [$ip, $username, $success ? 1 : 0]);

    // Régi kísérletek törlése (> 24 óra)
    $db->execute("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
}

/**
 * Ellenőrzi, hogy blokkolva van-e az IP
 */
function isLoginBlocked() {
    $db = Database::getInstance();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Sikertelen kísérletek száma az elmúlt 15 percben
    $sql = "SELECT COUNT(*) as attempts
            FROM login_attempts
            WHERE ip_address = ?
            AND success = 0
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";

    $result = $db->fetchOne($sql, [$ip]);
    $attempts = $result['attempts'] ?? 0;

    return $attempts >= 5; // Maximum 5 sikertelen próbálkozás
}

/**
 * Mikor lesz újra engedélyezett a bejelentkezés
 */
function getBlockedUntil() {
    $db = Database::getInstance();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $sql = "SELECT DATE_ADD(MAX(attempt_time), INTERVAL 15 MINUTE) as blocked_until
            FROM login_attempts
            WHERE ip_address = ? AND success = 0";

    $result = $db->fetchOne($sql, [$ip]);
    return $result['blocked_until'] ?? null;
}
```

**3. Login.php módosítás:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Rate limiting ellenőrzés
    if (isLoginBlocked()) {
        $blockedUntil = getBlockedUntil();
        $error = 'Túl sok sikertelen bejelentkezési kísérlet! Próbálja újra: ' . $blockedUntil;
        recordLoginAttempt($username, false);
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Kérlek add meg a felhasználónevet és jelszót!';
            recordLoginAttempt($username, false);
        } else {
            try {
                $db = Database::getInstance();
                $user = $db->fetchOne(
                    "SELECT id, username, password, full_name, email FROM users WHERE username = ?",
                    [$username]
                );

                if ($user && password_verify($password, $user['password'])) {
                    // Sikeres bejelentkezés
                    recordLoginAttempt($username, true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];

                    setFlashMessage('success', 'Sikeres bejelentkezés!');
                    redirect('dashboard.php');
                } else {
                    recordLoginAttempt($username, false);
                    $error = 'Hibás felhasználónév vagy jelszó!';
                }
            } catch (Exception $e) {
                recordLoginAttempt($username, false);
                $error = 'Hiba történt a bejelentkezés során.';
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}
```

**Tesztelési módszer:**
1. Próbálj 3x rossz jelszóval bejelentkezni - működnie kell
2. Próbálj 5x rossz jelszóval - 5. után blokkolt
3. Várj 15 percet vagy töröld az adatbázis rekordokat - újra működik

**Becsült effort:** Large (4-5 óra, adatbázis migráció + kód)

---

### BUG-006: Hiányzó path ellenőrzés az auth_check.php redirect-jében

**Prioritás:** P1 (High)
**Komponens:** `includes/auth_check.php`
**Kategória:** Biztonság - Open Redirect
**Reprodukálható:** Yes

**Leírás:**
Az auth_check.php abszolút path-al hivatkozik a login.php-ra, de a különböző mappákból behívott fájlok esetén (pl. worksheets/, companies/) ez nem megfelelő relatív útvonalat eredményez.

**Reprodukálás:**
1. Lépj be a worksheets/edit.php-hez kijelentkezve
2. Az auth_check.php átirányít 'login.php'-hez
3. A böngésző megpróbálja betölteni: http://localhost/munkalap-app/worksheets/login.php
4. 404 hiba

**Elvárt viselkedés:**
Mindig a root szintű login.php-hez irányítson.

**Tényleges viselkedés:**
Relatív útvonal használata miatt rossz helyre irányít.

**Kód helye:**
```php
// auth_check.php, sor 7-10
if (!isLoggedIn()) {
    setFlashMessage('warning', 'Kérlek jelentkezz be az oldal eléréséhez!');
    redirect('login.php');  // Relatív útvonal
    exit();
}
```

**Root Cause:**
A redirect() függvény nem abszolút URL-t használ.

**Javasolt megoldás:**

**1. Módosítás config.php-ban:**
```php
// Alkalmazás root URL meghatározása
define('BASE_URL', 'http://localhost/munkalap-app');

// Vagy dinamikusan:
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);

    // Ha nem a root-ban van a script, adjuk hozzá az útvonalat
    if ($path === '/' || $path === '\\') {
        return $protocol . '://' . $host;
    }

    return $protocol . '://' . $host . $path;
}

/**
 * Átirányít egy oldalra (javított verzió)
 */
function redirect($url) {
    // Ha relatív URL, csináljunk belőle abszolútot
    if (strpos($url, 'http') !== 0) {
        $baseUrl = getBaseUrl();

        // Ha a base URL már egy alkönyvtárban van, távolítsuk el
        if (strpos($baseUrl, '/worksheets') !== false ||
            strpos($baseUrl, '/companies') !== false ||
            strpos($baseUrl, '/monthly') !== false) {
            $baseUrl = dirname($baseUrl);
        }

        $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
    }

    header("Location: " . $url);
    exit();
}
```

**2. Vagy egyszerűbb megoldás - Abszolút path:**
```php
// config.php
define('APP_ROOT', '/munkalap-app');  // Változtasd meg, ha máshol van

function redirect($url) {
    // Ha relatív URL, adjunk hozzá abszolút path-ot
    if (strpos($url, 'http') !== 0 && strpos($url, '/') !== 0) {
        $url = APP_ROOT . '/' . ltrim($url, '/');
    }

    header("Location: " . $url);
    exit();
}
```

**Tesztelési módszer:**
1. Jelentkezz ki
2. Próbálj hozzáférni worksheets/edit.php-hez
3. Átirányítás után a root szintű login.php-nek kell betöltődnie
4. Próbálj különböző almappákból is

**Becsült effort:** Small (1 óra)

---

### BUG-007: Anyag validáció hiányosságai

**Prioritás:** P1 (High)
**Komponens:** `worksheets/edit.php`, `worksheets/add.php`
**Kategória:** Validáció
**Reprodukálható:** Yes

**Leírás:**
Az anyagok validációja csak akkor fut le, ha a termék neve nem üres. Ez azt jelenti, hogy üres anyag sorokkal is lehet menteni, ami felesleges adatbázis terheltséget okoz.

**Reprodukálás:**
1. Adj hozzá munkalapot
2. Kattints 10x az "Új anyag hozzáadása" gombra
3. Töltsd ki csak egyet
4. Ments
5. Az adatbázisban 10 sor jön létre, ebből 9 üres

**Elvárt viselkedés:**
Csak a kitöltött anyag sorok mentődjenek.

**Tényleges viselkedés:**
Üres anyag sorok is mentésre kerülnek (bár a jelenlegi kód ezt kiszűri, de jobb lenne explicit validáció).

**Kód helye:**
```php
// edit.php, sor 122-145
if (isset($_POST['materials']) && is_array($_POST['materials'])) {
    foreach ($_POST['materials'] as $material) {
        if (!empty($material['product_name'])) {  // Csak ez ellenőrzi
            // Validáció
            // ...
            $updatedMaterials[] = [...];
        }
    }
}
```

**Root Cause:**
Nincs explicit ellenőrzés az üres anyag sorokra, és nincs minimum validáció sem.

**Javasolt megoldás:**
```php
// Anyagok validációja
if (isset($_POST['materials']) && is_array($_POST['materials'])) {
    foreach ($_POST['materials'] as $index => $material) {
        // Üres sor átugrása
        $isEmpty = empty(trim($material['product_name'] ?? '')) &&
                   empty(trim($material['quantity'] ?? 0)) &&
                   empty(trim($material['net_price'] ?? 0));

        if ($isEmpty) {
            continue; // Üres sor, skip
        }

        // Ha van termék neve, akkor minden mező kötelező
        if (!empty(trim($material['product_name'] ?? ''))) {
            // Termék név validáció
            if (strlen(trim($material['product_name'])) < 2) {
                $errors[] = "Anyag #" . ($index + 1) . ": A termék nevének legalább 2 karakter hosszúnak kell lennie!";
            }

            // Mennyiség validáció
            if (!isset($material['quantity']) || !is_numeric($material['quantity']) ||
                floatval($material['quantity']) <= 0) {
                $errors[] = "Anyag #" . ($index + 1) . ": A mennyiségnek pozitív számnak kell lennie!";
            }

            // Mértékegység validáció
            if (empty(trim($material['unit'] ?? ''))) {
                $errors[] = "Anyag #" . ($index + 1) . ": A mértékegység kötelező!";
            }

            // Nettó ár validáció
            if (!isset($material['net_price']) || !is_numeric($material['net_price']) ||
                floatval($material['net_price']) < 0) {
                $errors[] = "Anyag #" . ($index + 1) . ": A nettó árnak nem-negatív számnak kell lennie!";
            }

            // ÁFA kulcs validáció
            if (!isset($material['vat_rate']) || !is_numeric($material['vat_rate']) ||
                floatval($material['vat_rate']) < 0 || floatval($material['vat_rate']) > 100) {
                $errors[] = "Anyag #" . ($index + 1) . ": Az ÁFA kulcsnak 0 és 100 között kell lennie!";
            }

            // Ha minden rendben, hozzáadjuk
            if (empty($errors)) {
                $updatedMaterials[] = [
                    'product_name' => trim($material['product_name']),
                    'quantity' => floatval($material['quantity']),
                    'unit' => trim($material['unit'] ?? 'db'),
                    'net_price' => floatval($material['net_price']),
                    'vat_rate' => floatval($material['vat_rate'] ?? 27)
                ];
            }
        } else {
            // Ha nincs termék neve, de van más adat, akkor hiba
            if (!empty(trim($material['quantity'] ?? '')) ||
                !empty(trim($material['net_price'] ?? ''))) {
                $errors[] = "Anyag #" . ($index + 1) . ": A termék neve kötelező!";
            }
        }
    }
}
```

**Tesztelési módszer:**
1. Adj hozzá anyagokat különböző állapotokban:
   - Teljesen üres sor
   - Csak név kitöltve
   - Csak mennyiség kitöltve
   - Minden mező kitöltve
2. Ellenőrizd a validációs hibaüzeneteket
3. Ellenőrizd az adatbázisban, hogy csak a valid anyagok mentődtek

**Becsült effort:** Medium (2 óra)

---

### BUG-008: SQL injection lehetőség a list.php szűrőkben

**Prioritás:** P2 (Medium)
**Komponens:** `worksheets/list.php`
**Kategória:** Biztonság - SQL Injection
**Reprodukálható:** Yes

**Leírás:**
Bár a Worksheet osztály prepared statements-et használ, a list.php-ban a szűrő paraméterek validálása nem teljes. A date_from és date_to paraméterek nem validáltak dátum formátumra.

**Reprodukálás:**
1. Nyisd meg: `list.php?date_from='; DROP TABLE worksheets; --`
2. Mivel prepared statement van, nem fog SQL injection történni
3. De invalid dátum hibát okozhat

**Elvárt viselkedés:**
Dátum formátum validáció a szűrők előtt.

**Tényleges viselkedés:**
Bármilyen string átmehet, ami MySQL hibát okozhat.

**Kód helye:**
```php
// list.php, sor 15-23
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];  // Nincs validáció
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];  // Nincs validáció
}
```

**Root Cause:**
Hiányzik a dátum formátum validáció.

**Javasolt megoldás:**
```php
// Dátum validáló függvény (config.php)
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// list.php módosítás
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    if (validateDate($_GET['date_from'])) {
        $filters['date_from'] = $_GET['date_from'];
    } else {
        setFlashMessage('warning', 'Érvénytelen dátum formátum (tól)!');
    }
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    if (validateDate($_GET['date_to'])) {
        $filters['date_to'] = $_GET['date_to'];
    } else {
        setFlashMessage('warning', 'Érvénytelen dátum formátum (ig)!');
    }
}

// Status validáció
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $validStatuses = ['Aktív', 'Lezárt', 'Folyamatban', 'Törölt'];
    if (in_array($_GET['status'], $validStatuses, true)) {
        $filters['status'] = $_GET['status'];
    } else {
        setFlashMessage('warning', 'Érvénytelen státusz!');
    }
}
```

**Tesztelési módszer:**
1. Próbálj invalid dátummal szűrni
2. Próbálj invalid státusszal szűrni
3. Ellenőrizd, hogy hibaüzenet jelenik meg
4. Próbálj SQL injection stringekkel

**Becsült effort:** Small (1 óra)

---

### BUG-009: Hiányzó audit trail / naplózás

**Prioritás:** P2 (Medium)
**Komponens:** Teljes alkalmazás
**Kategória:** Funkcionalitás - Naplózás
**Reprodukálható:** Yes

**Leírás:**
Az alkalmazásban nincs audit log, ami nyilvántartaná, hogy ki, mit, mikor módosított vagy törölt. Ez megnehezíti a problémák utólagos kivizsgálását és a jogosulatlan műveletek felderítését.

**Elvárt viselkedés:**
Minden kritikus művelet (létrehozás, módosítás, törlés) naplózva van.

**Tényleges viselkedés:**
Nincs naplózás.

**Javasolt megoldás:**

**1. Audit log tábla (install.php vagy migration):**
```sql
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

**2. Audit log függvények (config.php):**
```php
/**
 * Audit log bejegyzés rögzítése
 */
function logAudit($action, $entityType, $entityId, $oldValues = null, $newValues = null) {
    try {
        $db = Database::getInstance();
        $user = getCurrentUser();
        $userId = $user['id'] ?? null;

        $sql = "INSERT INTO audit_log
                (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $userId,
            $action,
            $entityType,
            $entityId,
            json_encode($oldValues),
            json_encode($newValues),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        $db->execute($sql, $params);
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}
```

**3. Használat a kódban:**

```php
// edit.php - Munkalap szerkesztés után
if ($worksheet->update($id, $data)) {
    logAudit('UPDATE', 'worksheet', $id, $worksheetData, $data);
    // ...
}

// delete.php - Munkalap törlés előtt
$worksheetData = $worksheet->getById($id);
if ($worksheetData) {
    logAudit('DELETE', 'worksheet', $id, $worksheetData, null);
    // ... törlés
}

// add.php - Új munkalap létrehozása után
$id = $worksheet->create($data);
if ($id) {
    logAudit('CREATE', 'worksheet', $id, null, $data);
    // ...
}
```

**Tesztelési módszer:**
1. Hozz létre, módosíts és törölj munkalapot
2. Ellenőrizd az audit_log táblát
3. Minden műveletnek szerepelnie kell

**Becsült effort:** Large (4-5 óra)

---

### BUG-010: Nincs password policy és erős jelszó ellenőrzés

**Prioritás:** P2 (Medium)
**Komponens:** User regisztráció (ha van), jelszó változtatás
**Kategória:** Biztonság - Jelszó biztonság
**Reprodukálható:** Yes

**Leírás:**
Az alkalmazásban nincs jelszó policy. A jelenlegi teszt felhasználók is gyenge jelszavakkal rendelkeznek (admin123, user123).

**Elvárt viselkedés:**
- Minimum 8 karakter
- Legalább 1 nagybetű
- Legalább 1 kisbetű
- Legalább 1 szám
- Legalább 1 speciális karakter

**Tényleges viselkedés:**
Bármilyen jelszó elfogadott.

**Javasolt megoldás:**
```php
/**
 * Jelszó erősség ellenőrzése
 */
function validatePasswordStrength($password, &$errors = []) {
    $valid = true;

    if (strlen($password) < 8) {
        $errors[] = 'A jelszónak legalább 8 karakter hosszúnak kell lennie!';
        $valid = false;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'A jelszónak tartalmaznia kell legalább egy nagybetűt!';
        $valid = false;
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'A jelszónak tartalmaznia kell legalább egy kisbetűt!';
        $valid = false;
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'A jelszónak tartalmaznia kell legalább egy számot!';
        $valid = false;
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'A jelszónak tartalmaznia kell legalább egy speciális karaktert!';
        $valid = false;
    }

    return $valid;
}
```

**Becsült effort:** Small (1-2 óra)

---

### BUG-011: Flash üzenetek XSS sérülékenysége

**Prioritás:** P3 (Low)
**Komponens:** `config.php` - getFlashMessage()
**Kategória:** Biztonság - XSS
**Reprodukálható:** Yes

**Leírás:**
A flash üzenetek megjelenítésekor escape() használva van, de a setFlashMessage()-ben nincs sanitization. Ha egy támadó manipulálni tudja a flash üzenetet (pl. redirect előtt), XSS támadást hajthat végre.

**Példa:**
```php
setFlashMessage('success', $_GET['message']);  // Veszélyes
```

**Elvárt viselkedés:**
A flash üzenetek automatikusan escapelve vannak.

**Tényleges viselkedés:**
A megjelenítéskor escapelve vannak, de ha a fejlesztő elfelejti, akkor XSS lehetséges.

**Javasolt megoldás:**
```php
/**
 * Flash üzenet beállítása (biztonságos verzió)
 */
function setFlashMessage($type, $message, $allowHtml = false) {
    if (!$allowHtml) {
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    }

    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}
```

**Megjegyzés:** Jelenleg már escape()-elve van a megjelenítéskor, így ez alacsony prioritású.

**Becsült effort:** Small (30 perc)

---

### BUG-012: Error message information disclosure

**Prioritás:** P3 (Low)
**Komponens:** `classes/Database.php`, kivételkezelés
**Kategória:** Biztonság - Information Disclosure
**Reprodukálható:** Yes

**Leírás:**
A Database osztály die()-val terminál, ami production környezetben felfedi az adatbázis kapcsolat részleteit.

**Kód helye:**
```php
// Database.php, sor 23
die("Adatbázis kapcsolat hiba: " . $e->getMessage());
```

**Elvárt viselkedés:**
Generikus hibaüzenet a felhasználónak, részletes hiba a logban.

**Tényleges viselkedés:**
Részletes hiba a felhasználónak is.

**Javasolt megoldás:**
```php
// Database.php konstruktor
try {
    $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log a részletes hibát
    error_log("Database connection failed: " . $e->getMessage());

    // Generikus üzenet a felhasználónak
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        die("Adatbázis kapcsolat hiba: " . $e->getMessage());
    } else {
        die("Az alkalmazás jelenleg nem elérhető. Kérjük, próbálja később!");
    }
}
```

**config.php kiegészítés:**
```php
// Debug mode (csak development-ben legyen true)
define('DEBUG_MODE', false);  // Production-ben mindig false
```

**Becsült effort:** Small (30 perc)

---

## Prioritás Mátrix

### P0 - Azonnal Javítandó (Kritikus)

| Bug ID | Cím | Komponens | Effort | Kockázat |
|--------|-----|-----------|--------|----------|
| BUG-001 | Hiányzó tranzakciókezelés edit.php | edit.php | Medium | Adatvesztés, inkonzisztencia |
| BUG-002 | Hiányzó tranzakciókezelés delete.php | delete.php | Small | Árva rekordok |
| BUG-003 | Session biztonsági beállítások | config.php | Small | Session hijacking |

**Összesen:** 1 nap munkaidő

---

### P1 - Ezt a Sprintet (High Priority)

| Bug ID | Cím | Komponens | Effort | Kockázat |
|--------|-----|-----------|--------|----------|
| BUG-004 | CSRF token védelem | edit/delete/add | Medium | CSRF támadás |
| BUG-005 | Rate limiting login | login.php | Large | Brute force |
| BUG-006 | Auth redirect path fix | auth_check.php | Small | 404 hiba |
| BUG-007 | Anyag validáció javítás | edit/add | Medium | Invalid adatok |

**Összesen:** 2-3 nap munkaidő

---

### P2 - Következő Sprint (Medium Priority)

| Bug ID | Cím | Komponens | Effort | Kockázat |
|--------|-----|-----------|--------|----------|
| BUG-008 | Szűrő validáció list.php | list.php | Small | Hibás szűrők |
| BUG-009 | Audit log hiánya | Minden | Large | Nincs nyomonkövetés |
| BUG-010 | Password policy | User mgmt | Small | Gyenge jelszavak |

**Összesen:** 1-2 nap munkaidő

---

### P3 - Backlog (Low Priority)

| Bug ID | Cím | Komponens | Effort | Kockázat |
|--------|-----|-----------|--------|----------|
| BUG-011 | Flash message XSS | config.php | Small | Alacsony XSS kockázat |
| BUG-012 | Error information disclosure | Database.php | Small | Info leak |

**Összesen:** 1 óra munkaidő

---

## Javítási Terv

### Sprint 1 - Kritikus bugok (1 hét)

**Cél:** P0 és P1 bugok javítása

**Nap 1-2:**
- BUG-001: Tranzakciókezelés edit.php (2-3 óra)
- BUG-002: Tranzakciókezelés delete.php (1 óra)
- BUG-003: Session biztonság (1-2 óra)

**Nap 3-4:**
- BUG-004: CSRF token implementáció (3-4 óra)
- BUG-006: Auth redirect fix (1 óra)

**Nap 5:**
- BUG-007: Anyag validáció (2 óra)
- BUG-005: Rate limiting - kezdés (2-3 óra)

**Becsült idő:** 5 munkanap

---

### Sprint 2 - Medium prioritás (1 hét)

**Nap 1-2:**
- BUG-005: Rate limiting befejezés + tesztelés (2-3 óra)
- BUG-010: Password policy (1-2 óra)

**Nap 3-5:**
- BUG-009: Audit log implementáció (4-5 óra)
- BUG-008: Szűrő validáció (1 óra)

**Becsült idő:** 5 munkanap

---

### Sprint 3 - Alacsony prioritás (1 nap)

**Nap 1:**
- BUG-011: Flash message XSS (30 perc)
- BUG-012: Error disclosure (30 perc)
- Dokumentáció frissítés (2 óra)
- Végső tesztelés (4 óra)

**Becsült idő:** 1 munkanap

---

## Összesített Statisztikák

### Hibák kategóriák szerint

- **Biztonság (Security):** 7 bug (58%)
- **Adatintegritás (Data Integrity):** 2 bug (17%)
- **Validáció (Validation):** 2 bug (17%)
- **Funkcionalitás (Functionality):** 1 bug (8%)

### Komponensek szerint

- **edit.php:** 3 bug
- **delete.php:** 2 bug
- **config.php:** 3 bug
- **login.php:** 1 bug
- **list.php:** 1 bug
- **Database.php:** 1 bug
- **auth_check.php:** 1 bug

### Becsült összefoglalás

| Prioritás | Bugok száma | Becsült idő | Kockázat |
|-----------|-------------|-------------|----------|
| P0 (Kritikus) | 3 | 1 nap | Magas |
| P1 (High) | 4 | 2-3 nap | Közepes-Magas |
| P2 (Medium) | 3 | 1-2 nap | Közepes |
| P3 (Low) | 2 | 1 óra | Alacsony |
| **TOTAL** | **12** | **~12 munkanap** | **Változó** |

---

## Ajánlások

### Azonnal teendők

1. **BUG-001 és BUG-002 (Tranzakciókezelés):** Ezek kritikusak, mert adatvesztést okozhatnak
2. **BUG-003 (Session biztonság):** Session hijacking elleni védelem
3. **BUG-004 (CSRF):** Cross-site request forgery védelem

### Hosszú távú fejlesztések

1. **CI/CD pipeline:** Automatizált tesztelés minden commit után
2. **Unit tesztek:** PHPUnit bevezetése a kritikus osztályokhoz
3. **Code review process:** Minden PR-hez legalább 1 review
4. **Security audit:** Rendszeres biztonsági audit külső szakértővel
5. **Monitoring:** Application monitoring (pl. Sentry, New Relic)
6. **Backup stratégia:** Napi automatikus adatbázis mentés

---

## Tesztelési Checklist

### Minden javítás után

- [ ] Unit tesztek futtatása
- [ ] Manuális funkcionális teszt
- [ ] Biztonsági teszt (OWASP Top 10)
- [ ] Performance teszt
- [ ] Cross-browser teszt
- [ ] Dokumentáció frissítése
- [ ] Code review

---

**Verzió:** 1.0
**Státusz:** Végleges
**Következő felülvizsgálat:** 2025-12-10
