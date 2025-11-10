# CSRF TOKEN IMPLEMENTÁCIÓ - TESZTELÉSI ÚTMUTATÓ

## Bevezetés

Ez az útmutató gyakorlati módszereket mutat a CSRF token védekezés tesztelésére. Nyolc teszt módszer és hat tesztelési forgatókönyv.

---

## 1. TESZT MÓDSZER 1: Manual Form Testing

### Cél
CSRF token meglétének ellenőrzése minden form-ban

### Lépések

#### 1.1 Edit Form Teszt

```
1. Webböngésző: Navigáció -> worksheets/edit.php?id=1
2. Chrome DevTools (F12) megnyitása
3. Elements tab -> Form keresése
4. KERESÉS: <input type="hidden" name="csrf_token"
```

**Ellenőrizendő**:
```html
<input type="hidden" name="csrf_token" value="a6f8c3d2e1b9...">
```

**Eredmény**:
- [ ] Token jelen van
- [ ] Token érték nem üres
- [ ] Token érték 64 karakter hosszú (hexadecimal)
- [ ] Oldal újratöltés után más token érték

#### 1.2 Add Form Teszt

```
1. Webböngésző: Navigáció -> worksheets/add.php
2. Chrome DevTools (F12) megnyitása
3. Elements tab -> Form keresése
4. KERESÉS: <input type="hidden" name="csrf_token"
```

**Ellenőrizendő**: Ugyanaz, mint edit form

#### 1.3 Delete Modal Teszt

```
1. Webböngésző: Navigáció -> worksheets/list.php
2. Egy munkalap mellett "Törlés" gomb kattintása
3. Modal megnyitása
4. Chrome DevTools (F12) megnyitása
5. Elements tab -> Modal form keresése
```

**Ellenőrizendő**: Token jelen van a delete form-ban

---

## 2. TESZT MÓDSZER 2: Token Érték Validáció

### Cél
Token érték formátumának és hosszának ellenőrzése

### Script (Browser Console)

```javascript
// Konzol megnyitása (F12 -> Console)

// 1. Token lekérése
const tokenInput = document.querySelector('input[name="csrf_token"]');
const tokenValue = tokenInput.value;

// 2. Érték nyomtatása
console.log('Token érték:', tokenValue);
console.log('Token hossz:', tokenValue.length);

// 3. Hexadecimal ellenőrzés
const isHex = /^[0-9a-f]{64}$/i.test(tokenValue);
console.log('Helyes formátum (64 hex char):', isHex);

// 4. Értékhez képest ellenőrzés
console.log('Nem üres:', tokenValue.length > 0);
console.log('Nagyobb mint 32 char:', tokenValue.length >= 32);
```

**Kimenet**:
```
Token érték: 3a7f2c5e9b1d4f8a6c2e1b9d3f7a5c8e9b1d4f8a6c2e1b9d3f7a5c8e9b1d4f
Token hossz: 64
Helyes formátum (64 hex char): true
Nem üres: true
Nagyobb mint 32 char: true
```

**Ellenőrizendő**:
- [ ] Token hossza 64 karakter
- [ ] Csupa hexadecimal (0-9, a-f)
- [ ] Nem üres érték

---

## 3. TESZT MÓDSZER 3: Token Frissítés

### Cél
Token frissítésének ellenőrzése oldal újratöltéskor

### Lépések

```
1. worksheets/edit.php?id=1 megnyitása
2. Chrome DevTools -> Console
3. Primeira token lekérése:
   const token1 = document.querySelector('input[name="csrf_token"]').value;
   console.log('Token 1:', token1);
4. F5 (Oldal újratöltés)
5. Második token lekérése:
   const token2 = document.querySelector('input[name="csrf_token"]').value;
   console.log('Token 2:', token2);
6. Összehasonlítás:
   console.log('Különbözőek?', token1 !== token2);
```

**Ellenőrizendő**:
- [ ] Token 1 != Token 2 (különbözőek)
- [ ] Token értékek véletlenszer (random)

---

## 4. TESZT MÓDSZER 4: POST Submission

### Cél
CSRF token validációjának tesztelése helyes POST kéréskor

### Lépések

