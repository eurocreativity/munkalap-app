# Munkalap App - Fejlesztési Ügynökök

Ez a dokumentáció az automatikus fejlesztési ügynök rendszert írja le, amely az alkalmazás fejlesztésének koordinálása és automatizálása.

## Ügynök Overview

### 1. Backend Development Agent (`/backend-dev`)
**Felelősség**: PHP backend fejlesztés, adatbázis integrációs

**Feladatok**:
- Database class elemzés és fejlesztés
- PHP logika fejlesztés
- SQL lekérdezések optimálása
- API endpoint tesztelése
- Hiba javítás a backend-ben

**MCP Integráció**: GitHub, Playwright

---

### 2. Frontend Development Agent (`/frontend-dev`)
**Felelősség**: Frontend UI/UX fejlesztés

**Feladatok**:
- HTML/PHP template fejlesztés
- Bootstrap 5 CSS optimálása
- JavaScript interaktivitás
- Responsív design
- Felhasználói élmény javítása

**MCP Integráció**: Playwright

---

### 3. Testing Suite Agent (`/testing-suite`)
**Felelősség**: Teljes körű tesztelés

**Feladatok**:
- Unit tesztek (PHP)
- Integrációs tesztek
- End-to-End tesztek (Playwright)
- Regressziós tesztelés
- Tesztkörnyezet kezelése

**MCP Integráció**: Playwright

---

### 4. Security Check Agent (`/security-check`)
**Felelősség**: Biztonsági audit és sebezhetőség scanning

**Feladatok**:
- SQL Injection ellenőrzés
- XSS védelem audit
- CSRF token validálás
- Session kezelés biztonság
- OWASP Top 10 audit
- Jelszó biztonság

**MCP Integráció**: GitHub

---

### 5. Bug Fixer Agent (`/bug-fixer`)
**Felelősség**: Hibafelderítés és javítás

**Feladatok**:
- Hiba reprodukálás
- Gyökér ok analízis
- Minimális megoldás implementálás
- Regressziós tesztelés
- Hibajelentések dokumentálása

**MCP Integráció**: GitHub, Playwright

---

### 6. Development Orchestrator (`/dev-orchestrator`)
**Felelősség**: Agent koordináció és workflow menedzsment

**Feladatok**:
- Ügynökök ütemezése
- Konfliktusfeloldás
- Progress tracking
- MCP szerver integráció
- Status reporting
- Git history menedzsment

**MCP Integráció**: GitHub, Playwright

---

### 7. Git Sync Agent (`/git-sync`)
**Felelősség**: Git repository monitorozás, commit és merge kezelés

**Feladatok**:
- Development branch fájlok figyelése (watch mode)
- Automatikus commits generálása
- Branch szinkronizáció
- Main branch-be merge-olés (kérésre)
- Napi aktivitási riportok készítése
- Konfliktusok detektálása és jelzése

**Működési Módok**:
- **Watch Mode** (alapértelmezett) - Automatikus commit és push
- **Manual Mode** - Kérésedre merge-ol main-be

**MCP Integráció**: GitHub

---

## Automatikus Workflow

Az ügynökök az alábbi sorrendben működnek:

```
1. Backend-Dev (párhuzamosan Front-End-del)
   └─ Frontend-Dev (párhuzamosan Backend-del)
      └─ Testing-Suite (backend és frontend tesztelés)
         └─ Security-Check (biztonsági audit)
            └─ Bug-Fixer (hibajavítás)
               └─ Dev-Orchestrator (finalizálás és reporting)
```

## Indítás

### Egyetlen ügynök indítása
```bash
/backend-dev
/frontend-dev
/testing-suite
/security-check
/bug-fixer
/dev-orchestrator
```

### Összes ügynök indítása
```bash
/init-agents
```

### Automatikus monitoring
Az ügynökök automatikusan monitorozzák egymást, és szükség esetén eskaláció történik az orchestrátornak.

## Logging és Reporting

- **Logs**: `.claude/logs/` - Minden ügynök logol saját mappájában
- **Reports**: `.claude/reports/` - Napi összefoglaló riportok
- **Git History**: Automatikus commit-ek minden lezárt feladat után

## MCP Szerver Integráció

### GitHub MCP
- Repository műveletek (pull, push, commit)
- Issue tracking
- PR management
- Code review

### Playwright MCP
- Automatizált böngészési tesztelés
- Visual regression testing
- E2E automatizálás
- Screenshot és HTML capture

## Hibaelvárások

Ha egy ügynök hibát talál:
1. **Local logging** - Az agentje loggolja az errort
2. **Escalation** - Az Orchestrator-nak jelenti
3. **Resolution** - Bug-Fixer Agent indítása
4. **Verification** - Testing Suite futtatása
5. **Documentation** - Hiba dokumentálása és git commit

## Konfigurációs fájlok

- `agent-config.yaml` - Agent konfigurációk
- `mcp.json` - MCP szerver beállítások
- `agent-manager.js` - Agent manager logika

## Monitoring Dashboard

Real-time status a `.claude/logs/` mappában:
- `backend.log` - Backend agent aktivitás
- `frontend.log` - Frontend agent aktivitás
- `testing.log` - Testing aktivitás
- `security.log` - Security audit log
- `bugfix.log` - Hiba javítás log
- `orchestrator.log` - Koordináció és workflow

## Best Practices

1. **Git commits** - Minden agent commitol az saját munkáját
2. **Documentation** - Változások dokumentálása
3. **Testing** - Minden kódmódosítást tesztelni kell
4. **Security** - Biztonsági review előtt merge
5. **Review** - Peer review az orchestratorral

## Támogatás és Debugging

Ha egy ügynök nem működik:
1. Ellenőrizd a `.claude/logs/` fájlokat
2. Nézd meg az MCP szerver statuszt
3. Futtass `/dev-orchestrator`-t a diagnózishoz
4. Nézz meg `.claude/reports/` -ot a részletekért
