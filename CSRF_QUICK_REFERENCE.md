# CSRF TOKEN - GYORS REFERENCIA KÁRTYA

**Munkalap App CSRF Implementáció - Developer Útmutató**

---

## 1. TOKEN HASZNÁLATA (Template-ben)

### Kódrészlet

```php
<!-- Form-ben token beágyazása -->
<form method="POST" action="">
    <!-- CSRF Token hozzáadása -->
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

    <!-- Többi form elemek -->
    <input type="text" name="field_name" required>
    <button type="submit" name="save">Mentés</button>
</form>
```

### Copy-Paste Template

```html
<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
    <!-- TODO: Add form fields -->
    <button type="submit" name="save">Mentés</button>
</form>
```

---

## 2. TOKEN VALIDÁLÁSA (Handler-ben)

### Kódrészlet

```php
<?php
require_once 'config.php';
require_once 'includes/auth_check.php';

// POST kérés feldolgozása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {

    // 1. CSRF token validáció (OBLIGATORIKUS!)
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Érvénytelen kérés! Token hibás.');
        header('Location: list.php');
        exit();
    }

    // 2. Input adat begyűjtése
    $data = [
        'field_name' => trim($_POST['field_name'] ?? '')
    ];

    // 3. Validáció
    $errors = [];
    if (empty($data['field_name'])) {
        $errors[] = 'A mező kötelező!';
    }

    // 4. Feldolgozás
    if (empty($errors)) {
        // Database operations
        // ...
        setFlashMessage('success', 'Sikeres mentés!');
        header('Location: list.php');
        exit();
    }
}
?>
```

### Copy-Paste Template (Handler)

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // CSRF token ellenőrzés
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        setFlashMessage('danger', 'Érvénytelen kérés! Token hibás.');
        header('Location: list.php');
        exit();
    }

    // Feldolgozás...
}
?>
```

---

## 3. TÖRLÉS (DELETE) FORM

### Kódrészlet

```html
<form method="POST" action="delete.php" style="display: inline;">
    <!-- CSRF Token (obligatorikus!) -->
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

    <!-- ID rejtett input -->
    <input type="hidden" name="id" value="<?php echo $item_id; ?>">

    <!-- Delete gomb (obligatorikus!) -->
    <button type="submit" name="delete" class="btn btn-danger">
        Törlés
    </button>
</form>
```

### Copy-Paste Template (Delete Form)

```html
<form method="POST" action="delete.php" style="display: inline;">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <button type="submit" name="delete" class="btn btn-danger">Törlés</button>
</form>
```

---

## 4. DELETE HANDLER

### Kódrészlet

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';

// 1. POST metódus ellenőrzés
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('danger', 'Érvénytelen kérés!');
    header('Location: list.php');
    exit();
}

// 2. CSRF token validáció
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('danger', 'Érvénytelen törlési kérés! Token hibás.');
    header('Location: list.php');
    exit();
}

// 3. ID validáció
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('danger', 'Érvénytelen azonosító!');
    header('Location: list.php');
    exit();
}

// 4. Delete gomb ellenőrzés
if (!isset($_POST['delete'])) {
    setFlashMessage('danger', 'Érvénytelen törlési kérés!');
    header('Location: list.php');
    exit();
}

$id = intval($_POST['id']);

// Törlés feldolgozása
try {
    $obj = new ClassName();
    if ($obj->delete($id)) {
        setFlashMessage('success', 'Sikeresen törölve!');
    } else {
        setFlashMessage('danger', 'Hiba történt!');
    }
} catch (Exception $e) {
    setFlashMessage('danger', 'Hiba: ' . $e->getMessage());
}

header('Location: list.php');
exit();
?>
```

### Copy-Paste Template (Delete Handler)

```php
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('danger', 'Érvénytelen kérés!');
    header('Location: list.php');
    exit();
}

if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    setFlashMessage('danger', 'Érvénytelen törlési kérés! Token hibás.');
    header('Location: list.php');
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('danger', 'Érvénytelen azonosító!');
    header('Location: list.php');
    exit();
}

if (!isset($_POST['delete'])) {
    setFlashMessage('danger', 'Érvénytelen törlési kérés!');
    header('Location: list.php');
    exit();
}

$id = intval($_POST['id']);

// Feldolgozás...
?>
```

---

## 5. CSRF FUNCTIONS (config.php)

### generateCsrfToken()

```php
// Token generálása és session-ben tárolása
getCsrfToken(); // Alias
generateCsrfToken(); // Teljes függvény
```

**Kimenet**: 64 karakteres hexadecimal string
**Tárolás**: $_SESSION['csrf_token']
**Idempotens**: Nem generál új token-t, ha már létezik