```
1. worksheets/edit.php?id=1 megnyitása
2. Form adatok kitöltése:
   - Company: Válasszon egy céget
   - Worksheet number: Auto (nem szerkeszthető)
   - Work date: 2025-11-10
   - Work hours: 8.00
   - Status: Aktív
3. "Save" gomb kattintása
4. Feldolgozás

Ellenőrzendő:
- Oldal a list.php-ra átirányít
- Flash üzenet: "A munkalap sikeresen módosítva!"
- Munkalap frissítve az adatbázisban
```

**Teszt Status**:
- [ ] Form submit siker
- [ ] Adatok elmentve
- [ ] Flash üzenet megjelent

---

## 5. TESZT MÓDSZER 5: Manual CSRF Attack (Szimulációs)

### Cél
CSRF token hiányának tesztelése (szimulált támadás)

### Lépések

#### 5.1 curl-lel POST (token nélkül)

```bash
# Terminal megnyitása (Windows: cmd, Mac/Linux: terminal)

# 1. Session cookie lekérése (login után)
curl -c cookies.txt -b cookies.txt \
     -X GET http://localhost/munkalap-app/worksheets/list.php

# 2. Edit POST token NÉLKÜL
curl -b cookies.txt \
     -X POST http://localhost/munkalap-app/worksheets/edit.php \
     -d "company_id=1&worksheet_number=WS-001&work_date=2025-11-10&work_hours=8&save=1"

# Ellenőrzendő: Redirect Location header vagy error
# Kimenet: Location: list.php (Redirect!)
```

#### 5.2 Postman-nel POST (token nélkül)

```
1. Postman nyitása
2. Új request: POST http://localhost/munkalap-app/worksheets/edit.php
3. Body tab -> form-data:
   Key: company_id -> Value: 1
   Key: worksheet_number -> Value: WS-001
   Key: work_date -> Value: 2025-11-10
   Key: work_hours -> Value: 8
   Key: save -> Value: 1
4. Headers tab: Cookie: PHPSESSID=<session_id_here>
5. Send

Kimenet:
- Status: 302 (Redirect)
- Location: list.php
```

**Ellenőrizendő**:
- [ ] Request ELUTASÍTVA (redirect vagy error)
- [ ] Adatok NEM mentve
- [ ] Flash üzenet: "Érvénytelen kérés! Token hibás."

---

## 6. TESZT MÓDSZER 6: Token Hamisítás

### Cél
Rossz/hamis token tesztelése

### Lépések

#### 6.1 curl-lel POST (rossz token)

```bash
# 1. Szeszálódott token küldése
curl -b cookies.txt \
     -X POST http://localhost/munkalap-app/worksheets/edit.php \
     -d "company_id=1&worksheet_number=WS-001&work_date=2025-11-10&work_hours=8&csrf_token=00000000000000000000000000000000000000000000000000000000000000000&save=1"

# Ellenőrzendő: Redirect Location header vagy error
# Kimenet: Location: list.php (Redirect!)
```

#### 6.2 Postman-nel POST (rossz token)

```
1. Postman nyitása
2. worksheets/list.php HTML Source lekérése (jó token másolása)
3. Edit form: Jó token másolása
4. Postman Body: Rossz token-t beillesztés:
   Key: csrf_token -> Value: invalidtoken123abc...
5. Send

Kimenet:
- Status: 302 (Redirect)
- Location: list.php
```

**Ellenőrizendő**:
- [ ] Request ELUTASÍTVA
- [ ] Adatok NEM mentva
- [ ] Flash üzenet: "Érvénytelen kérés! Token hibás."

---

## 7. TESZT MÓDSZER 7: Session Timeout Teszt

### Cél
Token/session timeout tesztelése (1 óra után)

### Lépések

```
1. worksheets/edit.php?id=1 megnyitása
2. Form oldal megtekintése (token legenerálódott)
3. Token értékét másolni:
   tokenValue = document.querySelector('input[name="csrf_token"]').value;

4. Böngésző bezárása (session törlés)
5. Böngésző újra megnyitása
6. Munkalap szerkesztésének megkíséreltése:
   - Direktben POST oldal szerkesztéskor:
   - curl -b cookies.txt -X POST ... csrf_token=<régi_token>

7. Ellenőrzés:
   - Session nélküli (auth_check.php): Login oldalra redirect
   - Ha nincs auth: Flash "Kérjük jelentkezzen be!"
```

