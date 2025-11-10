# Tesztelési Checklist - Munkalap App Edit/Delete funkcionalitás

## 1. Funkcionális tesztek

### edit.php

#### Alapvető funkciók
- [ ] **Munkalap betöltése:** GET paraméterrel válassz ki egy munkalapot
  - URL: `http://localhost/munkalap-app/worksheets/edit.php?id=1`
  - Elvárt: Munkalap adatok megjelennek

- [ ] **Munkalap módosítása:** Változtass meg 1-2 mezőt és mentsd
  - Teszt: Cég neve, munka órák száma
  - Elvárt: Sikeres mentés, redirect list.php-hez

- [ ] **Anyagok hozzáadása:** Adj hozzá új anyagot
  - Teszt: "Új anyag hozzáadása" gomb kattintás
  - Elvárt: Új anyag sor megjelenik

- [ ] **Anyagok törlése:** Törölj egy meglévő anyagot
  - Teszt: Törlés gomb kattintás anyag soron
  - Elvárt: Anyag sor eltűnik

- [ ] **Anyag árak számítása:** Adj meg nettó árat és ÁFA kulcsot
  - Teszt: Nettó ár: 1000, ÁFA: 27%
  - Elvárt: Bruttó ár: 1270

- [ ] **Státusz módosítás:** Változtasd meg a státuszt
  - Teszt: Aktív → Lezárt
  - Elvárt: Státusz mentve

- [ ] **Munka típus váltás:** Helyi ↔ Távoli
  - Teszt: Helyi → Távoli
  - Elvárt: Kiszállási díj mező eltűnik

- [ ] **Mégse gomb:** Kattints a "Mégse" gombra
  - Elvárt: Vissza a list.php-hez, nincs mentés

#### Meglévő anyagok
- [ ] **Anyagok betöltése:** Nyiss meg egy anyagokat tartalmazó munkalapot
  - Elvárt: Meglévő anyagok megjelennek

- [ ] **Anyag módosítása:** Változtass meg egy meglévő anyagot
  - Teszt: Mennyiség 5 → 10
  - Elvárt: Sikeres mentés

### delete.php

#### Alapvető funkciók
- [ ] **Törlés list.php-ből:** Kattints törlés gombra a listában
  - Elvárt: Modal megnyílik

- [ ] **Modal adatok:** Ellenőrizd a modal tartalmát
  - Elvárt: Munkalap szám és cég név megjelenik

- [ ] **Törlés megerősítése:** Kattints "Törlés megerősítése" gombra
  - Elvárt: Sikeres törlés, redirect list.php-hez, success üzenet

- [ ] **Törlés megszakítása:** Kattints "Mégse" gombra
  - Elvárt: Modal bezárul, nincs törlés

- [ ] **Törlés edit.php-ből:** Szerkesztés közben törlés
  - Elvárt: Modal megnyílik, törlés működik

- [ ] **Kapcsolódó anyagok törlése:** Töröld egy anyagokat tartalmazó munkalapot
  - Elvárt: Munkalap és anyagok is törlődnek

### list.php

#### Műveletek gombok
- [ ] **Szerkesztés gomb:** Kattints a ceruza ikonra
  - Elvárt: edit.php betöltődik a megfelelő ID-val

- [ ] **Törlés gomb:** Kattints a kuka ikonra
  - Elvárt: Törlés modal megnyílik

- [ ] **PDF gomb:** Kattints a PDF ikonra
  - Elvárt: PDF generálódik (ha implementálva)

---

## 2. Validációs tesztek

### edit.php validáció

#### Kötelező mezők
- [ ] **Üres cég:** Ne válassz céget
  - Elvárt: "Válasszon céget!" hiba

- [ ] **Üres munkalap szám:** Töröld ki a munkalap számot
  - Elvárt: "A munkalap száma kötelező!" hiba

- [ ] **Üres dátum:** Töröld ki a dátumot
  - Elvárt: "A dátum megadása kötelező!" hiba

- [ ] **Üres munka órák:** Töröld ki a munka órákat
  - Elvárt: "A munka órák száma kötelező!" hiba

- [ ] **Nulla munka órák:** Adj meg 0 órákat
  - Elvárt: "A munka órák száma nagyobb kell legyen 0-nál!" hiba

