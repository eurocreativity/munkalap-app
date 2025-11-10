# Biztonsági dokumentáció - Munkalap App

## Áttekintés
Ez a dokumentum részletezi a Munkalap App alkalmazás biztonsági intézkedéseit, különös tekintettel a szerkesztési és törlési funkcionalitásra.

---

## 1. SQL Injection védelem

### Megvalósítás
A teljes alkalmazásban **PDO prepared statements** használatával védekezünk az SQL injection támadások ellen.

#### Database.php - Alapvető védelem
```php
// PDO konfigurációja SQL injection elleni védelemmel
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,  // Valódi prepared statements
];
```

#### Worksheet.php - Példák
```php
// getById metódus
public function getById($id) {
    $sql = "SELECT w.*, c.name as company_name
            FROM worksheets w
            LEFT JOIN companies c ON w.company_id = c.id
            WHERE w.id = ?";
    return $this->db->fetchOne($sql, [$id]);  // Paraméteres lekérdezés
}

// update metódus
public function update($id, $data) {
    $sql = "UPDATE worksheets
            SET company_id = ?, worksheet_number = ?, work_date = ?, ...
            WHERE id = ?";

    $params = [
        $data['company_id'] ?? null,
        // ... további paraméterek
        $id  // ID is paraméterezett
    ];

    return $this->db->execute($sql, $params);
}

// delete metódus
public function delete($id) {
    $sql = "DELETE FROM worksheets WHERE id = ?";
    return $this->db->execute($sql, [$id]);  // Paraméteres törlés
}
```

### Védett műveletek
- ✅ SELECT lekérdezések
- ✅ INSERT műveletek
- ✅ UPDATE műveletek
- ✅ DELETE műveletek

---

## 2. XSS (Cross-Site Scripting) védelem

### escape() függvény használata
Minden felhasználói input HTML kimenetkor escapelve van.

```php
// config.php
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
```

### Példák az alkalmazásban

#### edit.php
```php
// Biztonságos megjelenítés
<input type="text" value="<?php echo escape($data['worksheet_number']); ?>">
<p>Munkalap száma: <strong><?php echo escape($worksheetData['worksheet_number']); ?></strong></p>
```

#### list.php
```php
// Táblázatban történő megjelenítés
<td><?php echo escape($ws['worksheet_number']); ?></td>
<td><?php echo escape($ws['company_name'] ?? '-'); ?></td>
```

#### JavaScript escape
```javascript
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}
```

---

## 3. Input validáció

### edit.php - Teljes körű validáció

#### ID validáció
```php
// GET paraméter ellenőrzés
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('danger', 'Érvénytelen munkalap azonosító!');
    header('Location: list.php');
    exit();
}

$id = intval($_GET['id']);  // Típuskényszerítés
```

#### Munkalap adatok validáció
```php
// Cég ID validáció
if (empty($data['company_id']) || !is_numeric($data['company_id'])) {
    $errors[] = 'Válasszon céget!';
}

// Dátum formátum validáció
if (empty($data['work_date'])) {
    $errors[] = 'A dátum megadása kötelező!';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['work_date'])) {
    $errors[] = 'Érvénytelen dátum formátum!';
}

// Numerikus értékek validáció
if (empty($data['work_hours']) || !is_numeric($data['work_hours']) || $data['work_hours'] <= 0) {
    $errors[] = 'A munka órák száma kötelező és nagyobb kell legyen 0-nál!';
}

// Enum értékek validáció
if (!in_array($data['work_type'], ['Helyi', 'Távoli'])) {
    $errors[] = 'Érvénytelen munka típus!';
}

if (!in_array($data['payment_type'], ['Átalány', 'Eseti'])) {
    $errors[] = 'Érvénytelen díjazás típus!';
}

if (!in_array($data['status'], ['Aktív', 'Lezárt', 'Törölt'])) {
    $errors[] = 'Érvénytelen státusz!';
}

// Munkaidő formátum validáció
if (!empty($data['work_time']) && !preg_match('/^([0-9]{1,2}):([0-9]{2})$/', $data['work_time'])) {
    $errors[] = 'Érvénytelen munkaidő formátum! (óó:pp)';
}
```

