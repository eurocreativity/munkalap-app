# TCPDF Manuális Telepítési Script
Write-Host "TCPDF telepítése manuálisan..." -ForegroundColor Green

# Mappák létrehozása
Write-Host "Mappák létrehozása..." -ForegroundColor Yellow
New-Item -ItemType Directory -Force -Path "vendor\tecnickcom\tcpdf" | Out-Null

# TCPDF letöltése
Write-Host "TCPDF letöltése GitHub-ról..." -ForegroundColor Yellow
try {
    Invoke-WebRequest -Uri "https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip" -OutFile "tcpdf.zip"
    Write-Host "Letöltés sikeres!" -ForegroundColor Green
} catch {
    Write-Host "Hiba történt a letöltés során: $_" -ForegroundColor Red
    exit 1
}

# Kicsomagolás
Write-Host "Kicsomagolás..." -ForegroundColor Yellow
try {
    Expand-Archive -Path "tcpdf.zip" -DestinationPath "temp" -Force
    Move-Item -Path "temp\TCPDF-main\*" -Destination "vendor\tecnickcom\tcpdf\" -Force
    Remove-Item -Path "temp" -Recurse -Force
    Remove-Item -Path "tcpdf.zip" -Force
    Write-Host "Kicsomagolás sikeres!" -ForegroundColor Green
} catch {
    Write-Host "Hiba történt a kicsomagolás során: $_" -ForegroundColor Red
    exit 1
}

# Autoload létrehozása
Write-Host "Autoload fájl létrehozása..." -ForegroundColor Yellow
$autoloadContent = @"
<?php
// Egyszerű autoload TCPDF-hez
if (!class_exists('TCPDF')) {
    require_once __DIR__ . '/tecnickcom/tcpdf/tcpdf.php';
}

// TCPDF konstansok definiálása ha szükséges
if (!defined('PDF_PAGE_ORIENTATION')) {
    define('PDF_PAGE_ORIENTATION', 'P');
}
if (!defined('PDF_UNIT')) {
    define('PDF_UNIT', 'mm');
}
if (!defined('PDF_PAGE_FORMAT')) {
    define('PDF_PAGE_FORMAT', 'A4');
}
"@

$autoloadContent | Out-File -FilePath "vendor\autoload.php" -Encoding UTF8

Write-Host "`nTCPDF sikeresen telepítve!" -ForegroundColor Green
Write-Host "Ellenőrizd a telepítést a worksheets/list.php oldalon!" -ForegroundColor Yellow

Read-Host "Nyomj Enter-t a kilépéshez"


