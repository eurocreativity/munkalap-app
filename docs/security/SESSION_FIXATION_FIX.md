# Session Fixation Sebezhetőség Javítása

## Probléma azonosítása

**Sebezhetőség típusa:** CWE-384 Session Fixation
**Súlyosság:** KRITIKUS
**Érintett fájl:** `login.php`

### Mi a Session Fixation támadás?

A Session Fixation támadás során a támadó előre beállít egy session azonosítót (session ID-t), majd ráveszi az áldozatot, hogy ezzel a session ID-val jelentkezzen be. Sikeres bejelentkezés után a támadó átveheti a kontrollt a bejelentkezett munkamenet felett, mivel ismeri a session ID-t.

### Támadási forgatókönyv

1. **Támadó előkészíti a session ID-t:**
   ```
   https://example.com/login.php?PHPSESSID=attacker_session_id
   ```

2. **Áldozat bejelentkezik** ezzel a session ID-val (pl. phishing link)

3. **Támadó átveszi a sessiont** ugyanazzal a session ID-val:
   ```php
   session_id('attacker_session_id');
   session_start();
   // Átveszi az áldozat bejelentkezett sessionjét!
   ```

## Implementált megoldás

### Módosított fájl: `login.php`

**Változtatás helye:** Sikeres bejelentkezés után, session változók beállítása előtt

**Előtte (SEBEZHETŐ):**
```php
if ($user && password_verify($password, $user['password'])) {
    // Sikeres bejelentkezés
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    // ...
}
```

**Utána (BIZTONSÁGOS):**
```php
if ($user && password_verify($password, $user['password'])) {
    // Session fixation elleni védelem - új session ID generálása
    // CWE-384 mitigation: új session azonosító generálása sikeres autentikáció után
    session_regenerate_id(true);

    // Sikeres bejelentkezés - session változók beállítása
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    // ...
}
```

### Kulcs funkció: `session_regenerate_id(true)`

**Paraméterek:**
- `true` - Törli a régi session fájlt a szerverről
- `false` - Megtartja a régi session fájlt (NEM AJÁNLOTT!)

**Működés:**
1. Új session ID generálása
2. Session adatok átmásolása az új ID alá
3. Régi session fájl törlése (ha `true`)
4. Cookie frissítése az új session ID-val

## Biztonsági hatás

### Előnyök

1. **Támadás meghiúsítása:**
   - A támadó által ismert régi session ID érvénytelenné válik
   - Új, ismeretlen session ID generálódik

2. **Automatikus védelem:**
   - Minden sikeres bejelentkezésnél aktiválódik
   - Nincs szükség felhasználói beavatkozásra

3. **Session tisztaság:**
   - Régi session adatok törlése
   - Friss session indítása

### Korlátozások

- Nem véd session hijacking ellen (más védelem szükséges)
- Nem véd XSS támadás ellen (input validáció szükséges)
- Nem véd CSRF ellen (CSRF token szükséges - már implementálva)

## Tesztelés

### Manuális teszt

1. **Kiinduló állapot:**
   - Nyisd meg: `http://localhost/munkalap-app/test_session_fixation.php`
   - Jegyezd meg a Session ID-t (pl. `abc123def456`)

2. **Bejelentkezés:**
   - Jelentkezz be: `login.php`
   - Használd a teszt felhasználókat: `admin / admin123`

3. **Ellenőrzés:**
   - Frissítsd a `test_session_fixation.php` oldalt
   - Session ID megváltozott? (pl. `xyz789ghi012`)
   - ✅ PASS: Session ID megváltozott
   - ❌ FAIL: Session ID ugyanaz

### Developer Tools ellenőrzés

**Chrome/Edge/Firefox:**
1. F12 > Application/Storage > Cookies
2. Keresd a `PHPSESSID` cookie-t
3. Jegyezd meg az értéket bejelentkezés előtt
4. Jelentkezz be
5. Ellenőrizd, hogy az érték megváltozott

**Konzol parancs:**
```javascript
// Bejelentkezés előtt
console.log('Előtte:', document.cookie);

// Bejelentkezés után
console.log('Utána:', document.cookie);
```

### Automatizált teszt (Playwright)

```javascript
test('Session ID változik bejelentkezés után', async ({ page, context }) => {
    await page.goto('http://localhost/munkalap-app/login.php');

    // Session ID előtte
    const cookiesBefore = await context.cookies();
    const sessionBefore = cookiesBefore.find(c => c.name === 'PHPSESSID');

    // Bejelentkezés
    await page.fill('#username', 'admin');
    await page.fill('#password', 'admin123');
    await page.click('button[name="login"]');

    // Session ID utána
    const cookiesAfter = await context.cookies();
    const sessionAfter = cookiesAfter.find(c => c.name === 'PHPSESSID');

    // Ellenőrzés
    expect(sessionAfter.value).not.toBe(sessionBefore.value);
});
```

## Compliance és szabványok

### OWASP Top 10
- **A07:2021 - Identification and Authentication Failures**
- Session fixation a hitelesítési hibák kategóriába tartozik

### CWE (Common Weakness Enumeration)
- **CWE-384:** Session Fixation
- **Kapcsolódó:** CWE-287 (Improper Authentication)

### PHP Best Practices
- [PHP Security Guide - Session Management](https://www.php.net/manual/en/features.session.security.management.php)
- Ajánlás: `session_regenerate_id()` használata jogosultság változásnál

## További ajánlások

### 1. Session timeout implementálása
```php
// config.php-ban már implementálva
$session_lifetime = 3600; // 1 óra
if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    redirect('login.php');
}
$_SESSION['last_activity'] = time();
```

### 2. Secure cookie beállítások
```php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,      // Csak HTTPS-en
    'httponly' => true,    // JavaScript nem érheti el
    'samesite' => 'Strict' // CSRF védelem
]);
```

### 3. Session fixation védelem kijelentkezéskor is
```php
// logout.php
session_regenerate_id(true);
session_unset();
session_destroy();
```

## Ellenőrzési lista

- [x] `session_regenerate_id(true)` hozzáadva `login.php`-hoz
- [x] Sikeres autentikáció után kerül meghívásra
- [x] Session változók beállítása UTÁN történik
- [x] `true` paraméter használata (régi session törlése)
- [x] Teszt script létrehozva (`test_session_fixation.php`)
- [x] Dokumentáció elkészítve
- [x] Manuális teszt elvégezve
- [ ] Automatizált teszt létrehozva (opcionális)

## Hivatkozások

- [OWASP Session Fixation](https://owasp.org/www-community/attacks/Session_fixation)
- [CWE-384](https://cwe.mitre.org/data/definitions/384.html)
- [PHP session_regenerate_id()](https://www.php.net/manual/en/function.session-regenerate-id.php)
- [OWASP Session Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)

## Változtatás dátuma

**Dátum:** 2025-11-10
**Verzió:** 1.0
**Szerző:** Claude Code
**Review státusz:** Pending
