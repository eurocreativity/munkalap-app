/**
 * Development Agent Manager
 * Automatikusan koordinálja az összes fejlesztési ügynöket
 */

const fs = require('fs');
const path = require('path');

class AgentManager {
  constructor() {
    this.agents = {
      'backend': { status: 'idle', lastRun: null },
      'frontend': { status: 'idle', lastRun: null },
      'testing': { status: 'idle', lastRun: null },
      'security': { status: 'idle', lastRun: null },
      'bugfix': { status: 'idle', lastRun: null },
      'orchestrator': { status: 'idle', lastRun: null }
    };
    this.logDir = path.join(__dirname, 'logs');
    this.reportDir = path.join(__dirname, 'reports');
  }

  /**
   * Az ügynök statuszát loggolja
   */
  log(agent, message, level = 'info') {
    const timestamp = new Date().toISOString();
    const logFile = path.join(this.logDir, `${agent}.log`);
    const logMessage = `[${timestamp}] [${level.toUpperCase()}] ${message}\n`;

    try {
      fs.appendFileSync(logFile, logMessage);
    } catch (err) {
      console.error(`Failed to write log for ${agent}:`, err);
    }
  }

  /**
   * Ügynök indítása
   */
  startAgent(agent) {
    this.log(agent, `Agent ${agent} indítása...`);
    this.agents[agent].status = 'running';
    this.agents[agent].lastRun = new Date();
  }

  /**
   * Ügynök leállítása
   */
  stopAgent(agent) {
    this.log(agent, `Agent ${agent} leállítása...`);
    this.agents[agent].status = 'stopped';
  }

  /**
   * Ügynök statusza
   */
  getStatus(agent) {
    return this.agents[agent];
  }

  /**
   * Összes ügynök statusza
   */
  getAllStatus() {
    return this.agents;
  }

  /**
   * Report generálása
   */
  generateReport() {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const reportFile = path.join(this.reportDir, `report-${timestamp}.json`);

    const report = {
      generated: new Date().toISOString(),
      agents: this.agents,
      summary: {
        total: Object.keys(this.agents).length,
        running: Object.values(this.agents).filter(a => a.status === 'running').length,
        idle: Object.values(this.agents).filter(a => a.status === 'idle').length
      }
    };

    try {
      fs.writeFileSync(reportFile, JSON.stringify(report, null, 2));
      this.log('orchestrator', `Report generálva: ${reportFile}`);
    } catch (err) {
      console.error('Report generálási hiba:', err);
    }

    return report;
  }

  /**
   * Szinkronizálás ellenőrzés
   */
  checkSync() {
    this.log('orchestrator', 'Git szinkronizáció ellenőrzése...');
    // Git status check logic goes here
  }

  /**
   * Automatikus workflow
   */
  async runAutomaticWorkflow() {
    this.log('orchestrator', 'Automatikus workflow indítása...');

    // Sequential agent execution
    const order = ['backend', 'frontend', 'testing', 'security', 'bugfix'];

    for (const agent of order) {
      this.startAgent(agent);
      // Simulate agent work
      await this.sleep(1000);
      this.stopAgent(agent);
    }

    this.generateReport();
    this.log('orchestrator', 'Automatikus workflow befejezve');
  }

  sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// Export
if (require.main === module) {
  const manager = new AgentManager();
  manager.runAutomaticWorkflow().catch(console.error);
}

module.exports = AgentManager;
