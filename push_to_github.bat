@echo off
echo ========================================
echo GitHub-ra feltoltes
echo ========================================
echo.

REM GitHub repository URL bekérése
set /p GITHUB_URL="Add meg a GitHub repository URL-t (pl: https://github.com/USERNAME/REPO-NAME.git): "

if "%GITHUB_URL%"=="" (
    echo Hiba: Meg kell adnod a GitHub repository URL-t!
    pause
    exit /b 1
)

echo.
echo Remote repository hozzaadasa...
git remote add origin %GITHUB_URL%

if errorlevel 1 (
    echo.
    echo Figyelem: A remote mar letezhet. Probalkozom az update-tel...
    git remote set-url origin %GITHUB_URL%
)

echo.
echo Branch neve: main
git branch -M main

echo.
echo Feltoltes GitHub-ra...
git push -u origin main

if errorlevel 1 (
    echo.
    echo Hiba tortent a feltoltes soran!
    echo Ellenorizd:
    echo 1. Hogy a GitHub repository letezik-e
    echo 2. Hogy van-e hozzaferesed (jelszo vagy token)
    pause
    exit /b 1
) else (
    echo.
    echo ========================================
    echo Sikeres feltoltes!
    echo ========================================
)

pause

