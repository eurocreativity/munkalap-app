# Session Timeout Implementáció - Összefoglaló

## Implementált Változtatások

### 1. config.php - Session Security Flags

**Fájl:** `c:\xampp\htdocs\munkalap-app\config.php`

**Változtatások (12-33. sor):**

```php
// Session biztonsági beállítások és indítás
if (session_status() === PHP_SESSION_NONE) {
    // Session timeout: 1 óra (3600 másodperc)
    ini_set('session.gc_maxlifetime', 3600);

    // Session cookie csak böngésző bezárásig
    ini_set('session.cookie_lifetime', 0);

    // HttpOnly flag - JavaScript nem férhet hozzá (XSS védelem)
    ini_set('session.cookie_httponly', 1);

    // Secure flag - csak HTTPS-en (production-ban)
    // Development-ben (localhost) kikapcsolva, production-ban bekapcsolva
    if (!in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1'])) {
        ini_set('session.cookie_secure', 1);
    }

    // SameSite Strict - CSRF védelem
    ini_set('session.cookie_samesite', 'Strict');

    session_start();
}
```

### 2. includes/auth_check.php - Session Timeout Ellenőrzés

**Fájl:** `c:\xampp\htdocs\munkalap-app\includes\auth_check.php`

**Változtatások (13-35. sor):**

```php
// Session timeout ellenőrzés
if (isLoggedIn()) {
    // Ha már volt last_activity és lejárt (1 óra = 3600 sec)
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > 3600)) {
        // Session lejárt - flash message ELŐBB, mielőtt destroy-oljuk
        setFlashMessage('warning', 'A munkamenet lejárt biztonsági okokból. Kérjük, jelentkezz be újra!');

        // Session megsemmisítése
        session_unset();
        session_destroy();

        // Új session indítása a flash message számára
        session_start();
        setFlashMessage('warning', 'A munkamenet lejárt biztonsági okokból. Kérjük, jelentkezz be újra!');

        redirect('login.php');
        exit();
    }

    // Frissítjük a last_activity időt
    $_SESSION['last_activity'] = time();
}
```

### 3. login.php - last_activity Inicializálás

**Fájl:** `c:\xampp\htdocs\munkalap-app\login.php`

**Változtatás (39. sor):**

```php
$_SESSION['last_activity'] = time(); // Session timeout tracking
```

## Létrehozott Fájlok

### 1. Test Fájlok

- **test_session_timeout.php** - Részletes session timeout teszt
- **test_session_quick.php** - 60 másodperces gyors timeout demo

### 2. Dokumentáció

- **docs/SECURITY_SESSION_TIMEOUT.md** - Teljes biztonsági dokumentáció
- **docs/SESSION_TIMEOUT_IMPLEMENTATION.md** - Ez a fájl

## Tesztelési Lépések

### A. Alapvető Működés Tesztelése

1. **Navigálj a teszt oldalra:**
   ```
   http://localhost/munkalap-app/test_session_timeout.php
   ```

2. **Ellenőrizd a session konfigurációt:**
   - gc_maxlifetime: 3600 sec ✓
   - cookie_httponly: Enabled ✓
   - cookie_samesite: Strict ✓

3. **Jelentkezz be:**
   ```
   Username: admin
   Password: admin123
   ```

4. **Ellenőrizd a last_activity időbélyeget:**
   - Menj vissza a test oldalra
   - Látni fogod a "Last Activity" időt
   - Frissítsd az oldalt - az idő frissül

### B. Timeout Működés Tesztelése (Gyors - 60 sec)

1. **Nyisd meg:**
   ```
   http://localhost/munkalap-app/test_session_quick.php
   ```

2. **Figyeld a countdown-t:**
   - 60 másodperces visszaszámláló
   - NE frissítsd az oldalt

3. **60 másodperc után:**
   - "TIMEOUT!" üzenet jelenik meg
   - Ez szimulálja az 1 órás timeout-ot

### C. Éles Timeout Teszt (1 óra)

**FIGYELEM:** Ez az éles timeout teszt!

1. **Bejelentkezés:**
   ```
   http://localhost/munkalap-app/login.php
   ```

2. **Navigálj egy védett oldalra:**
   ```
   http://localhost/munkalap-app/dashboard.php
   ```

3. **Várj 1 órát** (vagy módosítsd átmenetileg az auth_check.php-ben 60 sec-re)

4. **Próbálj meg egy védett oldalt meglátogatni:**
   - Dashboard vagy worksheets/list.php
   - Átirányítás login.php-ra
   - Flash message: "A munkamenet lejárt biztonsági okokból..."

### D. Cookie Security Flags Ellenőrzése

1. **Developer Tools (F12):**
   - Application > Cookies > http://localhost

