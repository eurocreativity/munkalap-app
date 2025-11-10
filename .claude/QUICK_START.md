# Ügynök Rendszer - Gyors Indítás

## Mi történik, amikor elindítod az agenteket?

Az alábbi ügynök rendszer automatikusan koordinálódik és dolgozik együtt az alkalmazás fejlesztésén:

```
┌─────────────────────────────────────────────────────────┐
│         DEVELOPMENT AGENT SYSTEM                         │
├─────────────────────────────────────────────────────────┤
│                                                           │
│  ┌──────────────┐         ┌──────────────┐             │
│  │ BACKEND-DEV  │         │ FRONTEND-DEV │             │
│  │ (PHP, DB)    │─────┬───│ (HTML, CSS)  │             │
│  └──────────────┘     │   └──────────────┘             │
│                       │                                  │
│                       ▼                                  │
│                ┌──────────────────┐                     │
│                │ TESTING-SUITE    │                     │
│                │ (Unit, E2E, Int) │                     │
│                └──────────────────┘                     │
│                       │                                  │
│                       ▼                                  │
│                ┌──────────────────┐                     │
│                │ SECURITY-CHECK   │                     │
│                │ (Vuln. Scanning) │                     │
│                └──────────────────┘                     │
│                       │                                  │
│                       ▼                                  │
│                ┌──────────────────┐                     │
│                │  BUG-FIXER       │                     │
│                │ (Debugging, Fix) │                     │
│                └──────────────────┘                     │
│                       │                                  │
│                       ▼                                  │
│                ┌──────────────────┐                     │
│                │ DEV-ORCHESTRATOR │                     │
│                │ (Koordináció)    │                     │
│                └──────────────────┘                     │
│                                                           │
└─────────────────────────────────────────────────────────┘
```

## Parancsok

### Egyetlen ügynök indítása
```bash
/backend-dev       # PHP backend fejlesztés
/frontend-dev      # Frontend UI fejlesztés
/testing-suite     # Tesztelés
/security-check    # Biztonsági audit
/bug-fixer         # Hibák javítása
/dev-orchestrator  # Szinkronizálás és reporting
```

### Összes ügynök indítása
```bash
/init-agents       # Automatikus workflow indítása
```

## Mit csinálnak az ügynökök?

### Backend Development Agent
- **Célja**: PHP backend kód fejlesztése
- **Működik**: `classes/`, `includes/` mappákon
- **Ellenőrzi**: Database, felhasználó logika, API logika
- **Módosít**: PHP fájlok, Database schema

### Frontend Development Agent
- **Célja**: Frontend UI fejlesztése
- **Működik**: HTML, CSS, JavaScript
- **Ellenőrzi**: Felhasználói interfész, interaktivitás
- **Módosít**: HTML/PHP template-ek, CSS, JS fájlok

### Testing Suite Agent
- **Célja**: Teljes körű tesztelés
- **Működik**: Unit tesztek, E2E tesztek, integrációs tesztek
- **Ellenőrzi**: Funkciók, regresszió, edge case-ek
- **Jelenti**: Test report, code coverage

### Security Check Agent
- **Célja**: Biztonsági sebezhetőségek feltárása
- **Működik**: SQL Injection, XSS, CSRF, autentifikáció
- **Ellenőrzi**: OWASP Top 10, best practices
- **Jelenti**: Biztonsági audit, sebezhetőség lista

### Bug Fixer Agent
- **Célja**: Hibajavítás
- **Működik**: Hibareprodukálás, diagnózis, megoldás
- **Ellenőrzi**: Root cause, regresszió
- **Jelenti**: Hiba javítás dokumentáció

### Dev Orchestrator Agent
- **Célja**: Ügynökök koordinálása
- **Működik**: Workflow szerkesztés, MCP integráció
- **Ellenőrzi**: Status, szinkronizáció, konfliklusok
- **Jelenti**: Status report, final summary

## Naplók és Riportok

Minden ügynök saját naplót ír:

```
.claude/logs/
├── backend.log       # Backend activity
├── frontend.log      # Frontend activity
├── testing.log       # Test results
├── security.log      # Security audit
├── bugfix.log        # Bug fixes
└── orchestrator.log  # Coordination
```

Napi riportok:

```
.claude/reports/
├── report-2025-11-10-*.json  # Daily summary
└── ...
```

## Git Integrációs

Az ügynökök automatikusan:
- Commitolnak végzett munkájukra
- Push-olnak a GitHub-ra
- Nyomon követik a History-t
- Jelzik az MCP szewer-nek a változásokat

## MCP Szerver Integrációs

- **GitHub MCP**: Repository műveletek, commits, pushes
- **Playwright MCP**: Böngészési tesztelés, visual testing

## Tipikus Workflow

1. **Frontend-Dev** kezd a UI fejlesztéssel
2. **Backend-Dev** párhuzamosan PHP logikán dolgozik
3. **Testing-Suite** ellenőrzi mindkettőt
4. **Security-Check** auditet futtat
5. **Bug-Fixer** orvosol bármilyen hibát
6. **Dev-Orchestrator** véglegesen szinkronizál

## Hibakezelés

Ha valami nem működik:

1. **Nézd meg a logokat**: `.claude/logs/`
2. **Ellenőrizd az MCP szerver-t**: GitHub, Playwright
3. **Futtass egy diagnózist**: `/dev-orchestrator`
4. **Nézd meg a reportot**: `.claude/reports/`

## Fontos Tudnivalók

⚠️ **Az ügynökök automatikusan:**
- Módosítanak kódot
- Commitolnak Git-re
- Pusholnak GitHub-ra
- Futtatnak teszteket

✅ **Ezt biztonságosan teszik:**
- Csak saját feladataikon
- Tesztelés előtt módosítás
- Git history megőrzése
- Backup a régi verziók számára

## Első Lépések

```bash
# 1. Nézd meg az agenteket
cat .claude/AGENTS.md

# 2. Indítsd el az összes ügynöket
/init-agents

# 3. Monitorozd a naplókat
tail -f .claude/logs/orchestrator.log

# 4. Nézd meg a végső riportot
cat .claude/reports/report-*.json
```

## Kérdések?

- 📖 Dokumentáció: `.claude/AGENTS.md`
- 🔧 Konfigurációs: `.claude/agent-config.yaml`
- 📊 Riportok: `.claude/reports/`
- 📝 Naplók: `.claude/logs/`
