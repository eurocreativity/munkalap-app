# Munkalap App - Fejlesztési Ügynök Manifest

## Projekt Információ
- **Projekt**: Munkalap Kezelő Webalkalmazás
- **Típus**: PHP + MySQL webalkalmazás
- **Technológia Stack**: PHP 7.4+, Bootstrap 5, TCPDF
- **Indítva**: 2025-11-10
- **State**: Production-ready development environment

## Ügynök Rendszer

### Szerkezet
```
.claude/
├── commands/              # Ügynök command fájlok
│   ├── backend-dev.md
│   ├── frontend-dev.md
│   ├── testing-suite.md
│   ├── security-check.md
│   ├── bug-fixer.md
│   ├── dev-orchestrator.md
│   └── init-agents.md
├── logs/                  # Naplók (automatikus)
├── reports/               # Riportok (automatikus)
├── agent-config.yaml      # Agent konfigurációs
├── agent-manager.js       # Agent manager logika
├── mcp.json              # MCP szerver konfig
├── AGENTS.md             # Ügynök dokumentáció
├── QUICK_START.md        # Gyors indítás útmutató
└── MANIFEST.md          # Ez a fájl
```

## Ügynökök

### 1. Backend Development Agent
- **Command**: `/backend-dev`
- **File**: `.claude/commands/backend-dev.md`
- **Felelősség**: PHP logika, adatbázis, API
- **Capabilities**: Code analysis, bug fixing, database ops
- **MCP Szerver**: GitHub, Playwright

### 2. Frontend Development Agent
- **Command**: `/frontend-dev`
- **File**: `.claude/commands/frontend-dev.md`
- **Felelősség**: HTML, CSS, JavaScript UI
- **Capabilities**: UI development, styling, interactivity
- **MCP Szerver**: Playwright

### 3. Testing Suite Agent
- **Command**: `/testing-suite`
- **File**: `.claude/commands/testing-suite.md`
- **Felelősség**: Unit, integration, E2E tesztek
- **Capabilities**: Test automation, QA, regression testing
- **MCP Szerver**: Playwright

### 4. Security Check Agent
- **Command**: `/security-check`
- **File**: `.claude/commands/security-check.md`
- **Felelősség**: Biztonsági audit, sebezhetőség scanning
- **Capabilities**: Vuln scanning, security audit, code review
- **MCP Szerver**: GitHub

### 5. Bug Fixer Agent
- **Command**: `/bug-fixer`
- **File**: `.claude/commands/bug-fixer.md`
- **Felelősség**: Hibajavítás, debugging
- **Capabilities**: Bug triage, debugging, testing
- **MCP Szerver**: GitHub, Playwright

### 6. Development Orchestrator
- **Command**: `/dev-orchestrator`
- **File**: `.claude/commands/dev-orchestrator.md`
- **Felelősség**: Koordináció, workflow, integration
- **Capabilities**: Coordination, task scheduling, reporting
- **MCP Szerver**: GitHub, Playwright

## Automatikus Workflow

```
Start
  ├─ Backend-Dev ────┐
  └─ Frontend-Dev ───┤
                     ├─→ Testing-Suite
                     │     │
                     │     └─→ Security-Check
                     │           │
                     │           └─→ Bug-Fixer
                     │                 │
                     │                 └─→ Dev-Orchestrator
                     │                       │
                     └─→─────────────────────┘
                               │
                               ↓
                            Report
```

## MCP Szerver Integráció

### GitHub MCP
- Commit és push operations
- Branch management
- Issue tracking
- Pull request handling
- Code review integration

### Playwright MCP
- Automatizált böngészési tesztelés
- Visual regression testing
- Screenshot és HTML capture
- E2E test automation
- Performance monitoring

## Naplózási és Reporting

### Log Fájlok
Helye: `.claude/logs/`

```
backend.log        - Backend agent aktivitás
frontend.log       - Frontend agent aktivitás
testing.log        - Testing aktivitás
security.log       - Security audit log
bugfix.log         - Bug fixing log
orchestrator.log   - Koordináció log
```

