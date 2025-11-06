# TCPDF Telepítési útmutató

## 1. Composer telepítése

Ha még nincs telepítve a Composer:

1. Látogasd meg: https://getcomposer.org/download/
2. Töltsd le és telepítsd a Composer-t

Vagy használd a Composer telepítőt PowerShell-ben (Windows):
```powershell
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

## 2. TCPDF telepítése Composer-rel

Nyisd meg a PowerShell vagy Command Prompt-ot, navigálj a projekt mappájába:

```bash
cd C:\xampp\htdocs\munkalap-app
composer install
```

Ez automatikusan telepíti a TCPDF könyvtárat a `vendor` mappába.

## 3. Ellenőrzés

A telepítés után a következő fájloknak kell lennie:
- `vendor/autoload.php`
- `vendor/tecnickcom/tcpdf/`

## 4. PDF generálás tesztelése

1. Jelentkezz be az alkalmazásba
2. Menj a Munkalapok listához
3. Kattints a PDF gombra egy munkalapon
4. A PDF új ablakban kell megnyíljon

## Alternatív módszer: TCPDF manuális telepítés

Ha nem használsz Composer-t:

1. Látogasd meg: https://github.com/tecnickcom/TCPDF
2. Töltsd le a TCPDF-t (Clone vagy Download ZIP)
3. Csomagold ki és másold be a `vendor/tecnickcom/tcpdf` mappába
4. Hozz létre egy egyszerű autoload.php fájlt:

```php
<?php
// vendor/autoload.php
require_once __DIR__ . '/tecnickcom/tcpdf/tcpdf.php';
```

## Hibaelhárítás

Ha a PDF generálás nem működik:

1. Ellenőrizd, hogy a `vendor/autoload.php` létezik-e
2. Ellenőrizd a PHP verziót (7.4 vagy újabb szükséges)
3. Ellenőrizd a hibaüzeneteket a PHP error log-ban
4. Győződj meg róla, hogy a TCPDF fájlok megfelelően telepítve vannak