**Ellenőrizendő**:
- [ ] Session újrakezdet után bejelentkezésre kérelem
- [ ] Régi token már nem érvényes

---

## 8. TESZT MÓDSZER 8: SameSite Cookie Teszt

### Cél
SameSite=Strict cookie beállítás tesztelése

### Lépések

#### 8.1 Cross-Site POST Test

```javascript
// attacker.com-on helyezett код:

<form id="csrf-attack" action="http://localhost/munkalap-app/worksheets/delete.php" method="POST">
    <input type="hidden" name="id" value="1">
    <input type="hidden" name="csrf_token" value="doesnt_matter">
    <input type="hidden" name="delete" value="1">
</form>

<script>
    // Automatikus submit
    document.getElementById('csrf-attack').submit();
</script>

// Eredmény (SameSite=Strict):
// 1. POST request munkalap-app felé
// 2. Cookie NEM küldödik (SameSite=Strict)
// 3. auth_check.php: isLoggedIn() = false
// 4. Redirect: login.php
// 5. Támadás SIKERTELEN!
```

#### 8.2 Chrome DevTools Network Test

```
1. worksheets/list.php megnyitása
2. Chrome DevTools -> Network tab
3. Delete form POST trigger
4. Network-ben POST request keresése (delete.php)
5. Request headers megtekintése:
   - Cookie: PHPSESSID=... (jelen van - Same-site)
6. Cross-site request szimulálása (curl):
   curl -X POST http://localhost/munkalap-app/worksheets/delete.php \
        -d "id=1&delete=1"
7. Ellenőrzés:
   - Cookie nincs (no PHPSESSID header)
   - Request fails (no auth)
```

**Ellenőrizendő**:
- [ ] Same-site POST: Cookie KÜLDÖDIK (normal)
- [ ] Cross-site POST: Cookie NEM KÜLDÖDIK (protected)

---

## TESZTELÉSI FORGATÓKÖNYVEK (Teljes Integrációs Tesztek)

### Forgatókönyv 1: Teljes Munkalap Szerkesztés

**Cél**: Létrehozás, szerkesztés, törlés teljes folyamata CSRF védelemmel

```
1. BEJELENTKEZÉS
   ✓ Login oldal megtekintése
   ✓ Hitelesítés sikeres

2. ÚJ MUNKALAP (add.php)
   ✓ add.php megnyitása
   ✓ CSRF token meglét ellenőrzése
   ✓ Form adatok kitöltése:
     - Company: "Test Corp"
     - Work date: 2025-11-10
     - Work hours: 8
     - Description: "Test worksheet"
   ✓ Save kattintása
   ✓ Adatok elmentve (DB check)
   ✓ Flash message: "sikeresen létrehozva"
   ✓ Redirect list.php-ra

3. MUNKALAP SZERKESZTÉS (edit.php)
   ✓ edit.php?id=<új_id> megnyitása
   ✓ CSRF token meglét ellenőrzése
   ✓ Form adatok módosítása:
     - Work hours: 10 (korábban 8)
     - Description: "Updated test"
   ✓ Save kattintása
   ✓ Adatok frissítve (DB check)
   ✓ Flash message: "sikeresen módosítva"
   ✓ Redirect list.php-ra

4. MUNKALAP TÖRLÉS (delete.php)
   ✓ list.php megnyitása
   ✓ Munkalap Törlés gomb kattintása
   ✓ Modal megnyitása
   ✓ CSRF token meglét ellenőrzése delete form-ban
   ✓ "Törlés megerősítése" kattintása
   ✓ Adatok törlve (DB check)
   ✓ Flash message: "sikeresen törölve"
   ✓ Redirect list.php-ra

Végeredmény: PASS (teljes CSRF védelem működik)
```

### Forgatókönyv 2: CSRF Attack Szimulációja

**Cél**: CSRF támadás blokkolásának verifikálása

