# GitHub Feltöltés Útmutató

## 1. Git Repository Inicializálása

Ha még nincs Git telepítve, töltsd le: https://git-scm.com/download/win

Nyisd meg a PowerShell-t vagy Command Prompt-ot a projekt mappájában:

```bash
cd C:\xampp\htdocs\munkalap-app
```

## 2. Git Repository Létrehozása

```bash
# Git inicializálása
git init

# Összes fájl hozzáadása
git add .

# Első commit
git commit -m "Initial commit: Munkalap App - teljes funkcionalitással"
```

## 3. GitHub Repository Létrehozása

1. Látogasd meg: https://github.com
2. Kattints a "+" gombra (jobbra fent) → "New repository"
3. Add meg a repository nevét (pl: `munkalap-app`)
4. Válaszd ki, hogy Public vagy Private
5. **NE** jelöld be az "Initialize with README" opciót
6. Kattints a "Create repository" gombra

## 4. GitHub-ra Feltöltés

### Egyszerű módszer (ajánlott):

**PowerShell script:**
```powershell
.\push_to_github.ps1
```

**Vagy Batch fájl:**
```cmd
push_to_github.bat
```

A script megkérdezi a GitHub repository URL-t, majd automatikusan feltölti.

### Manuális módszer:

A GitHub meg fogja mutatni a parancsokat. Használd ezeket:

```bash
# Remote repository hozzáadása (cseréld ki a USERNAME-t és REPO-NAME-t)
git remote add origin https://github.com/USERNAME/REPO-NAME.git

# Branch neve (main vagy master)
git branch -M main

# Feltöltés
git push -u origin main
```

## 5. Vagy SSH-vel (ha be van állítva)

```bash
git remote add origin git@github.com:USERNAME/REPO-NAME.git
git branch -M main
git push -u origin main
```

## 6. További Commitok

Ha módosítasz fájlokat:

```bash
# Változások hozzáadása
git add .

# Commit
git commit -m "Leírás a változásokról"

# Feltöltés
git push
```

## Fontos Megjegyzések

⚠️ **FIGYELEM**: A `.gitignore` fájl kizárja a következőket:
- `/vendor/` mappa (TCPDF - ezt manuálisan kell telepíteni)
- `/tmp/` mappa (ideiglenes PDF-ek)
- `/logs/` mappa (email logok)
- Egyéb ideiglenes fájlok

Ez azt jelenti, hogy aki klónozza a repository-t, annak telepítenie kell a TCPDF-et (lásd: `MANUAL_TCPDF_INSTALL.md`).

## Alternatíva: TCPDF is a Repository-ban

Ha szeretnéd, hogy a TCPDF is benne legyen a repository-ban, töröld ki a `/vendor/` sort a `.gitignore` fájlból, de figyelem: ez nagy fájlokat fog tartalmazni!

