# GitHub-ra feltöltés PowerShell script

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "GitHub-ra feltöltés" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# GitHub repository URL bekérése
$githubUrl = Read-Host "Add meg a GitHub repository URL-t (pl: https://github.com/USERNAME/REPO-NAME.git)"

if ([string]::IsNullOrWhiteSpace($githubUrl)) {
    Write-Host "Hiba: Meg kell adnod a GitHub repository URL-t!" -ForegroundColor Red
    Read-Host "Nyomj Enter-t a kilépéshez"
    exit 1
}

Write-Host ""
Write-Host "Remote repository hozzáadása..." -ForegroundColor Yellow

# Remote hozzáadása vagy frissítése
$existingRemote = git remote get-url origin 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "Remote már létezik, frissítés..." -ForegroundColor Yellow
    git remote set-url origin $githubUrl
} else {
    git remote add origin $githubUrl
}

if ($LASTEXITCODE -ne 0) {
    Write-Host "Hiba történt a remote beállításakor!" -ForegroundColor Red
    Read-Host "Nyomj Enter-t a kilépéshez"
    exit 1
}

Write-Host ""
Write-Host "Branch neve: main" -ForegroundColor Yellow
git branch -M main

Write-Host ""
Write-Host "Feltöltés GitHub-ra..." -ForegroundColor Yellow
git push -u origin main

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "Hiba történt a feltöltés során!" -ForegroundColor Red
    Write-Host "Ellenőrizd:" -ForegroundColor Yellow
    Write-Host "1. Hogy a GitHub repository létezik-e" -ForegroundColor Yellow
    Write-Host "2. Hogy van-e hozzáférése (jelszó vagy token)" -ForegroundColor Yellow
    Write-Host "3. Hogy be vagy-e jelentkezve GitHub-ra" -ForegroundColor Yellow
    Read-Host "Nyomj Enter-t a kilépéshez"
    exit 1
} else {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Sikeres feltöltés!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "A projekt most elérhető GitHub-on!" -ForegroundColor Green
}

Read-Host "Nyomj Enter-t a kilépéshez"

