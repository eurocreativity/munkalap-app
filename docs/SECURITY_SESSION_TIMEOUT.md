# Session Timeout és Security Flags Implementáció

## Áttekintés

Ez a dokumentum leírja a CWE-613 Insufficient Session Expiration sebezhetőség kijavítását és a session biztonsági fejlesztéseket.

## Probléma Leírása

### CWE-613: Insufficient Session Expiration

**Eredeti probléma:**
- Session-ök örökké éltek, nincs automatikus timeout
- Nyilvános gépeken hagyott munkamenetek kihasználhatók
- Biztonsági kockázat: Session hijacking lehetősége

**Példa támadási szcenárió:**
1. Felhasználó bejelentkezik egy nyilvános számítógépen (pl. internet kávézó)
2. Elfelejt kijelentkezni
3. A következő felhasználó továbbra is hozzáfér a session-höz
4. Bizalmas adatok elérhetők

## Megoldás

### 1. Session Security Flags (config.php)

**Fájl:** `c:\xampp\htdocs\munkalap-app\config.php`

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

#### Security Flag-ek Magyarázata:

| Flag | Érték | Védelem | Magyarázat |
|------|-------|---------|------------|
| `gc_maxlifetime` | 3600 sec | Session lejárat | PHP garbage collector 1 óra után törli a session fájlokat |
| `cookie_lifetime` | 0 | Session persistence | Cookie csak böngésző bezárásig él |
| `cookie_httponly` | 1 | XSS védelem | JavaScript nem fér hozzá a session cookie-hoz |
| `cookie_secure` | 1* | Man-in-the-Middle | Cookie csak HTTPS-en küldhető (*production-ban) |
| `cookie_samesite` | Strict | CSRF védelem | Cookie csak ugyanarról a domain-ről küldhető |

### 2. Session Timeout Ellenőrzés (auth_check.php)

**Fájl:** `c:\xampp\htdocs\munkalap-app\includes\auth_check.php`

```php
// Session timeout ellenőrzés
if (isLoggedIn()) {
    // Ha már volt last_activity és lejárt (1 óra = 3600 sec)
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > 3600)) {
        // Session lejárt
        session_unset();
        session_destroy();
        setFlashMessage('warning', 'A munkamenet lejárt biztonsági okokból. Kérjük, jelentkezz be újra!');
        redirect('login.php');
        exit();
    }

    // Frissítjük a last_activity időt
    $_SESSION['last_activity'] = time();
}
```

#### Működési Elv:

1. **last_activity inicializálás:** Login során beállítjuk az aktuális időt
2. **Aktivitás ellenőrzés:** Minden védett oldal betöltésekor ellenőrizzük az eltelt időt
3. **Timeout kezelés:** Ha > 1 óra telt el, session megszüntetése és átirányítás
4. **Activity frissítés:** Minden kérés frissíti a last_activity időt (sliding window)

### 3. Login Inicializálás (login.php)

**Fájl:** `c:\xampp\htdocs\munkalap-app\login.php`

```php
// Sikeres bejelentkezés - session változók beállítása
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email'] = $user['email'];
$_SESSION['last_activity'] = time(); // Session timeout tracking
```

## Tesztelés

### Teszt Fájl

**Fájl:** `c:\xampp\htdocs\munkalap-app\test_session_timeout.php`

A teszt fájl ellenőrzi:
- Session configuration beállításokat
- Current session információkat
- last_activity időbélyeget
- Cookie security flag-eket

### Tesztelési Módszerek

#### A) Normál Timeout Teszt (1 óra)

```
1. Jelentkezz be az alkalmazásba
2. Navigálj egy védett oldalra
3. Várj 1 órát (vagy hagyd nyitva a böngészőt inaktívan)
4. Próbálj meg egy védett oldalt újra meglátogatni
5. Elvárt eredmény: Átirányítás login.php-ra "A munkamenet lejárt..." üzenettel
```

#### B) Gyors Teszt (60 másodperc)

**Ideiglenes módosítás teszteléshez:**

```php
// auth_check.php - csak teszteléshez!
if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity'] > 60)) { // 60 helyett 3600
```

```
1. Módosítsd a timeout-ot 60 sec-re
2. Jelentkezz be
3. Várj 61 másodpercet
4. Próbálj meg egy védett oldalt meglátogatni
5. Elvárt eredmény: Session timeout
6. FONTOS: Állítsd vissza 3600-ra!
```

#### C) Cookie Security Flags Ellenőrzés

Browser Developer Tools-ban (F12):

```
1. Menj az Application/Storage > Cookies fülre
2. Keresd meg a PHPSESSID cookie-t
3. Ellenőrizd:
   - HttpOnly: ✓ (pipa)
   - Secure: ✓ (production-ban) / ✗ (localhost-on)
   - SameSite: Strict
```

### Tesztelési Eredmények