```
1. FELHASZNÁLÓ BEJELENTKEZIK
   ✓ Munkalap app bejelentkezés
   ✓ Session aktív

2. TÁMADÓ OLDAL MEGNYITÁSA
   ✓ attacker.com megnyitása (másik tab)
   ✓ Rejtett CSRF form: <form action="...delete.php" method="POST">

3. CSRF TÁMADÁS SZIMULÁLÁSA
   ✓ Auto-submit vagy manual trigger
   ✓ Munkalap-app-ba POST kérés (token nélkül)

4. VÉDELEM ELLENŐRZÉSE
   ✓ Cookie NEM küldödik (SameSite=Strict)
   VAGY
   ✓ Támadás HTTP_REFERER alapján blokkolva
   VAGY
   ✓ Token hiánya miatt elutasítva

5. VÉGEREDMÉNY
   ✓ Munkalap NEM törlódik
   ✓ Támadás SIKERTELEN

Végeredmény: PASS (CSRF támadás blokkolva)
```

### Forgatókönyv 3: XSS Attack Szimulációja (HttpOnly Védelem)

**Cél**: HttpOnly cookie biztonsága tesztelése

```
1. VULNERABLE FIELD AZONOSÍTÁSA
   ✓ Description field: <textarea name="description">
   ✓ User input: <img src=x onerror='alert("XSS")'>

2. XSS PAYLOAD BEKÜLDÉSE
   ✓ Munkalap szerkesztés formájában payload beillesztése
   ✓ Save kattintása

3. PAYLOAD TÁROLÁSA (DB-ben)
   ✓ Adatok elmentve payload-dal

4. PAYLOAD MEGTEKINTÉSE
   ✓ edit.php?id=<id> újra megnyitása
   ✓ Textarea megtekintése

5. VÉDELEM ELLENŐRZÉSE
   ✓ escape() függvény: htmlspecialchars()
   ✓ Payload HTML-ként renderel: &lt;img src=x ...&gt;
   ✓ JS NEM végrehajt (XSS BLOKKOLVA)

6. SESSION COOKIE BIZTONSÁGA
   ✓ JavaScript: document.cookie
   ✓ HttpOnly flag: PHPSESSID NEM látható
   ✓ Csak HTTP header-ben van

Végeredmény: PASS (XSS blokkolva, cookie biztonságos)
```

### Forgatókönyv 4: Session Hijacking Védelem

**Cél**: Ellopott session törlésének ellenőrzése (1 óra timeout)

```
1. BEJELENTKEZÉS
   ✓ Session létrehozva: gc_maxlifetime = 3600 másodperc

2. SESSION LEKÉRÉSE (Simuláció: ellopott session)
   ✓ curl: session cookie másolása (PHPSESSID)
   ✓ Támadó 1 óra múlva próbál használni

3. 1 ÓRA MÚLVA
   ✓ Server: Session garbage collection
   ✓ Session file törlve (3600 mp után)
   ✓ CSRF token: $_SESSION['csrf_token'] már nem létezik

4. TÁMADÓ PRÓBÁLKOZÁSA
   ✓ curl: Régi PHPSESSID + POST kérés
   ✓ Server: $_SESSION üres
   ✓ CSRF token hiánya: validateCsrfToken() -> false
   ✓ Kérés ELUTASÍTVA

Végeredmény: PASS (Session timeout működik)
```

### Forgatókönyv 5: SQL Injection Védelem

**Cél**: SQL injection támadás blokkolásának tesztelése

```
1. GET PARAMETER TÁMADÁSA
   ✓ URL: edit.php?id=1' OR '1'='1'
   ✓ Szűrő: is_numeric() -> false
   ✓ Validáció: Error message
   ✓ Adatbázis lekérdezés NEM végrehajtódik

2. POST PARAMETER TÁMADÁSA
   ✓ company_id=1; DROP TABLE worksheets;--
   ✓ POST feldolgozás: intval(company_id) -> 1
   ✓ SQL injection payload ELTÁVOLÍTÓDIK
   ✓ Only: UPDATE worksheets SET company_id=1...

3. MATERIAL VALIDATION
   ✓ quantity=999999999999999999999999999
   ✓ Validáció: is_numeric() + floatval()
   ✓ Konverziók: float érték
   ✓ DB: Biztonságos tárolás

Végeredmény: PASS (SQL injection blokkolva)
```