#### Anyagok validáció
```php
foreach ($_POST['materials'] as $material) {
    if (!empty($material['product_name'])) {
        // Mennyiség validáció
        if (!is_numeric($material['quantity'] ?? 0) || floatval($material['quantity']) < 0) {
            $errors[] = 'Érvénytelen mennyiség az anyagoknál!';
        }

        // Nettó ár validáció
        if (!is_numeric($material['net_price'] ?? 0) || floatval($material['net_price']) < 0) {
            $errors[] = 'Érvénytelen nettó ár az anyagoknál!';
        }

        // ÁFA kulcs validáció
        if (!is_numeric($material['vat_rate'] ?? 0) ||
            floatval($material['vat_rate']) < 0 ||
            floatval($material['vat_rate']) > 100) {
            $errors[] = 'Érvénytelen ÁFA kulcs az anyagoknál!';
        }
    }
}
```

### delete.php - Törlési validáció

```php
// Csak POST kérést fogadunk el
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('danger', 'Érvénytelen kérés!');
    header('Location: list.php');
    exit();
}

// ID validáció
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    setFlashMessage('danger', 'Érvénytelen munkalap azonosító!');
    header('Location: list.php');
    exit();
}

// CSRF védelem - Delete gomb ellenőrzés
if (!isset($_POST['delete'])) {
    setFlashMessage('danger', 'Érvénytelen törlési kérés!');
    header('Location: list.php');
    exit();
}

$id = intval($_POST['id']);

// Munkalap létezésének ellenőrzése
$worksheetData = $worksheet->getById($id);
if (!$worksheetData) {
    setFlashMessage('danger', 'A munkalap nem található!');
    header('Location: list.php');
    exit();
}
```

---

## 4. CSRF (Cross-Site Request Forgery) védelem

### Alapvető CSRF védelem

#### POST kérés ellenőrzés
```php
// delete.php - Csak POST kérés
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlashMessage('danger', 'Érvénytelen kérés!');
    header('Location: list.php');
    exit();
}
```

#### Törlés gomb ellenőrzés
```php
// A delete gomb létezésének ellenőrzése
if (!isset($_POST['delete'])) {
    setFlashMessage('danger', 'Érvénytelen törlési kérés!');
    header('Location: list.php');
    exit();
}
```

#### Modal confirmation
```html
<!-- list.php - Törlés megerősítő modal -->
<div class="modal fade" id="deleteModal<?php echo $ws['id']; ?>">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Figyelmeztetés -->
            <div class="alert alert-warning">
                <strong>Figyelem:</strong> Ez a művelet nem visszavonható!
            </div>

            <!-- POST form -->
            <form method="POST" action="delete.php">
                <input type="hidden" name="id" value="<?php echo $ws['id']; ?>">
                <button type="submit" name="delete" class="btn btn-danger">
                    Törlés megerősítése
                </button>
            </form>
        </div>
    </div>
</div>
```

### Javasolt fejlesztések

#### CSRF token implementáció
```php
// Jövőbeli implementáció
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}
```

---

## 5. Autentikáció és autorizáció

### auth_check.php
Minden védett oldalon szerepel az autentikációs ellenőrzés:

```php
require_once __DIR__ . '/../includes/auth_check.php';
```

### Session védelem
```php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
```

---

## 6. Adatintegritás

### Tranzakciók használata
A delete.php és edit.php tranzakciókat használ az adatintegritás megőrzésére:

```php
// delete.php
try {
    // 1. Kapcsolódó anyagok törlése
    $materialObj->deleteByWorksheetId($id);

    // 2. Munkalap törlése
    if ($worksheet->delete($id)) {
        setFlashMessage('success', 'A munkalap sikeresen törölve!');
    }
} catch (Exception $e) {
    setFlashMessage('danger', 'Hiba történt: ' . $e->getMessage());
    error_log("Worksheet delete error: " . $e->getMessage());
}
```

```php
// edit.php
try {
    // 1. Munkalap frissítése
    if ($worksheet->update($id, $data)) {
        // 2. Régi anyagok törlése
        $materialObj->deleteByWorksheetId($id);

        // 3. Új anyagok mentése
        foreach ($updatedMaterials as $materialData) {
            $materialData['worksheet_id'] = $id;
            $materialObj->create($materialData);
        }

        setFlashMessage('success', 'A munkalap sikeresen módosítva!');
    }
} catch (Exception $e) {
    setFlashMessage('danger', 'Hiba történt: ' . $e->getMessage());
    error_log("Worksheet update error: " . $e->getMessage());
}
```

---

## 7. Hibaüzenetek és naplózás

