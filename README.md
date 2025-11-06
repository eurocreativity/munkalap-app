# Munkalap Kezelő Webalkalmazás

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

## Mi ez a projekt?

Ez egy munkalap kezelő webalkalmazás PHP nyelven, amely lehetővé teszi munkalapok létrehozását, kezelését és követését. Az alkalmazás tartalmazza a cégkezelést, munkalap kezelést, anyagfelhasználás nyilvántartást, PDF generálást és havi zárást email küldéssel.

## Telepítés

### Előfeltételek

- XAMPP telepítve és fut (Apache + MySQL)
- PHP 7.4 vagy újabb
- MySQL/MariaDB adatbázis szerver

### Lépések

1. Győződj meg róla, hogy a projekt mappája a helyes helyen van:
   ```
   C:\xampp\htdocs\munkalap-app
   ```

2. Indítsd el az XAMPP Control Panel-t és indítsd el az Apache és MySQL szolgáltatásokat.

3. Nyisd meg a böngészőt és menj a következő címre:
   ```
   http://localhost/munkalap-app/
   ```

4. Teszteld az adatbázis kapcsolatot:
   ```
   http://localhost/munkalap-app/test_db.php
   ```

## Célja

Az alkalmazás célja, hogy:

- Munkalapokat lehessen létrehozni és kezelni
- Munkalapokhoz tartozó információk nyomon követése
- Felhasználók kezelése
- Egyszerű és könnyen használható felület biztosítása

## Főbb funkciók

✅ **Felhasználókezelés**
- Bejelentkezés/kijelentkezés
- Session kezelés
- Biztonságos jelszó tárolás

✅ **Cégkezelés**
- Cégek CRUD műveletei
- Cég adatok kezelése

✅ **Munkalap kezelés**
- Munkalapok létrehozása, szerkesztése, törlése
- Automatikus munkalap számozás (év/sorszám)
- Munka típusok: Helyi/Távoli
- Díjazás típusok: Átalány/Eseti
- Anyagfelhasználás kezelése
- PDF generálás

✅ **Havi zárás**
- Cégenkénti összesítő
- PDF generálás havi összesítőhöz
- Email küldés PDF csatolmánnyal
- Teszt mód email küldéshez

✅ **Beállítások**
- Saját cég adatai
- Email beállítások (SMTP)
- Alapértelmezett értékek

## Projekt struktúra

```
munkalap-app/
├── classes/              # Osztályok
│   ├── Company.php       # Cég osztály
│   ├── Database.php      # Adatbázis osztály
│   ├── Material.php      # Anyag osztály
│   ├── Settings.php      # Beállítások osztály
│   └── Worksheet.php     # Munkalap osztály
├── companies/            # Cégkezelés oldalak
│   ├── add.php
│   ├── edit.php
│   ├── delete.php
│   └── list.php
├── worksheets/           # Munkalap oldalak
│   ├── add.php
│   ├── list.php
│   └── pdf.php
├── monthly/              # Havi zárás
│   ├── close.php
│   └── summary_pdf.php
├── includes/             # Közös fájlok
│   ├── auth_check.php
│   └── email.php
├── config.php            # Konfiguráció
├── install.php           # Telepítő
├── settings.php          # Beállítások
└── README.md
```

## Telepítési lépések

### 1. Adatbázis telepítés

1. Nyisd meg: `http://localhost/munkalap-app/install.php`
2. Kattints a "Telepítés indítása" gombra
3. Az adatbázis és táblák automatikusan létrejönnek

### 2. TCPDF telepítés

**Opció A: PowerShell script (ajánlott)**
```powershell
.\install_tcpdf_manual.ps1
```

**Opció B: Composer**
```bash
composer install
```

**Opció C: Manuális telepítés**
Lásd: `MANUAL_TCPDF_INSTALL.md`

### 3. Bejelentkezés

- URL: `http://localhost/munkalap-app/login.php`
- Teszt felhasználók:
  - `admin` / `admin123`
  - `user` / `user123`

## GitHub

A projekt Git repository-ként inicializálva van. GitHub-ra feltöltéshez lásd: `GITHUB_SETUP.md`

## További információk

- **Telepítési útmutató**: `INSTALL_TCPDF.md`
- **TCPDF manuális telepítés**: `MANUAL_TCPDF_INSTALL.md`
- **GitHub feltöltés**: `GITHUB_SETUP.md`

## Technológiai stack

- **Backend**: PHP 7.4+
- **Adatbázis**: MySQL/MariaDB
- **PDF generálás**: TCPDF
- **Frontend**: Bootstrap 5
- **Icons**: Bootstrap Icons


