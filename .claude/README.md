# Claude Code - Fejlesztési Ügynök Rendszer

Üdvözöllek a Munkalap App automatikus fejlesztési ügynök rendszerében!

## 🚀 Mi ez?

Ez egy teljes ügynök infrastruktúra az alkalmazás fejlesztésének automatizálásához és koordinálásához. Az ügynökök egymással automatikusan együttműködnek, és az MCP szerverekhez csatlakoznak a GitHub és böngészési tesztelés integrálásához.

## 📋 Gyors Indítás

### Lépés 1: Az ügynökök megtekintése
```bash
cat .claude/QUICK_START.md
```

### Lépés 2: Ügynök indítása
```bash
# Egyetlen ügynök
/backend-dev

# Vagy összes ügynök
/init-agents
```

### Lépés 3: Monitorozd az aktivitást
```bash
tail -f .claude/logs/orchestrator.log
```

## 🤖 Ügynökök

| Ügynök | Parancs | Feladat |
|--------|---------|--------|
| **Backend Dev** | `/backend-dev` | PHP, adatbázis, API logika |
| **Frontend Dev** | `/frontend-dev` | HTML, CSS, JavaScript UI |
| **Testing Suite** | `/testing-suite` | Unit, E2E, integrációs tesztek |
| **Security Check** | `/security-check` | Biztonsági audit, sebezhetőség scanning |
| **Bug Fixer** | `/bug-fixer` | Hibajavítás, debugging |
| **Dev Orchestrator** | `/dev-orchestrator` | Koordináció, workflow, reporting |

## 📁 Mappastruktúra

```
.claude/
├── commands/              # Ügynök command fájlok
├── logs/                  # Naplók (real-time)
├── reports/               # Napi riportok
├── agent-config.yaml      # Konfigurációs
├── mcp.json              # MCP szerver konfig
├── QUICK_START.md        # Gyors start
├── AGENTS.md             # Ügynök docs
├── MANIFEST.md           # Teljes manifest
└── README.md             # Ez a fájl
```

## 🔄 Automatikus Workflow

1. **Backend-Dev** és **Frontend-Dev** párhuzamosan futnak
2. **Testing-Suite** teszteli a kódot
3. **Security-Check** auditet futtat
4. **Bug-Fixer** javít bármilyen hibát
5. **Dev-Orchestrator** szinkronizál és jelent

## 🔗 MCP Szerver Integráció

### GitHub
- Automatikus commits végzett munkáról
- Push-ok a szerver-hez
- Branch management
- PR integráció

### Playwright
- Automatizált böngészési tesztelés
- Visual regression testing
- Screenshot és HTML capture
- E2E test automation

## 📊 Naplózás és Reporting

### Naplók
```
.claude/logs/
├── backend.log
├── frontend.log
├── testing.log
├── security.log
├── bugfix.log
└── orchestrator.log
```

### Riportok
```
.claude/reports/
└── report-YYYY-MM-DD-HH-mm-ss.json
```

## 💡 Tippek

1. **Nézd meg az agenteket**
   ```bash
   cat .claude/AGENTS.md
   ```

2. **Nyomj egy ügynöket**
   ```bash
   /backend-dev
   ```

3. **Monitorozd a naplókat**
   ```bash
   tail -f .claude/logs/orchestrator.log
   ```

4. **Nézd meg a riportokat**
   ```bash
   cat .claude/reports/report-*.json | jq .
   ```

## ⚙️ Konfiguráció

Módosítsd az ügynök viselkedését:
- `agent-config.yaml` - Agent engedélyezés, függőségek
- `mcp.json` - MCP szerver beállítások

## 🔒 Biztonság

- ✅ Összes módosítás tesztelve
- ✅ Security audit minden commitnél
- ✅ Git history trail
- ✅ Rollback lehetőség

## 🐛 Hibaelhárítás

### Az ügynök nem indul
```bash
# Nézd meg a naplót
cat .claude/logs/[agent].log

# Diagnózis futtatása
/dev-orchestrator
```

### Hiba a tesztelésben
```bash
# Futtass tesztet egyenként
/testing-suite
```

### MCP szerver nem működik
```bash
# Ellenőrizd az MCP statusz-t
cat .claude/mcp.json
```

## 📚 Dokumentáció

- **[QUICK_START.md](QUICK_START.md)** - Gyors indítás
- **[AGENTS.md](AGENTS.md)** - Ügynök részletek
- **[MANIFEST.md](MANIFEST.md)** - Teljes manifest

## 🌐 GitHub

https://github.com/eurocreativity/munkalap-app

## 📞 Támogatás

Ha kérdéseid vannak:
1. Nézz meg a dokumentációt
2. Ellenőrizd a naplókat
3. Futtass egy diagnózist

## 🎉 Kezdj el!

```bash
# Indítsd el az összes ügynököt
/init-agents

# Vagy válassz egy ügynöket
/backend-dev
```

---

**Jó fejlesztést! 🚀**

Az ügynökrendszer automatikusan koordinálódik és dolgozik az alkalmazáson.
