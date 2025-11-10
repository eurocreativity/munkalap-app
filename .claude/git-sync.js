/**
 * Git Sync Agent - Repository Change Watcher
 * Automatikusan figyeli a fejlesztési branch-et és commitol/mergel
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

class GitSyncAgent {
  constructor(config = {}) {
    this.config = {
      watchInterval: config.watchInterval || 300000, // 5 minutes
      developmentBranch: config.developmentBranch || 'development',
      mainBranch: config.mainBranch || 'main',
      autoCommit: config.autoCommit !== false,
      watchMode: config.watchMode !== false,
      ...config
    };

    this.logDir = path.join(__dirname, 'logs');
    this.reportDir = path.join(__dirname, 'reports');
    this.lastCommitHash = null;
    this.changeLog = [];

    this.ensureDirectories();
  }

  /**
   * Könyvtárak ellenőrzése/létrehozása
   */
  ensureDirectories() {
    if (!fs.existsSync(this.logDir)) {
      fs.mkdirSync(this.logDir, { recursive: true });
    }
    if (!fs.existsSync(this.reportDir)) {
      fs.mkdirSync(this.reportDir, { recursive: true });
    }
  }

  /**
   * Loggolás
   */
  log(message, level = 'info') {
    const timestamp = new Date().toISOString();
    const logFile = path.join(this.logDir, 'git-sync.log');
    const logMessage = `[${timestamp}] [${level.toUpperCase()}] ${message}\n`;

    try {
      fs.appendFileSync(logFile, logMessage);
      if (level === 'error' || level === 'warn') {
        console.log(logMessage);
      }
    } catch (err) {
      console.error('Loggolási hiba:', err);
    }
  }

  /**
   * Git parancs futtatása
   */
  exec(command, errorMessage = '') {
    try {
      const result = execSync(command, { encoding: 'utf-8', stdio: 'pipe' });
      return result.trim();
    } catch (error) {
      this.log(`${errorMessage} ${error.message}`, 'error');
      throw error;
    }
  }

  /**
   * Módosított fájlok lekérése
   */
  getChangedFiles() {
    try {
      const status = this.exec('git status --porcelain', 'Git status hiba');
      if (!status) return [];
      return status.split('\n').filter(line => line.trim());
    } catch (err) {
      return [];
    }
  }

  /**
   * Jelenlegi branch ellenőrzése
   */
  getCurrentBranch() {
    try {
      return this.exec('git rev-parse --abbrev-ref HEAD', 'Branch lekérési hiba');
    } catch (err) {
      return null;
    }
  }

  /**
   * Commit hash lekérése
   */
  getLastCommitHash() {
    try {
      return this.exec('git rev-parse HEAD', 'Commit hash lekérési hiba');
    } catch (err) {
      return null;
    }
  }

  /**
   * Intelligens commit üzenet generálása
   */
  generateCommitMessage(changes) {
    const timestamp = new Date().toISOString();
    const fileCount = changes.length;

    let changeType = 'Update';
    if (changes.some(c => c.startsWith('A'))) changeType = 'Add';
    if (changes.some(c => c.startsWith('D'))) changeType = 'Remove';
    if (changes.some(c => c.startsWith('M'))) changeType = 'Modify';

    const message = `[DEV] ${changeType} ${fileCount} files

Branch: ${this.config.developmentBranch}
Files: ${fileCount} módosított fájl
Timestamp: ${timestamp}

Modified files:
${changes.map(c => `- ${c}`).join('\n')}

🤖 Generated with Claude Code

Co-Authored-By: Claude <noreply@anthropic.com>`;

    return message;
  }

  /**
   * Automatikus commit
   */
  autoCommit() {
    const changes = this.getChangedFiles();
    if (changes.length === 0) {
      this.log('Nincs módosított fájl');
      return false;
    }

    try {
      this.log(`Staging ${changes.length} fájl...`);
      this.exec('git add .', 'Git add hiba');

      const commitMessage = this.generateCommitMessage(changes);
      this.log(`Commit-olás: ${changes.length} fájl`);
      this.exec(`git commit -m "${commitMessage}"`, 'Git commit hiba');

      this.log(`Push-olás origin/${this.config.developmentBranch}-be`);
      this.exec(`git push origin ${this.config.developmentBranch}`, 'Git push hiba');

      this.changeLog.push({
        timestamp: new Date(),
        fileCount: changes.length,
        changes: changes
      });

      return true;
    } catch (err) {
      this.log(`Commit hiba: ${err.message}`, 'error');
      return false;
    }
  }

  /**
   * Merge development -> main
   */
  mergeToMain() {
    const currentBranch = this.getCurrentBranch();

    try {
      this.log(`Merge indítása: ${this.config.developmentBranch} -> ${this.config.mainBranch}`);

      // Checkout main
      this.log(`Áttérés ${this.config.mainBranch}-re...`);
      this.exec(`git checkout ${this.config.mainBranch}`, 'Checkout hiba');

      // Pull latest
      this.log(`Frissítés ${this.config.mainBranch}-ből...`);
      this.exec(`git pull origin ${this.config.mainBranch}`, 'Pull hiba');

      // Merge development
      this.log(`Merge-olés ${this.config.developmentBranch}-ből...`);
      this.exec(`git merge ${this.config.developmentBranch} --no-ff`, 'Merge hiba');

      // Push
      this.log(`Push-olás origin/${this.config.mainBranch}-be...`);
      this.exec(`git push origin ${this.config.mainBranch}`, 'Push hiba');

      // Vissza development-re
      this.log(`Vissza ${this.config.developmentBranch}-re...`);
      this.exec(`git checkout ${this.config.developmentBranch}`, 'Checkout hiba');

      this.log('Merge sikeres!');
      return true;
    } catch (err) {
      this.log(`Merge hiba: ${err.message}`, 'error');
      // Vissza az eredeti branch-re
      if (currentBranch) {
        this.exec(`git checkout ${currentBranch}`, 'Checkout return hiba');
      }
      return false;
    }
  }

  /**
   * Watch mode indítása
   */
  startWatching() {
    this.log('Watch mode indítása...');
    this.log(`Figyelési intervallum: ${this.config.watchInterval / 1000} másodperc`);

    setInterval(() => {
      try {
        const currentBranch = this.getCurrentBranch();

        if (currentBranch !== this.config.developmentBranch) {
          this.log(`Eltérő branch: ${currentBranch}`, 'warn');
          return;
        }

        const changes = this.getChangedFiles();
        if (changes.length > 0) {
          this.log(`${changes.length} módosított fájl detektálva`);

          if (this.config.autoCommit) {
            this.autoCommit();
          } else {
            this.log('Auto-commit le van tiltva');
          }
        }
      } catch (err) {
        this.log(`Watch hiba: ${err.message}`, 'error');
      }
    }, this.config.watchInterval);
  }

  /**
   * Napi report generálása
   */
  generateReport() {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const reportFile = path.join(this.reportDir, `git-sync-${timestamp}.json`);

    const report = {
      generated: new Date().toISOString(),
      branch: {
        current: this.getCurrentBranch(),
        development: this.config.developmentBranch,
        main: this.config.mainBranch
      },
      commits: this.changeLog.length,
      filesModified: this.changeLog.reduce((sum, log) => sum + log.fileCount, 0),
      changeLog: this.changeLog,
      status: 'active',
      config: {
        autoCommit: this.config.autoCommit,
        watchMode: this.config.watchMode
      }
    };

    try {
      fs.writeFileSync(reportFile, JSON.stringify(report, null, 2));
      this.log(`Report generálva: ${reportFile}`);
    } catch (err) {
      this.log(`Report hiba: ${err.message}`, 'error');
    }

    return report;
  }

  /**
   * Status lekérése
   */
  getStatus() {
    return {
      branch: this.getCurrentBranch(),
      changes: this.getChangedFiles().length,
      autoCommit: this.config.autoCommit,
      watchMode: this.config.watchMode,
      lastCommits: this.changeLog.slice(-5)
    };
  }
}

// Module export
module.exports = GitSyncAgent;

// Standalone mode
if (require.main === module) {
  const agent = new GitSyncAgent({
    watchMode: true,
    autoCommit: true,
    watchInterval: 300000 // 5 minutes
  });

  agent.log('Git Sync Agent indítva');
  agent.startWatching();

  // Graceful shutdown
  process.on('SIGINT', () => {
    agent.log('Git Sync Agent leállítva');
    agent.generateReport();
    process.exit(0);
  });
}