### Report Fájlok
Helye: `.claude/reports/`

```
report-YYYY-MM-DD-HH-mm-ss.json  - Napi összefoglaló
```

## Konfigurációs Fájlok

### agent-config.yaml
- Agent engedélyezés/tiltás
- Függőségek (dependencies)
- MCP szerver beállítások
- Workflow konfigurációs
- Global beállítások

### mcp.json
- MCP szerver végpontok
- Agent capabilities
- Szerver prioritások

### agent-manager.js
- Manager logika
- Logging implementáció
- Report generálás
- Agent lifecycle management

## Indítási Parancsok

### Egyedi ügynök
```bash
/backend-dev       # Backend fejlesztés
/frontend-dev      # Frontend fejlesztés
/testing-suite     # Tesztelés
/security-check    # Biztonsági audit
/bug-fixer         # Hibák javítása
/dev-orchestrator  # Koordináció
```

### Teljes workflow
```bash
/init-agents       # Összes ügynök szekvenciális futtatása
```

## Git Integráció

Az ügynökök automatikusan:
- Trackingolnak változásokat
- Commitolnak végzett munkát
- Push-olnak a GitHub-ra
- Szinkronban tartják a históriát

Commit szintaxis:
```
[AGENT] Description

agent-type: backend|frontend|testing|security|bugfix|orchestrator
task-id: unique-identifier
status: completed|in-progress|failed
```

## Monitoring

### Real-time Status
Fájl: `.claude/logs/orchestrator.log`

```bash
tail -f .claude/logs/orchestrator.log
```

### Napi Summary
Fájl: `.claude/reports/report-*.json`

```bash
cat .claude/reports/report-*.json | jq .
```

## Biztonsági Konvenciók

- ✅ Összes módosítás tesztelve
- ✅ Security review előtt merge
- ✅ Git commit audit trail
- ✅ Hiba dokumentálása
- ✅ Backup/rollback lehetőség

## Hibajelentés

Ha valami nem működik:

1. **Nézd meg a naplót**: `tail -f .claude/logs/[agent].log`
2. **Futtass diagnózist**: `/dev-orchestrator`
3. **Nézd meg a reportot**: `.claude/reports/`
4. **MCP szerver status**: Ellenőrizd GitHub/Playwright

## Telepítési Követelmények

- ✅ XAMPP (Apache + MySQL)
- ✅ PHP 7.4+
- ✅ Node.js (MCP szerverekhez)
- ✅ npm (dependency management)
- ✅ Git (version control)

## Alkalmazás Szerkezete

```
munkalap-app/
├── .claude/                  # Ügynök rendszer
├── .github/workflows/        # GitHub Actions
├── classes/                  # PHP objektumok
├── companies/                # Cég kezelés
├── includes/                 # Közös logika
├── monthly/                  # Havi zárás
├── worksheets/               # Munkalap kezelés
├── vendor/                   # Composer dependencies
├── config.php               # Konfiguráció
├── login.php                # Bejelentkezés
├── dashboard.php            # Dashboard
└── README.md                # Dokumentáció
```

## Status

- **Development Agents**: ✅ Active
- **MCP Servers**: ✅ Configured
- **Git Integration**: ✅ Active
- **Testing Framework**: ✅ Ready
- **Security Auditing**: ✅ Ready
- **Monitoring**: ✅ Active

## Verzió

- **Verzió**: 1.0.0
- **Release Date**: 2025-11-10
- **Last Updated**: 2025-11-10

## Támogatás

Documentáció:
- `.claude/AGENTS.md` - Agent details
- `.claude/QUICK_START.md` - Getting started
- `.claude/agent-config.yaml` - Configuration

Naplók és Reports:
- `.claude/logs/` - Activity logs
- `.claude/reports/` - Daily reports

GitHub:
- https://github.com/eurocreativity/munkalap-app