### Forgatókönyv 6: Authorization Bypass

**Cél**: Auth check-ek verifikálása

```
1. LOGOUT ÁLLAPOT
   ✓ worksheets/edit.php?id=1 elérési kísérlete
   ✓ auth_check.php: isLoggedIn() check
   ✓ Result: Redirect login.php-ra
   ✓ Hibaüzenet: "Kérjük jelentkezzen be!"

2. DIREKTEN HANDLER ELÉRÉSE
   ✓ Logout állapot
   ✓ curl POST: worksheets/edit.php (token nélkül)
   ✓ auth_check.php: isLoggedIn() -> false
   ✓ Result: Redirect login.php-ra

3. ROSSZUL FORMATÁLT ID
   ✓ edit.php?id=abc123
   ✓ is_numeric() -> false
   ✓ Error: "Érvénytelen munkalap azonosító!"
   ✓ Redirect list.php-ra

Végeredmény: PASS (Authorization működik)
```

---

## GYORS ELLENŐRZŐLISTA (Quick Check)

Napi vagy tesztelés előtti gyors ellenőrzés:

```
[ ] CSRF Token
    [ ] Form-ban jelen van
    [ ] 64 karakter hosszú
    [ ] Hexadecimal érték
    [ ] Oldal újratöltéskor változik

[ ] Session Biztonsági Beállítások
    [ ] session.cookie_httponly = 1
    [ ] session.cookie_samesite = Strict
    [ ] session.gc_maxlifetime = 3600
    [ ] session.cookie_lifetime = 0

[ ] POST Validáció
    [ ] Token check (isset + validateCsrfToken)
    [ ] Token hiba -> Flash message
    [ ] Token hiba -> Redirect list.php
    [ ] Feldolgozás csak token OK után

[ ] SQL Injection Védelem
    [ ] GET id: is_numeric() + intval()
    [ ] POST company_id: is_numeric() + intval()
    [ ] POST dates: regex validation
    [ ] POST numbers: floatval() conversion

[ ] XSS Védelem
    [ ] User input escape() függvény
    [ ] Flash messages escape()
    [ ] Table cellák escape()
    [ ] Modal szövegek escape()

[ ] Authorization
    [ ] auth_check.php include
    [ ] isLoggedIn() check
    [ ] Redirect login.php ha nem auth

[ ] HTTP Method Check
    [ ] DELETE only POST accepts
    [ ] GET reads allowed
    [ ] Helytelen metódusra error
```

---

## Tesztelési Függőségek

### Eszközök
- Chrome / Firefox (DevTools)
- curl (command line)
- Postman (API testing)

### Szükséges állomások
- PHP server (localhost:80 vagy custom port)
- MySQL adatbázis (adatok tárolás)
- Munkalap App telepítve és működő

### Szükséges adatok
- Teszt user account (bejelentkezéshez)
- Teszt munkalap (szerkesztéshez)
- Teszt cég (form dropdown-hoz)

---

## Tesztelési Eredmények Rögzítése

Sablontá a tesztelési eredmények dokumentálásához:

```markdown
## Test Date: 2025-11-10
## Tester: QA Team
## Test Environment: Development (localhost)

### Test Case: TC-001 CSRF Token Presence
- Result: PASS
- Token found in form: YES
- Token length: 64 characters
- Token format: Hexadecimal
- Notes: All forms contain CSRF tokens

### Test Case: TC-002 CSRF Token Validation
- Result: PASS
- Valid token submission: ACCEPTED
- Missing token: BLOCKED
- Invalid token: BLOCKED
- Notes: Validation working correctly

### Test Case: TC-003 Session Security
- Result: PASS
- HttpOnly flag: SET
- SameSite: Strict
- Timeout: 1 hour
- Notes: All security headers present

### Summary
- Tests Executed: 6
- Tests Passed: 6
- Tests Failed: 0
- Coverage: 100%
- Status: APPROVED FOR PRODUCTION
```

---

## Referencia

- OWASP CSRF Prevention: https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html
- PHP Session Security: https://www.php.net/manual/en/session.security.php
- Hash Equals Timing Attack: https://www.php.net/manual/en/function.hash-equals.php
