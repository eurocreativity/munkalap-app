# TCPDF Manuális Telepítés (Composer nélkül)

Ha nem szeretnéd telepíteni a Composer-t, TCPDF-t manuálisan is telepítheted.

## 1. TCPDF letöltése

1. Látogasd meg: https://github.com/tecnickcom/TCPDF/releases
2. Töltsd le a legfrissebb ZIP fájlt (pl: `TCPDF-main.zip`)
3. Csomagold ki a ZIP-et

## 2. TCPDF bemásolása a projektbe

1. Hozz létre egy `vendor` mappát a projekt gyökerében (ha még nincs)
2. Hozz létre egy `vendor/tecnickcom` mappát
3. Másold be a kicsomagolt TCPDF mappát ide: `vendor/tecnickcom/tcpdf`

A végleges struktúrának így kell kinéznie:
```
munkalap-app/
├── vendor/
│   └── tecnickcom/
│       └── tcpdf/
│           ├── tcpdf.php
│           ├── config/
│           ├── fonts/
│           └── ...
```

## 3. Autoload fájl létrehozása

Hozz létre egy `vendor/autoload.php` fájlt a következő tartalommal:

```php
<?php
// Egyszerű autoload TCPDF-hez
if (!class_exists('TCPDF')) {
    require_once __DIR__ . '/tecnickcom/tcpdf/tcpdf.php';
}
```

## 4. Ellenőrzés

Nyisd meg a böngészőben: `http://localhost/munkalap-app/worksheets/list.php`

Ha egy munkalap PDF-jét megnyitod és működik, akkor sikeresen telepítetted!

## Alternatív: TCPDF letöltése PowerShell-lel (Windows)

Ha van PowerShell hozzáférésed, használd ezt a parancsot a projekt mappájában:

```powershell
# Mappák létrehozása
New-Item -ItemType Directory -Force -Path "vendor\tecnickcom\tcpdf"

# TCPDF letöltése GitHub-ról
Invoke-WebRequest -Uri "https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip" -OutFile "tcpdf.zip"

# Kicsomagolás
Expand-Archive -Path "tcpdf.zip" -DestinationPath "temp" -Force
Move-Item -Path "temp\TCPDF-main\*" -Destination "vendor\tecnickcom\tcpdf\" -Force
Remove-Item -Path "temp" -Recurse -Force
Remove-Item -Path "tcpdf.zip" -Force

# Autoload létrehozása
@"
<?php
// Egyszerű autoload TCPDF-hez
if (!class_exists('TCPDF')) {
    require_once __DIR__ . '/tecnickcom/tcpdf/tcpdf.php';
}
"@ | Out-File -FilePath "vendor\autoload.php" -Encoding UTF8

Write-Host "TCPDF sikeresen telepítve!"
```


