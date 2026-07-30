'use strict';

/**
 * Auth / access control
 * ----------------------
 * Lightweight IAM equivalent. Each API key has:
 *   - an id (safe to log)
 *   - a hashed secret (never stored in plaintext, never logged)
 *   - a set of allowed operations: encrypt, decrypt, rotate, create_key, audit
 *   - an optional restriction to specific key names (default: all)
 *
 * Real-world equivalent: AWS IAM policies attached to a role, or Vault's
 * token policies. This is a deliberately small version of the same idea.
 */

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const KEYS_FILE = path.join(__dirname, '..', 'data', 'api-keys.json');

function ensureFile() {
  fs.mkdirSync(path.dirname(KEYS_FILE), { recursive: true });
  if (!fs.existsSync(KEYS_FILE)) {
    fs.writeFileSync(KEYS_FILE, JSON.stringify({ apiKeys: [] }, null, 2));
  }
}

function hashSecret(secret) {
  return crypto.createHash('sha256').update(secret).digest('hex');
}

function readAll() {
  ensureFile();
  return JSON.parse(fs.readFileSync(KEYS_FILE, 'utf8'));
}

function writeAll(data) {
  fs.writeFileSync(KEYS_FILE, JSON.stringify(data, null, 2));
}

/**
 * Creates a new API key. Returns the PLAINTEXT secret exactly once —
 * it is never stored or retrievable again, only its hash is kept.
 */
function createApiKey({ label, operations, keyNames }) {
  const data = readAll();
  const id = 'ak_' + crypto.randomBytes(6).toString('hex');
  const secret = crypto.randomBytes(24).toString('base64url');
  data.apiKeys.push({
    id,
    label: label || null,
    secretHash: hashSecret(secret),
    operations: operations && operations.length ? operations : ['encrypt', 'decrypt'],
    keyNames: keyNames && keyNames.length ? keyNames : null, // null = all keys allowed
    createdAt: new Date().toISOString(),
    revoked: false,
  });
  writeAll(data);
  return { id, secret }; // caller must display/store `secret` now — it's gone after this
}

function revokeApiKey(id) {
  const data = readAll();
  const entry = data.apiKeys.find((k) => k.id === id);
  if (!entry) {
    const err = new Error(`API key ${id} not found`);
    err.code = 'API_KEY_NOT_FOUND';
    throw err;
  }
  entry.revoked = true;
  entry.revokedAt = new Date().toISOString();
  writeAll(data);
}

/** Given a raw "id.secret" credential string, return the matching key record or null. */
function authenticate(credential) {
  if (!credential || !credential.includes('.')) return null;
  const [id, secret] = credential.split('.');
  const data = readAll();
  const entry = data.apiKeys.find((k) => k.id === id);
  if (!entry || entry.revoked) return null;
  const hash = hashSecret(secret);
  // Constant-time compare to avoid timing side-channels
  const a = Buffer.from(hash);
  const b = Buffer.from(entry.secretHash);
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return null;
  return entry;
}

function isAuthorized(entry, operation, keyName) {
  if (!entry.operations.includes(operation)) return false;
  if (entry.keyNames && !entry.keyNames.includes(keyName)) return false;
  return true;
}

function listApiKeys() {
  const data = readAll();
  return data.apiKeys.map(({ secretHash, ...rest }) => rest); // never expose hashes either
}

module.exports = { createApiKey, revokeApiKey, authenticate, isAuthorized, listApiKeys };
