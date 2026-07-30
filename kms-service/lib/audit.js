'use strict';

/**
 * Audit log
 * ---------
 * Every KMS operation is appended here. Each entry includes the hash of the
 * previous entry, forming a hash chain — if any past entry is edited or
 * deleted, every subsequent hash breaks, so tampering is detectable even
 * though the log itself is "just a file."
 */

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const LOG_FILE = path.join(__dirname, '..', 'data', 'audit.log.jsonl');

function ensureFile() {
  fs.mkdirSync(path.dirname(LOG_FILE), { recursive: true });
  if (!fs.existsSync(LOG_FILE)) fs.writeFileSync(LOG_FILE, '');
}

function lastHash() {
  ensureFile();
  const content = fs.readFileSync(LOG_FILE, 'utf8').trim();
  if (!content) return '0'.repeat(64); // genesis hash
  const lines = content.split('\n');
  const last = JSON.parse(lines[lines.length - 1]);
  return last.entryHash;
}

function record({ apiKeyId, operation, keyName, keyVersion, success, detail, ip }) {
  ensureFile();
  const prevHash = lastHash();
  const entry = {
    ts: new Date().toISOString(),
    apiKeyId: apiKeyId || null,
    operation,               // e.g. "encrypt", "decrypt", "rotate", "create_key"
    keyName: keyName || null,
    keyVersion: keyVersion || null,
    success: !!success,
    detail: detail || null,
    ip: ip || null,
    prevHash,
  };
  const entryHash = crypto
    .createHash('sha256')
    .update(JSON.stringify(entry))
    .digest('hex');
  const line = JSON.stringify({ ...entry, entryHash });
  fs.appendFileSync(LOG_FILE, line + '\n');
  return entry;
}

function readAll() {
  ensureFile();
  const content = fs.readFileSync(LOG_FILE, 'utf8').trim();
  if (!content) return [];
  return content.split('\n').map((l) => JSON.parse(l));
}

/** Walks the chain and verifies no entry was altered or removed. */
function verifyChain() {
  const entries = readAll();
  let expectedPrev = '0'.repeat(64);
  for (const e of entries) {
    if (e.prevHash !== expectedPrev) {
      return { ok: false, brokenAt: e.ts, reason: 'prevHash mismatch' };
    }
    const { entryHash, ...rest } = e;
    const recomputed = crypto.createHash('sha256').update(JSON.stringify(rest)).digest('hex');
    if (recomputed !== entryHash) {
      return { ok: false, brokenAt: e.ts, reason: 'entryHash mismatch (entry was edited)' };
    }
    expectedPrev = entryHash;
  }
  return { ok: true, entries: entries.length };
}

module.exports = { record, readAll, verifyChain };
