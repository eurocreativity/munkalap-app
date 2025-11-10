# Ügynök Inicializálás

Initialize az összes fejlesztési ügynöket az alábbi sorrendben:

## Lépések
1. **Backend Dev Agent** - `/backend-dev` - PHP/Database alapok
2. **Frontend Dev Agent** - `/frontend-dev` - HTML/CSS/JS alapok
3. **Testing Suite Agent** - `/testing-suite` - Tesztelési infrastruktúra
4. **Security Check Agent** - `/security-check` - Biztonsági audit
5. **Bug Fixer Agent** - `/bug-fixer` - Hiba gyűjtés és javítás
6. **Dev Orchestrator** - `/dev-orchestrator` - Koordináció és felügyelet

## Autokonfigúráció
Az ügynökök automatikusan:
- Megosztják a projekt repository-t
- Szinkronban tartják a git historyt
- Egymást értesítik az változásokról
- MCP szervereket használnak szükség szerint

## Monitoring
- Status: `statusline-setup` - real-time progress tracking
- Logging: `.claude/logs/` mapában
- Reports: `.claude/reports/` mapában

## Engedélyezés
Az ügynökök minden feladatra kell:
- Git commits és pushes
- Kód módosítások
- Tesztelés végrehajtása
- MCP szerver queries

Ez a fájl a `/init-agents` command indításakor fog futni.