2. **PHPSESSID cookie ellenőrzése:**
   - HttpOnly: ✓ (pipálva kell legyen)
   - SameSite: Strict
   - Secure: ✗ (localhost-on nem, production-ban igen)

3. **Console teszt (XSS védelem):**
   ```javascript
   console.log(document.cookie);
   // PHPSESSID NEM jelenik meg (HttpOnly védelem)
   ```

## Módosított Timeout Érték (Teszteléshez)

Ha gyorsabb tesztelést szeretnél (pl. 60 sec):

**auth_check.php módosítás:**

```php
// EREDETILEG:
if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity'] > 3600)) {

// TESZTELÉSHEZ (60 sec):
if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity'] > 60)) {

// NE FELEJTSD EL VISSZAÁLLÍTANI 3600-ra!
```

## Biztonsági Előnyök

| Sebezhetőség | Előtte | Utána | Védelem |
|--------------|--------|-------|---------|
| CWE-613 Insufficient Session Expiration | ✗ Örökké élő session-ök | ✓ 1 órás timeout | Nyilvános gépeken hagyott session-ök |
| XSS (JavaScript cookie lopás) | ✗ Nincs védelem | ✓ HttpOnly flag | JavaScript nem éri el a session cookie-t |
| CSRF | Részleges (csak token) | ✓ Token + SameSite | Keresztdomain kérések blokkolása |
| Man-in-the-Middle | ✗ HTTP-n is megy | ✓ Secure flag (prod) | Cookie csak HTTPS-en |

## Ellenőrzési Lista

Implementáció után ellenőrizd:

- [x] config.php: Session security flag-ek beállítva
- [x] auth_check.php: Timeout ellenőrzés implementálva
- [x] login.php: last_activity inicializálva
- [x] test_session_timeout.php: Teszt fájl létrehozva
- [x] test_session_quick.php: Gyors demo készítve
- [x] Dokumentáció: SECURITY_SESSION_TIMEOUT.md
- [ ] Tesztelés: 1 órás timeout működik
- [ ] Tesztelés: Flash message megjelenik
- [ ] Tesztelés: HttpOnly flag aktív
- [ ] Tesztelés: SameSite Strict működik

## Production Deployment

Production környezetbe való telepítés előtt:

1. **HTTPS konfigurálása:**
   - SSL tanúsítvány telepítése
   - Apache/Nginx HTTPS konfiguráció
   - HTTP -> HTTPS átirányítás

2. **Session security ellenőrzése:**
   ```php
   // config.php production ellenőrzés
   ini_get('session.cookie_secure'); // = 1 (HTTPS)
   ini_get('session.cookie_httponly'); // = 1
   ini_get('session.cookie_samesite'); // = Strict
   ```

3. **Timeout érték konfirmálása:**
   - 3600 sec (1 óra) megfelelő-e?
   - Bizonyos alkalmazások 30 perc (1800 sec)
   - Érzékeny adatok esetén rövidebb

4. **Teszt fájlok törlése:**
   ```bash
   # Production-ban töröld:
   rm test_session_timeout.php
   rm test_session_quick.php
   ```

## Hibaelhárítás

### "A munkamenet lejárt..." üzenet nem jelenik meg

**Probléma:** Flash message elvész a session_destroy() után

**Megoldás:** Ellenőrizd az auth_check.php-t:
```php
// Session megsemmisítése UTÁN újra indítjuk
session_start();
setFlashMessage('warning', '...');
```

### Timeout nem működik

**Ellenőrzés:**
1. last_activity beállítva van-e login-nál?
2. auth_check.php be van-e töltve minden védett oldalon?
3. session.gc_maxlifetime megfelelő?

### HttpOnly flag nem működik

**Probléma:** JavaScript eléri a cookie-t

**Ellenőrzés:**
```php
// config.php
ini_get('session.cookie_httponly'); // = 1 kell legyen
```

## Kapcsolódó Dokumentumok

- [SECURITY_SESSION_TIMEOUT.md](./SECURITY_SESSION_TIMEOUT.md) - Részletes biztonsági dokumentáció
- [CSRF_PROTECTION.md](./CSRF_PROTECTION.md) - CSRF védelem dokumentáció
- [SESSION_FIXATION.md](./SESSION_FIXATION.md) - Session fixation védelem

## Változtatások

| Dátum | Verzió | Változás |
|-------|--------|----------|
| 2025-11-10 | 1.0.0 | Kezdeti implementáció |

## Kérdések / Támogatás

Problémák esetén:
- Ellenőrizd a session konfigurációt: test_session_timeout.php
- Nézd meg a teljes dokumentációt: SECURITY_SESSION_TIMEOUT.md
- Teszteld a gyors demo-val: test_session_quick.php
