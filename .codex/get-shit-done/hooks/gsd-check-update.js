#!/usr/bin/env node
// gsd-hook-version: 1.30.0
// Check for GSD updates in background, write result to cache
// Called by SessionStart hook - runs once per session

const fs = require('fs');
const path = require('path');
const os = require('os');
const { spawn } = require('child_process');

const homeDir = os.homedir();
const cwd = process.cwd();

// Detect runtime config directory with Codex-local preference.
function detectConfigDir(baseDir) {
  const envDirs = [process.env.CODEX_HOME, process.env.CLAUDE_CONFIG_DIR].filter(Boolean);
  for (const envDir of envDirs) {
    if (fs.existsSync(path.join(envDir, 'get-shit-done', 'VERSION'))) {
      return envDir;
    }
  }

  for (const dir of ['.codex', '.config/opencode', '.opencode', '.gemini', '.claude']) {
    if (fs.existsSync(path.join(baseDir, dir, 'get-shit-done', 'VERSION'))) {
      return path.join(baseDir, dir);
    }
  }

  return envDirs[0] || path.join(baseDir, '.codex');
}

const globalConfigDir = detectConfigDir(homeDir);
const projectConfigDir = detectConfigDir(cwd);
const activeConfigDir = fs.existsSync(path.join(projectConfigDir, 'get-shit-done', 'VERSION'))
  ? projectConfigDir
  : globalConfigDir;
const cacheDir = path.join(activeConfigDir, 'cache');
const cacheFile = path.join(cacheDir, 'gsd-update-check.json');

// VERSION file locations (check project first, then global)
const projectVersionFile = path.join(projectConfigDir, 'get-shit-done', 'VERSION');
const globalVersionFile = path.join(globalConfigDir, 'get-shit-done', 'VERSION');

// Ensure cache directory exists
if (!fs.existsSync(cacheDir)) {
  fs.mkdirSync(cacheDir, { recursive: true });
}

// Run check in background (spawn background process, windowsHide prevents console flash)
const child = spawn(process.execPath, ['-e', `
  const fs = require('fs');
  const path = require('path');
  const { execSync } = require('child_process');

  const cacheFile = ${JSON.stringify(cacheFile)};
  const projectVersionFile = ${JSON.stringify(projectVersionFile)};
  const globalVersionFile = ${JSON.stringify(globalVersionFile)};

  // Check project directory first (local install), then global
  let installed = '0.0.0';
  let configDir = '';
  try {
    if (fs.existsSync(projectVersionFile)) {
      installed = fs.readFileSync(projectVersionFile, 'utf8').trim();
      configDir = path.dirname(path.dirname(projectVersionFile));
    } else if (fs.existsSync(globalVersionFile)) {
      installed = fs.readFileSync(globalVersionFile, 'utf8').trim();
      configDir = path.dirname(path.dirname(globalVersionFile));
    }
  } catch (e) {}

  // Check for stale hooks — compare hook version headers against installed VERSION
  // Hooks live inside get-shit-done/hooks/, not configDir/hooks/
  let staleHooks = [];
  if (configDir) {
    const hooksDir = path.join(configDir, 'get-shit-done', 'hooks');
    try {
      if (fs.existsSync(hooksDir)) {
        const hookFiles = fs.readdirSync(hooksDir).filter(f => f.startsWith('gsd-') && f.endsWith('.js'));
        for (const hookFile of hookFiles) {
          try {
            const content = fs.readFileSync(path.join(hooksDir, hookFile), 'utf8');
            const versionMatch = content.match(/\\/\\/ gsd-hook-version:\\s*(.+)/);
            if (versionMatch) {
              const hookVersion = versionMatch[1].trim();
              if (hookVersion !== installed && !hookVersion.includes('{{')) {
                staleHooks.push({ file: hookFile, hookVersion, installedVersion: installed });
              }
            } else {
              // No version header at all — definitely stale (pre-version-tracking)
              staleHooks.push({ file: hookFile, hookVersion: 'unknown', installedVersion: installed });
            }
          } catch (e) {}
        }
      }
    } catch (e) {}
  }

  let latest = null;
  try {
    latest = execSync('npm view get-shit-done-cc version', { encoding: 'utf8', timeout: 10000, windowsHide: true }).trim();
  } catch (e) {}

  const result = {
    update_available: latest && installed !== latest,
    installed,
    latest: latest || 'unknown',
    checked: Math.floor(Date.now() / 1000),
    stale_hooks: staleHooks.length > 0 ? staleHooks : undefined
  };

  fs.writeFileSync(cacheFile, JSON.stringify(result));
`], {
  stdio: 'ignore',
  windowsHide: true,
  detached: true  // Required on Windows for proper process detachment
});

child.unref();