### validateCsrfToken($token)

```php
// Token validálása
if (validateCsrfToken($_POST['csrf_token'])) {
    // Token érvényes - feldolgozás
} else {
    // Token hibás - error
}
```

**Bemeneti**: A POST-ból kapott token
**Kimenet**: true / false
**Biztonság**: Timing-attack resist (hash_equals)

### getCsrfToken()

```php
// Template-ben a token lekérése
value="<?php echo getCsrfToken(); ?>"
```

**Kimenet**: 64 karakteres hexadecimal string
**Hatás**: Legenerálja a tokent ha még nem létezik
**Alkalmazás**: Form-okban

---

## 6. COMMON MISTAKES (Gyakori Hibák)

### ❌ HIBA 1: Token hiányzik a form-ból

```php
// ROSSZ!
<form method="POST">
    <input name="username">
    <button type="submit">Login</button>
</form>

// HELYES!
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
    <input name="username">
    <button type="submit">Login</button>
</form>
```

### ❌ HIBA 2: Token validáció hiányzik

```php
// ROSSZ!
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Feldolgozás token nélkül!
    $data = $_POST;
}

// HELYES!
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die('Invalid token');
    }
    $data = $_POST;
}
```

### ❌ HIBA 3: GET-ben token (felesleges)

```php
// ROSSZ!
<a href="delete.php?id=1&csrf_token=abc123">Delete</a>

// HELYES!
<form method="POST" action="delete.php">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">
    <input type="hidden" name="id" value="1">
    <button type="submit" name="delete">Delete</button>
</form>
```

### ❌ HIBA 4: Token újrahasználata (nincs auto-refresh)

```php
// FIGYELELEM!
// Ha egy form POST-ja sikeres:
// - Oldal újratöltődik (redirect)
// - Új token generálódik

// Ha oldalt nem töltödik újra:
// - Régi token még érvényes
// - Újabb POST-ok után invalidálódhat

// MEGOLDÁS: Mindig redirect utana POST-ra!
if ($success) {
    header('Location: list.php');
    exit();
}
```

### ❌ HIBA 5: Token escape-elés nélkül

```php
// ROSSZ!
<input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>">

// HELYES! (bár az adott kontextusban OK)
<input type="hidden" name="csrf_token" value="<?php echo escape(getCsrfToken()); ?>">
```

---

## 7. DEBUGGING

### Token ellenőrzése Chrome DevTools-ban

```javascript
// 1. Console megnyitása (F12 -> Console)

// 2. Token lekérése
document.querySelector('input[name="csrf_token"]').value

// 3. Session cookie ellenőrzése
document.cookie

// 4. Érték nyomtatása
console.log('Token:', document.querySelector('input[name="csrf_token"]').value);
```

### Token ellenőrzése Network tab-ban

```
1. F12 -> Network tab
2. Form submit kattintása
3. POST request keresése
4. Request payload megtekintése:
   - csrf_token: <érték>
   - Más form adatok
```

### Token ellenőrzése curl-lel

```bash
# Session cookie lekérése
curl -c cookies.txt http://localhost/munkalap-app/worksheets/list.php

# Token-nel POST
curl -b cookies.txt \
     -X POST http://localhost/munkalap-app/worksheets/edit.php \
     -d "csrf_token=YOUR_TOKEN_HERE&field=value"

# Token nélkül POST (ezt ellenőrizni)
curl -b cookies.txt \
     -X POST http://localhost/munkalap-app/worksheets/edit.php \
     -d "field=value"
# Eredmény: 302 redirect (Failure)
```

---

## 8. HIBAELHÁRÍTÁS

### Problem 1: "Érvénytelen kérés! Token hibás."

**Ok**:
- [ ] Token hiányzik a POST-ból
- [ ] Token érték rossz
- [ ] Session cookie letiltott
- [ ] $_SESSION üres

**Megoldás**:
```php
// Debug:
error_log('Token isset: ' . (isset($_POST['csrf_token']) ? 'yes' : 'no'));
error_log('Session token: ' . ($_SESSION['csrf_token'] ?? 'missing'));
error_log('Session status: ' . session_status());
```

### Problem 2: Token érték véletlenül nagyon rövid

**Ok**:
- [ ] random_bytes() nem működik
- [ ] Fallback openssl_random_pseudo_bytes() probléma
- [ ] $_SESSION['csrf_token'] korruptált

**Megoldás**:
```php
// config.php-ben debug:
function generateCsrfToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new Exception('Session not started.');
    }

    if (!isset($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            error_log('Token generated with random_bytes');
        } catch (Exception $e) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
            error_log('Token generated with openssl_random_pseudo_bytes');
        }
    }

    return $_SESSION['csrf_token'];
}
```