#### Formátum validáció
- [ ] **Rossz dátum formátum:** `2025-13-45`
  - Elvárt: "Érvénytelen dátum formátum!" hiba

- [ ] **Rossz munkaidő formátum:** `25:99`
  - Elvárt: "Érvénytelen munkaidő formátum!" hiba

- [ ] **Negatív munka órák:** `-5`
  - Elvárt: Browser natív validáció vagy hiba

#### Anyagok validáció
- [ ] **Negatív mennyiség:** `-10`
  - Elvárt: "Érvénytelen mennyiség!" hiba

- [ ] **Negatív nettó ár:** `-1000`
  - Elvárt: "Érvénytelen nettó ár!" hiba

- [ ] **ÁFA > 100%:** `150`
  - Elvárt: "Érvénytelen ÁFA kulcs!" hiba

- [ ] **Nem numerikus ár:** `abc`
  - Elvárt: Hiba üzenet

---

## 3. Biztonsági tesztek

### SQL Injection tesztek

#### edit.php
- [ ] **ID paraméter:** `?id=1' OR '1'='1`
  - URL: `http://localhost/munkalap-app/worksheets/edit.php?id=1' OR '1'='1`
  - Elvárt: "Érvénytelen munkalap azonosító!" hiba, redirect

- [ ] **ID paraméter:** `?id=1 UNION SELECT * FROM users`
  - Elvárt: "Érvénytelen munkalap azonosító!" hiba, redirect

- [ ] **ID paraméter:** `?id=1; DROP TABLE worksheets`
  - Elvárt: "Érvénytelen munkalap azonosító!" hiba, redirect

- [ ] **Munkalap szám mező:** `' OR '1'='1`
  - Teszt: Írd be a munkalap szám mezőbe
  - Elvárt: Escape-elve mentődik, prepared statement védelem

#### delete.php
- [ ] **POST ID paraméter:** POST data `id=1' OR '1'='1`
  - Használj Postman vagy curl
  - Elvárt: "Érvénytelen munkalap azonosító!" hiba, redirect

- [ ] **POST ID paraméter:** `id=1; DROP TABLE worksheets`
  - Elvárt: "Érvénytelen munkalap azonosító!" hiba, redirect

### XSS (Cross-Site Scripting) tesztek

#### edit.php
- [ ] **Hiba bejelentő neve:** `<script>alert('XSS')</script>`
  - Teszt: Írd be és mentsd
  - Elvárt: Escape-elve jelenik meg (látható script tag)

- [ ] **Eszköz neve:** `<img src=x onerror=alert('XSS')>`
  - Elvárt: Escape-elve jelenik meg

- [ ] **Munka leírása:** `<b>Bold</b> text`
  - Elvárt: Escape-elve jelenik meg (látható HTML tag)

- [ ] **Anyag neve:** `<script>document.location='http://evil.com'</script>`
  - Elvárt: Escape-elve jelenik meg

#### list.php
- [ ] **Munkalap megjelenítés:** Nyisd meg a listát XSS adatokkal
  - Elvárt: Minden adat escape-elve jelenik meg

### CSRF (Cross-Site Request Forgery) tesztek

#### delete.php
- [ ] **GET kérés:** `GET /worksheets/delete.php?id=1`
  - Teszt: Browser címsorba írd be
  - Elvárt: "Érvénytelen kérés!" hiba, redirect

- [ ] **POST kérés másik oldalról:** Készíts egy külső HTML formot
  ```html
  <form action="http://localhost/munkalap-app/worksheets/delete.php" method="POST">
    <input type="hidden" name="id" value="1">
    <button type="submit">Küldés</button>
  </form>
  ```
  - Elvárt: "Érvénytelen törlési kérés!" hiba (delete paraméter hiányzik)

- [ ] **POST kérés delete paraméter nélkül:**
  - POST data: `id=1`
  - Elvárt: "Érvénytelen törlési kérés!" hiba

### Autentikáció tesztek

#### Kijelentkezett felhasználó
- [ ] **edit.php elérése:** Jelentkezz ki, majd navigálj `/worksheets/edit.php?id=1`
  - Elvárt: Redirect login oldalra

- [ ] **delete.php elérése:** POST kérés kijelentkezve
  - Elvárt: Redirect login oldalra

- [ ] **list.php elérése:** Navigálj `/worksheets/list.php` kijelentkezve
  - Elvárt: Redirect login oldalra