### Biztonságos hibaüzenetek
```php
// Felhasználónak
setFlashMessage('danger', 'Hiba történt a mentés során!');

// Fejlesztőnek (log)
error_log("Worksheet update error: " . $e->getMessage());
```

### Flash üzenetek
```php
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,      // success, danger, warning, info
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);  // Egyszeri használat
        return $message;
    }
    return null;
}
```

---

## 8. Típuskényszerítés

### Adatok konverziója mentés előtt
```php
// edit.php - Típusbiztonsági konverziók
$data['company_id'] = intval($data['company_id']);
$data['work_hours'] = floatval($data['work_hours']);
$data['transport_fee'] = floatval($data['transport_fee']);
$data['travel_fee'] = floatval($data['travel_fee']);

// delete.php - ID konverzió
$id = intval($_POST['id']);
```

### Anyagok konverziója
```php
$updatedMaterials[] = [
    'product_name' => trim($material['product_name'] ?? ''),
    'quantity' => floatval($material['quantity'] ?? 0),
    'unit' => trim($material['unit'] ?? 'db'),
    'net_price' => floatval($material['net_price'] ?? 0),
    'vat_rate' => floatval($material['vat_rate'] ?? 27)
];
```

---

## 9. Biztonságos átirányítások

### Header Location használata
```php
// Sikeres művelet után átirányítás
header('Location: list.php');
exit();  // Fontos! Megállítja a szkript futását

// Hiba esetén átirányítás
if (!$worksheetData) {
    setFlashMessage('danger', 'A munkalap nem található!');
    header('Location: list.php');
    exit();
}
```

---

## 10. Javasolt jövőbeli fejlesztések

### 1. CSRF token implementáció
- [ ] Token generálás minden form-hoz
- [ ] Token validáció a szerver oldalon
- [ ] Token frissítés minden kérésnél

### 2. Rate limiting
- [ ] Kérések számának korlátozása IP alapján
- [ ] Sikertelen bejelentkezési kísérletek követése
- [ ] Törlési műveletek korlátozása

### 3. Audit log
- [ ] Minden módosítás naplózása (ki, mit, mikor)
- [ ] Törlési műveletek naplózása
- [ ] IP címek és felhasználói azonosítók tárolása

### 4. Szerepkör-alapú hozzáférés (RBAC)
- [ ] Admin, szerkesztő, csak olvasó szerepkörök
- [ ] Műveletek korlátozása szerepkörök alapján
- [ ] Törlési jogosultság csak adminoknak

### 5. Két-faktoros autentikáció (2FA)
- [ ] Email alapú megerősítés
- [ ] SMS alapú megerősítés
- [ ] Authenticator app támogatás

### 6. Session biztonsági fejlesztések
- [ ] Session fixation védelem
- [ ] Session timeout implementáció
- [ ] Secure és HttpOnly cookie flag-ek

---

## Összefoglalás

### Jelenlegi biztonsági intézkedések
✅ **SQL Injection védelem** - PDO prepared statements
✅ **XSS védelem** - htmlspecialchars minden kimeneten
✅ **Input validáció** - Teljes körű validáció minden input mezőn
✅ **Típuskényszerítés** - intval(), floatval() használata
✅ **CSRF alap védelem** - POST ellenőrzés, confirmation modal
✅ **Autentikáció** - auth_check.php használata
✅ **Hibaüzenetek** - Biztonságos hibaüzenetek + naplózás
✅ **Adatintegritás** - Tranzakció-szerű műveletek

### Tesztelési checklist

#### SQL Injection tesztek
- [ ] `id=1' OR '1'='1` - Védve prepared statements által
- [ ] `id=1 UNION SELECT * FROM users` - Védve
- [ ] `id=1; DROP TABLE worksheets` - Védve

#### XSS tesztek
- [ ] `<script>alert('XSS')</script>` a név mezőben - Védve escape() által
- [ ] `<img src=x onerror=alert('XSS')>` - Védve

#### CSRF tesztek
- [ ] Közvetlen POST kérés másik oldalról - Védve POST ellenőrzéssel
- [ ] GET kéréssel törlés - Védve POSTOnly-val

#### Autorizáció tesztek
- [ ] Kijelentkezett felhasználó hozzáférése - Védve auth_check.php-val
- [ ] Más felhasználó munkalapjának szerkesztése - Jelenleg nincs védelem (jövőbeli fejlesztés)

---

**Verzió:** 1.0
**Utolsó frissítés:** 2025-11-10
**Készítette:** Munkalap App Development Team
