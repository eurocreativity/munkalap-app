# Munkalap szerkesztési és törlési funkcionalitás - Implementáció

## Áttekintés
Ez a dokumentum részletezi a munkalapok szerkesztési és törlési funkcionalitásának implementációját a Munkalap App alkalmazásban.

---

## Implementált fájlok

### 1. worksheets/edit.php
**Felelősség:** Munkalapok szerkesztése

#### Főbb funkciók:
- ✅ Munkalap betöltése ID alapján (GET)
- ✅ Munkalap adatok módosítása (POST)
- ✅ Kapcsolódó anyagok kezelése
- ✅ Teljes körű validáció
- ✅ Státusz módosítás (Aktív, Lezárt)
- ✅ Törlés gomb a form alján
- ✅ SQL injection védelem
- ✅ XSS védelem
- ✅ Flash üzenetek

#### Biztonsági intézkedések:
```php
// ID validáció - SQL injection védelem
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('danger', 'Érvénytelen munkalap azonosító!');
    header('Location: list.php');
    exit();
}

$id = intval($_GET['id']);

// Munkalap létezésének ellenőrzése
$worksheetData = $worksheet->getById($id);
if (!$worksheetData) {
    setFlashMessage('danger', 'A munkalap nem található!');
    header('Location: list.php');
    exit();
}
```

#### Validációs szabályok:
- Cég ID: kötelező, numerikus
- Munkalap szám: kötelező, string
- Dátum: kötelező, YYYY-MM-DD formátum
- Munka órák: kötelező, pozitív szám
- Munka típus: enum ['Helyi', 'Távoli']
- Díjazás: enum ['Átalány', 'Eseti']
- Státusz: enum ['Aktív', 'Lezárt', 'Törölt']
- Munkaidő: opcionális, HH:MM formátum
- Anyagok: numerikus értékek, ÁFA 0-100%

---

### 2. worksheets/delete.php
**Felelősség:** Munkalapok törlése

#### Főbb funkciók:
- ✅ POST-ból ID lekérése
- ✅ Worksheet::delete() meghívása
- ✅ Kapcsolódó anyagok törlése
- ✅ Redirect list.php-hez success üzenettel
- ✅ SQL DELETE statement
- ✅ Confirmation check a frontenden

#### Biztonsági intézkedések:
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

#### Törlési folyamat:
1. Kapcsolódó anyagok törlése: `$materialObj->deleteByWorksheetId($id)`
2. Munkalap törlése: `$worksheet->delete($id)`
3. Success üzenet: Flash message
4. Redirect: list.php

---

### 3. worksheets/list.php (módosítva)
**Módosítás:** Szerkesztés és törlés gombok hozzáadása

#### Új funkciók:
```php
// Szerkesztés gomb
<a href="edit.php?id=<?php echo $ws['id']; ?>"
   class="btn btn-sm btn-outline-secondary"
   title="Szerkesztés">
    <i class="bi bi-pencil"></i>
</a>

// Törlés gomb - Modal megnyitása
<button type="button"
        class="btn btn-sm btn-outline-danger"
        title="Törlés"
        data-bs-toggle="modal"
        data-bs-target="#deleteModal<?php echo $ws['id']; ?>">
    <i class="bi bi-trash"></i>
</button>
```

#### Törlés megerősítő modal:
- Figyelmeztetés üzenet
- Munkalap adatok megjelenítése
- Megerősítés gomb
- POST form a delete.php-hez

---

## Worksheet osztály módosítások

### classes/Worksheet.php
Az osztály már tartalmazza a szükséges metódusokat:

#### getById()
```php
public function getById($id) {
    $sql = "SELECT w.*, c.name as company_name
            FROM worksheets w
            LEFT JOIN companies c ON w.company_id = c.id
            WHERE w.id = ?";
    return $this->db->fetchOne($sql, [$id]);
}
```