| Teszt | Állapot | Megjegyzés |
|-------|---------|------------|
| Session timeout 1 óra után | ✓ | Sikeres átirányítás login-ra |
| last_activity frissül | ✓ | Minden kérésnél frissül |
| HttpOnly flag | ✓ | JavaScript nem éri el |
| Secure flag (production) | ⚠️ | Csak HTTPS-en |
| SameSite Strict | ✓ | CSRF védelem aktív |
| Flash message megjelenik | ✓ | "A munkamenet lejárt..." |

## Biztonsági Előnyök

### 1. Automatikus Session Lejárat
- **Előny:** Inaktív session-ök automatikusan megszűnnek
- **Védelem:** Nyilvános gépeken hagyott session-ök védelme
- **Compliance:** SOC 2, PCI DSS követelmények teljesítése

### 2. XSS Védelem (HttpOnly)
- **Előny:** JavaScript nem férhet hozzá a session cookie-hoz
- **Védelem:** XSS támadás esetén a session cookie nem lopható el
- **Példa:** `document.cookie` nem tartalmazza a PHPSESSID-t

### 3. CSRF Védelem (SameSite)
- **Előny:** Cookie csak ugyanarról a domain-ről küldhető
- **Védelem:** Cross-site request forgery támadások ellen
- **Példa:** Rosszindulatú site nem tud kérést küldeni a felhasználó session-jével

### 4. Man-in-the-Middle Védelem (Secure)
- **Előny:** Cookie csak titkosított HTTPS kapcsolaton
- **Védelem:** HTTP-n történő lehallgatás ellen
- **Megjegyzés:** Production környezetben kötelező HTTPS

## Konfiguráció

### Timeout Érték Módosítása

Ha más timeout értéket szeretnél:

```php
// config.php
ini_set('session.gc_maxlifetime', 7200); // 2 óra

// auth_check.php
if (time() - $_SESSION['last_activity'] > 7200) { // 2 óra
```

### Session Típusok

| Timeout | Használati Eset |
|---------|-----------------|
| 1800 sec (30 perc) | Érzékeny adatok (bank, egészségügy) |
| 3600 sec (1 óra) | Normál webalkalmazások (jelenlegi) |
| 7200 sec (2 óra) | Hosszabb munkamenetek |
| 28800 sec (8 óra) | Munkanap hosszúság |

## OWASP Megfelelés

### OWASP Top 10 - 2021

| Kategória | Státusz | Implementáció |
|-----------|---------|---------------|
| A01:2021 - Broken Access Control | ✓ | Session timeout ellenőrzés |
| A02:2021 - Cryptographic Failures | ✓ | Secure flag (HTTPS) |
| A03:2021 - Injection | ⚠️ | További SQL injection védelem szükséges |
| A04:2021 - Insecure Design | ✓ | Security-first design |
| A05:2021 - Security Misconfiguration | ✓ | Helyes session config |
| A07:2021 - Identification and Authentication Failures | ✓ | Session management |

### CWE Kategóriák

| CWE ID | Név | Státusz |
|--------|-----|---------|
| CWE-613 | Insufficient Session Expiration | ✓ FIXED |
| CWE-384 | Session Fixation | ✓ FIXED (session_regenerate_id) |
| CWE-79 | Cross-site Scripting (XSS) | ✓ MITIGATED (HttpOnly) |
| CWE-352 | Cross-Site Request Forgery (CSRF) | ✓ MITIGATED (SameSite + Token) |

## Production Checklist

Deployment előtt ellenőrizd:

- [ ] HTTPS konfigurálva a szerveren
- [ ] `session.cookie_secure` = 1 production-ban
- [ ] Session timeout megfelelő (3600 sec)
- [ ] Flash message működik timeout esetén
- [ ] Logout function tisztítja a session-t
- [ ] Database session tárolás (opcionális, de ajánlott)

## Továbbfejlesztési Lehetőségek

### 1. Database Session Storage
```php
// Session adatok DB-ben tárolása
session_set_save_handler(new DatabaseSessionHandler());
```

### 2. Remember Me Funkció
```php
// Biztonságos "Emlékezz rám" implementáció
// Külön token táblával, nem session-nel
```

### 3. Session Hijacking Detection
```php
// User-Agent és IP cím ellenőrzés
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
```

### 4. Concurrent Session Limit
```php
// Maximum 3 egyidejű session / felhasználó
// Session táblában `user_id` és `session_id` tracking
```

## Referenciák

- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [CWE-613: Insufficient Session Expiration](https://cwe.mitre.org/data/definitions/613.html)
- [PHP Session Security](https://www.php.net/manual/en/session.security.php)
- [MDN: Cookie SameSite](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite)

## Változások Története

| Dátum | Verzió | Változás |
|-------|--------|----------|
| 2025-11-10 | 1.0.0 | Kezdeti implementáció - Session timeout és security flags |

## Támogatás

Kérdések esetén:
- Dokumentáció: `docs/SECURITY_SESSION_TIMEOUT.md`
- Teszt fájl: `test_session_timeout.php`
- Konfigurációs fájl: `config.php`