---

## 4. Edge Case tesztek

### Nem létező adatok
- [ ] **Nem létező ID:** `?id=99999`
  - Elvárt: "A munkalap nem található!" hiba, redirect

- [ ] **Törölt munkalap szerkesztése:** Töröld ki a munkalapot adatbázisból, majd próbáld szerkeszteni
  - Elvárt: "A munkalap nem található!" hiba

### Speciális karakterek
- [ ] **Unicode karakterek:** `áéíóöőúüű ÁÉÍÓÖŐÚÜŰ`
  - Teszt: Írd be különböző mezőkbe
  - Elvárt: Helyesen mentve és megjelenítve

- [ ] **Emoji karakterek:** `😀 🚀 ❤️`
  - Elvárt: Helyesen mentve és megjelenítve (ha UTF8MB4 charset)

- [ ] **Speciális jelek:** `& < > " ' / \`
  - Elvárt: Escape-elve mentve és megjelenítve

### Nagyméretű adatok
- [ ] **Hosszú leírás:** 5000+ karakter
  - Elvárt: Sikeres mentés vagy field limit elérése

- [ ] **Sok anyag:** 50+ anyag hozzáadása
  - Elvárt: Sikeres mentés

- [ ] **Nagy számok:** Munka órák: 99999.99
  - Elvárt: Sikeres mentés

---

## 5. UI/UX tesztek

### Responsive design
- [ ] **Mobil nézet:** 375px szélesség (iPhone SE)
  - Elvárt: Megfelelő megjelenés

- [ ] **Tablet nézet:** 768px szélesség (iPad)
  - Elvárt: Megfelelő megjelenés

- [ ] **Desktop nézet:** 1920px szélesség
  - Elvárt: Megfelelő megjelenés

### Form használhatóság
- [ ] **Tab navigáció:** Tab billentyűvel mezők között
  - Elvárt: Logikus sorrend

- [ ] **Enter mentés:** Enter billentyű a form mezőkben
  - Elvárt: Form submit

- [ ] **Törlés modal:** ESC billentyű a modalban
  - Elvárt: Modal bezárul

### Flash üzenetek
- [ ] **Success üzenet:** Sikeres mentés
  - Elvárt: Zöld háttér, dismiss gomb

- [ ] **Error üzenet:** Validációs hiba
  - Elvárt: Piros háttér, dismiss gomb, lista

- [ ] **Üzenet eltűnése:** Dismiss gomb kattintás
  - Elvárt: Üzenet eltűnik

---

## 6. Teljesítmény tesztek

### Adatbázis műveletek
- [ ] **Betöltési idő:** edit.php betöltése
  - Elvárt: < 500ms

- [ ] **Mentési idő:** Munkalap mentése 10 anyaggal
  - Elvárt: < 1000ms

- [ ] **Törlési idő:** Munkalap törlése 10 anyaggal
  - Elvárt: < 500ms

### JavaScript teljesítmény
- [ ] **50 anyag sor hozzáadása:** "Új anyag" gomb 50x kattintás
  - Elvárt: Nincs lag, smooth működés

- [ ] **Bruttó ár számítás:** Gyors gépelés nettó ár mezőben
  - Elvárt: Valós idejű számítás, nincs lag

---

## 7. Kompatibilitási tesztek

### Böngészők
- [ ] **Chrome:** Legújabb verzió
  - Teszt: Minden funkció
  - Elvárt: Működik

- [ ] **Firefox:** Legújabb verzió
  - Teszt: Minden funkció
  - Elvárt: Működik

- [ ] **Safari:** Legújabb verzió (macOS/iOS)
  - Teszt: Minden funkció
  - Elvárt: Működik

- [ ] **Edge:** Legújabb verzió
  - Teszt: Minden funkció
  - Elvárt: Működik

### PHP verziók
- [ ] **PHP 7.4:** Minimum támogatott verzió
  - Elvárt: Működik

- [ ] **PHP 8.0:** Ajánlott verzió
  - Elvárt: Működik

- [ ] **PHP 8.1+:** Legújabb verzió
  - Elvárt: Működik

---

## 8. Regression tesztek

### Meglévő funkciók
- [ ] **add.php:** Új munkalap hozzáadás
  - Elvárt: Továbbra is működik

- [ ] **list.php:** Szűrés funkció
  - Elvárt: Továbbra is működik

- [ ] **pdf.php:** PDF generálás
  - Elvárt: Továbbra is működik

- [ ] **Login/Logout:** Autentikáció
  - Elvárt: Továbbra is működik

---

## 9. Adatbázis integritás tesztek

### Munkalap törlés
- [ ] **Kapcsolódó anyagok:** Ellenőrizd adatbázisban
  ```sql
  SELECT * FROM materials WHERE worksheet_id = 1;
  ```
  - Munkalap törlése előtt: Anyagok léteznek
  - Munkalap törlése után: Anyagok törlődtek

- [ ] **Munkalap rekord:** Ellenőrizd adatbázisban
  ```sql
  SELECT * FROM worksheets WHERE id = 1;
  ```
  - Törlés után: Rekord nem létezik

### Munkalap szerkesztés
- [ ] **UPDATE végrehajtása:** Ellenőrizd adatbázisban
  ```sql
  SELECT * FROM worksheets WHERE id = 1;
  ```
  - Szerkesztés után: Adatok frissültek

- [ ] **Anyagok frissítése:** Ellenőrizd adatbázisban
  ```sql
  SELECT * FROM materials WHERE worksheet_id = 1;
  ```
  - Szerkesztés után: Régi anyagok törlődtek, újak létrejöttek

---

## 10. Hibaüzenet tesztek

### edit.php hibaüzenetek
- [ ] Érvénytelen ID
- [ ] Munkalap nem található
- [ ] Cég kiválasztása kötelező
- [ ] Dátum kötelező
- [ ] Munka órák kötelező
- [ ] Érvénytelen dátum formátum
- [ ] Érvénytelen munkaidő formátum
- [ ] Érvénytelen munka típus
- [ ] Érvénytelen díjazás típus
- [ ] Érvénytelen státusz
- [ ] Érvénytelen anyag adatok

### delete.php hibaüzenetek
- [ ] Érvénytelen kérés (nem POST)
- [ ] Érvénytelen ID
- [ ] Érvénytelen törlési kérés (nincs delete paraméter)
- [ ] Munkalap nem található
- [ ] Hiba törlés közben

---

## Tesztelési eszközök

### Manuális tesztelés
- Browser DevTools (F12)
- Network tab (HTTP kérések ellenőrzése)
- Console tab (JavaScript hibák)

### Automatizált tesztelés (opcionális)
- PHPUnit: Unit tesztek
- Selenium: E2E tesztek
- Postman: API tesztek

### Adatbázis ellenőrzés
- phpMyAdmin
- MySQL Workbench
- SQL parancsok

### Biztonsági tesztelés
- OWASP ZAP: Automatikus biztonsági scan
- Burp Suite: Manual security testing
- sqlmap: SQL injection tesztelés

---

## Tesztelési sorrend

1. **Funkcionális tesztek** (30 perc)
2. **Validációs tesztek** (20 perc)
3. **Biztonsági tesztek** (45 perc)
4. **Edge case tesztek** (15 perc)
5. **UI/UX tesztek** (15 perc)
6. **Teljesítmény tesztek** (10 perc)
7. **Kompatibilitási tesztek** (30 perc)
8. **Regression tesztek** (15 perc)
9. **Adatbázis integritás** (10 perc)
10. **Hibaüzenet tesztek** (10 perc)

**Becsült összes idő:** ~3 óra

---

## Jelentés készítése

### Teszt eredmények dokumentálása
```
Teszt név: [Teszt neve]
Állapot: [✅ Sikeres / ❌ Sikertelen / ⚠️ Részleges]
Leírás: [Mi történt]
Elvárt eredmény: [Mit vártunk]
Kapott eredmény: [Mit kaptunk]
Screenshot: [Ha van]
Megjegyzés: [További infók]
```

### Prioritások
- 🔴 **Kritikus:** Biztonsági hibák, adatvesztés
- 🟠 **Magas:** Funkciók nem működnek
- 🟡 **Közepes:** UI problémák, validációs hibák
- 🟢 **Alacsony:** Apró javítások, optimalizációk

---

**Verzió:** 1.0
**Utolsó frissítés:** 2025-11-10
**Tesztelő:** [Név]
**Dátum:** [Dátum]