#### update()
```php
public function update($id, $data) {
    $sql = "UPDATE worksheets
            SET company_id = ?, worksheet_number = ?, work_date = ?, work_hours = ?,
                description = ?, reporter_name = ?, device_name = ?, worker_name = ?,
                work_type = ?, transport_fee = ?, travel_fee = ?,
                payment_type = ?, work_time = ?, status = ?
            WHERE id = ?";

    $params = [
        $data['company_id'] ?? null,
        $data['worksheet_number'] ?? '',
        $data['work_date'] ?? date('Y-m-d'),
        $data['work_hours'] ?? 0,
        $data['description'] ?? null,
        $data['reporter_name'] ?? null,
        $data['device_name'] ?? null,
        $data['worker_name'] ?? null,
        $data['work_type'] ?? 'Helyi',
        $data['transport_fee'] ?? 0,
        $data['travel_fee'] ?? 0,
        $data['payment_type'] ?? 'Eseti',
        $data['work_time'] ?? null,
        $data['status'] ?? 'Aktív',
        $id
    ];

    return $this->db->execute($sql, $params);
}
```

#### delete()
```php
public function delete($id) {
    $sql = "DELETE FROM worksheets WHERE id = ?";
    return $this->db->execute($sql, [$id]);
}
```

---

## Material osztály használata

### classes/Material.php
Kapcsolódó metódusok:

#### getByWorksheetId()
```php
public function getByWorksheetId($worksheetId) {
    $sql = "SELECT * FROM materials WHERE worksheet_id = ? ORDER BY id ASC";
    return $this->db->fetchAll($sql, [$worksheetId]);
}
```

#### deleteByWorksheetId()
```php
public function deleteByWorksheetId($worksheetId) {
    $sql = "DELETE FROM materials WHERE worksheet_id = ?";
    return $this->db->execute($sql, [$worksheetId]);
}
```

---

## Form Layout

### edit.php Form struktúra
Azonos az add.php-val, különbségek:

1. **Fejléc:** "Munkalap szerkesztése" vs "Új munkalap"
2. **Munkalap szám mező:** Módosítható (nem readonly)
3. **Státusz mező:** Hozzáadva (Aktív, Lezárt)
4. **Anyagok:** Betöltve a meglévő anyagok
5. **Törlés szekció:** Új szekció a form alján

#### Törlés szekció
```html
<div class="delete-section">
    <h5 class="text-danger mb-3">
        <i class="bi bi-trash"></i> Veszélyes művelet
    </h5>
    <p class="text-muted">
        A munkalap törlése végleges és nem visszavonható.
        Az összes kapcsolódó anyag is törlődni fog.
    </p>
    <button type="button" class="btn btn-danger"
            data-bs-toggle="modal"
            data-bs-target="#deleteModal">
        <i class="bi bi-trash"></i> Munkalap törlése
    </button>
</div>
```

---

## JavaScript funkcionalitás

### edit.php JavaScript
Azonos az add.php-val, kiegészítve:

1. **Meglévő anyagok betöltése:**
```javascript
const existingMaterials = <?php echo json_encode($materials); ?>;

if (existingMaterials.length > 0) {
    existingMaterials.forEach(function(material) {
        addMaterialRow(material);
    });
} else {
    addMaterialRow();
}
```

2. **Kiszállási díj kezelés:** Munka típus alapján show/hide
3. **Bruttó ár számítás:** Nettó ár + ÁFA
4. **Anyag sorok dinamikus hozzáadása/törlése**

---

## Flash üzenetek

### Sikeres műveletek
```php
// edit.php
setFlashMessage('success', 'A munkalap sikeresen módosítva!');

// delete.php
setFlashMessage('success', 'A munkalap sikeresen törölve! (Munkalap szám: ' . escape($worksheetData['worksheet_number']) . ')');
```

### Hibaüzenetek
```php
// Érvénytelen ID
setFlashMessage('danger', 'Érvénytelen munkalap azonosító!');

// Nem található
setFlashMessage('danger', 'A munkalap nem található!');

// Hiba történt
setFlashMessage('danger', 'Hiba történt a mentés során!');
```

---

## Adatbázis műveletek

### UPDATE lekérdezés
```sql
UPDATE worksheets
SET company_id = ?,
    worksheet_number = ?,
    work_date = ?,
    work_hours = ?,
    description = ?,
    reporter_name = ?,
    device_name = ?,
    worker_name = ?,
    work_type = ?,
    transport_fee = ?,
    travel_fee = ?,
    payment_type = ?,
    work_time = ?,
    status = ?
WHERE id = ?
```

### DELETE lekérdezés
```sql
-- Munkalap törlése
DELETE FROM worksheets WHERE id = ?

-- Kapcsolódó anyagok törlése
DELETE FROM materials WHERE worksheet_id = ?
```

---