### Problem 3: Token a form-ban nem jelenik meg

**Ok**:
- [ ] getCsrfToken() függvény nem működik
- [ ] Session nincs elindítva
- [ ] PHP szintaxis hiba

**Megoldás**:
```php
// Ellenőrzés:
<?php
echo "Session status: " . session_status() . "\n";
echo "Token: " . (isset($_SESSION['csrf_token']) ? 'OK' : 'MISSING') . "\n";
echo "Function exists: " . (function_exists('getCsrfToken') ? 'YES' : 'NO') . "\n";
?>
```

---

## 9. BIZTONSÁGI ELLENŐRZÉSES

### Token ellenőrzése form-ban

```html
<!-- Helyes token -->
<input type="hidden" name="csrf_token" value="a6f8c3d2e1b9f4a7c2e5d8a1b4c7f0a3e6b9d2c5f8a1b4c7e0a3d6f9c2e5">

<!-- Ellenőrzendő -->
- [ ] Token 64 karakter hosszú?
- [ ] Csupa hexadecimal (0-9, a-f)?
- [ ] Nem üres?
- [ ] Oldal újratöltéskor változik?
```

### POST handler ellenőrzése

```php
// Ellenőrzendő sorrendben:
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { /* ... */ } // 1. Metódus
if (!isset($_POST['csrf_token'])) { /* ... */ }           // 2. Token isset
if (!validateCsrfToken($_POST['csrf_token'])) { /* ... */ } // 3. Token valid
if (!isset($_POST['id'])) { /* ... */ }                   // 4. ID isset
if (!is_numeric($_POST['id'])) { /* ... */ }               // 5. ID numeric
// Feldolgozás                                              // 6. Process
```

---

## 10. TESZT PARANCSOK

### Token a formban tesztelés

```bash
# HTML letöltése és token keresése
curl -s http://localhost/munkalap-app/worksheets/edit.php?id=1 | grep -o 'csrf_token" value="[^"]*"'

# Kimenet:
# csrf_token" value="a6f8c3d2e1b9..."
```

### POST tesztelés token nélkül

```bash
curl -X POST http://localhost/munkalap-app/worksheets/edit.php \
     -d "company_id=1&work_hours=8" \
     -i

# Kimenet:
# HTTP/1.1 302 Found
# Location: list.php
```

### POST tesztelés token-nel

```bash
TOKEN=$(curl -s http://localhost/munkalap-app/worksheets/edit.php?id=1 | grep -o 'csrf_token" value="[^"]*"' | cut -d'"' -f6)

curl -c cookies.txt -b cookies.txt \
     -X POST http://localhost/munkalap-app/worksheets/edit.php \
     -d "company_id=1&work_hours=8&csrf_token=$TOKEN&save=1" \
     -i

# Kimenet:
# HTTP/1.1 200 OK (feldolgozva)
```

---

## 11. SESSION BEÁLLÍTÁSOK

### config.php Session Config

```php
ini_set('session.cookie_httponly', 1);      // XSS védelem
ini_set('session.cookie_samesite', 'Strict'); // CSRF védelem
ini_set('session.gc_maxlifetime', 3600);     // 1 óra timeout
ini_set('session.cookie_lifetime', 0);       // Browser close logout
ini_set('session.cookie_secure', 1);         // HTTPS-only (prod)
```

### Módosítás ha szükséges

```php
// 2 órás timeout
ini_set('session.gc_maxlifetime', 7200);

// Lax SameSite (ritkán szükséges)
ini_set('session.cookie_samesite', 'Lax');

// HTTP-n is működjön (dev-ben)
// Már így van lokálhost-on
```

---

## 12. KONTAKT ÉS SUPPORT

### Kérdések?

1. Nézd meg: `CSRF_TECHNICAL_ANALYSIS.md`
2. Tesztelj: `CSRF_TESTING_GUIDE.md`
3. Ellenőrizd: `CSRF_FIX_VERIFICATION.md`

### Hibák?

1. Debug script futtatása
2. Error log ellenőrzése (`error_log()`)
3. Session cookie ellenőrzése (F12 -> Application)
4. Token érték nyomtatása (console.log)

### Production Issue?

1. Backup restore: `cp -r munkalap-app.backup munkalap-app`
2. Restart PHP-FPM: `systemctl restart php-fpm`
3. Cache törlés: `rm -rf /var/www/html/*.cache`
4. Ticket megnyitás

---

**Gyors Referencia Kártya v1.0**
Utolsó frissítés: 2025-11-10
Biztonsági Audit Csapat