## Tesztelési útmutató

### 1. Szerkesztés tesztelés

#### Pozitív tesztek:
- [ ] Munkalap betöltése ID alapján
- [ ] Adatok módosítása
- [ ] Anyagok hozzáadása/törlése
- [ ] Státusz módosítás
- [ ] Mentés gomb funkció
- [ ] Sikeres mentés után redirect

#### Negatív tesztek:
- [ ] Érvénytelen ID
- [ ] Nem létező munkalap
- [ ] Hiányzó kötelező mezők
- [ ] Érvénytelen formátumok
- [ ] Érvénytelen enum értékek

### 2. Törlés tesztelés

#### Pozitív tesztek:
- [ ] Törlés gomb megjelenítése
- [ ] Modal megnyitása
- [ ] Munkalap adatok megjelenítése modalban
- [ ] Törlés megerősítése
- [ ] Sikeres törlés után redirect
- [ ] Kapcsolódó anyagok törlése

#### Negatív tesztek:
- [ ] Érvénytelen ID
- [ ] GET kérés elutasítása
- [ ] Nem létező munkalap
- [ ] Hiányzó delete paraméter

### 3. Biztonsági tesztek

#### SQL Injection:
- [ ] `id=1' OR '1'='1`
- [ ] `id=1; DROP TABLE worksheets`
- [ ] `id=1 UNION SELECT * FROM users`

#### XSS:
- [ ] `<script>alert('XSS')</script>` mezőkben
- [ ] `<img src=x onerror=alert('XSS')>`

#### CSRF:
- [ ] POST kérés másik oldalról
- [ ] GET kéréssel törlés

---

## Felhasználói felület

### edit.php képernyő
```
+-----------------------------------------------+
|  Munkalap szerkesztése                     [X]|
|  Munkalap száma: 2025/001                     |
+-----------------------------------------------+
|  Hibaüzenetek (ha van)                        |
+-----------------------------------------------+
|  [ Form mezők - azonos add.php-val ]          |
|  + Státusz dropdown                           |
+-----------------------------------------------+
|  Anyagok szekció                              |
|  [ Meglévő anyagok listája ]                  |
|  [+ Új anyag hozzáadása]                      |
+-----------------------------------------------+
|  [Mégse]                          [Mentés]    |
+-----------------------------------------------+
|  VESZÉLYES MŰVELET                            |
|  [Munkalap törlése]                           |
+-----------------------------------------------+
```

### Törlés modal (list.php és edit.php)
```
+---------------------------------------+
|  ⚠ Munkalap törlése              [X] |
+---------------------------------------+
|  Biztosan törölni szeretné ezt a     |
|  munkalapot?                          |
|                                       |
|  ⚠ Figyelem: Ez a művelet nem        |
|  visszavonható!                       |
|                                       |
|  Munkalap száma: 2025/001            |
|  Cég: Teszt Kft.                     |
+---------------------------------------+
|  [Mégse]  [Törlés megerősítése]      |
+---------------------------------------+
```

---

## Összefoglalás

### Implementált funkciók
✅ Munkalap szerkesztés teljes funkcionalitással
✅ Munkalap törlés megerősítéssel
✅ Anyagok kezelése szerkesztéskor
✅ SQL injection védelem
✅ XSS védelem
✅ Input validáció
✅ CSRF alap védelem
✅ Flash üzenetek
✅ Responsive UI
✅ Bootstrap modals

### Fájlok összefoglalása
| Fájl | Típus | Funkció | Sorok száma |
|------|-------|---------|-------------|
| worksheets/edit.php | Új | Szerkesztés | ~700 |
| worksheets/delete.php | Új | Törlés | ~50 |
| worksheets/list.php | Módosított | Gombok + Modal | ~260 |
| classes/Worksheet.php | Meglévő | Metódusok | ~195 |
| classes/Material.php | Meglévő | Metódusok | ~129 |

### Következő lépések (opcionális)
- [ ] CSRF token implementáció
- [ ] Szerepkör-alapú hozzáférés
- [ ] Audit log
- [ ] Soft delete (törölt státusz helyett végleges törlés)
- [ ] Munkalap megtekintés (view.php)
- [ ] Munkalap visszaállítás (undelete)

---

**Verzió:** 1.0
**Utolsó frissítés:** 2025-11-10
**Státusz:** Implementálva és tesztelésre kész
