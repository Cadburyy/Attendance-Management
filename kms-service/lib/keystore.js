'use strict';

/**
 * Keystore
 * --------
 * Manages named master keys ("KEKs"), each with one or more versions.
 * Every key VERSION is 32 random bytes, generated once and never reused.
 *
 * At rest, each key version is stored as its own small JSON file, encrypted
 * with AES-256-GCM under a single ROOT SECRET (KMS_ROOT_SECRET env var).
 *
 * IMPORTANT / HONEST LIMITATION (explain this in your defense):
 * A software-only KMS still needs *one* secret to bootstrap everything —
 * here, that's KMS_ROOT_SECRET. Real managed KMS products solve this with
 * an HSM (a physical device that never releases raw key material at all).
 * We don't have that. What we DO have that the old design didn't:
 *   - the root secret only unlocks per-VERSION key files, never the DEKs
 *     or plaintext photos directly
 *   - it lives only inside this service's process, isolated by a network
 *     boundary, with its own auth and audit trail
 *   - losing the app server (Laravel) does NOT expose this secret at all,
 *     since the app never has file access to it or to the KEK material
 */

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const DATA_DIR = path.join(__dirname, '..', 'data', 'keys');
const META_FILE = path.join(__dirname, '..', 'data', 'keys-meta.json');

function rootKey() {
  const secret = process.env.KMS_ROOT_SECRET;
  if (!secret) {
    throw new Error('KMS_ROOT_SECRET is not set. Refusing to start.');
  }
  // Derive a 32-byte key from the root secret (scrypt is deliberately slow,
  // which is desirable here since this only runs at boot / key ops, not
  // per-request on hot paths).
  return crypto.scryptSync(secret, 'kms-root-salt-v1', 32);
}

function ensureDirs() {
  fs.mkdirSync(DATA_DIR, { recursive: true });
  if (!fs.existsSync(META_FILE)) {
    fs.writeFileSync(META_FILE, JSON.stringify({ keys: {} }, null, 2));
  }
}

function readMeta() {
  ensureDirs();
  return JSON.parse(fs.readFileSync(META_FILE, 'utf8'));
}

function writeMeta(meta) {
  fs.writeFileSync(META_FILE, JSON.stringify(meta, null, 2));
}

function encryptAtRest(plaintextBuf) {
  const key = rootKey();
  const iv = crypto.randomBytes(12);
  const cipher = crypto.createCipheriv('aes-256-gcm', key, iv);
  const ct = Buffer.concat([cipher.update(plaintextBuf), cipher.final()]);
  const tag = cipher.getAuthTag();
  return { ct: ct.toString('base64'), iv: iv.toString('base64'), tag: tag.toString('base64') };
}

function decryptAtRest(record) {
  const key = rootKey();
  const decipher = crypto.createDecipheriv('aes-256-gcm', key, Buffer.from(record.iv, 'base64'));
  decipher.setAuthTag(Buffer.from(record.tag, 'base64'));
  const pt = Buffer.concat([
    decipher.update(Buffer.from(record.ct, 'base64')),
    decipher.final(),
  ]);
  return pt;
}

function keyVersionFilePath(name, version) {
  const safeName = name.replace(/[^a-zA-Z0-9_-]/g, '_');
  return path.join(DATA_DIR, `${safeName}.v${version}.json`);
}

/** Create a brand-new named key with version 1. Errors if it already exists. */
function createKey(name) {
  const meta = readMeta();
  if (meta.keys[name]) {
    const err = new Error(`Key "${name}" already exists`);
    err.code = 'KEY_EXISTS';
    throw err;
  }
  const raw = crypto.randomBytes(32);
  const encrypted = encryptAtRest(raw);
  fs.writeFileSync(keyVersionFilePath(name, 1), JSON.stringify(encrypted));
  raw.fill(0); // wipe plaintext key material from memory ASAP

  meta.keys[name] = { activeVersion: 1, versions: [1], createdAt: new Date().toISOString() };
  writeMeta(meta);
  return { name, version: 1 };
}

/** Rotate a key: generates a new version and makes it the active one. */
function rotateKey(name) {
  const meta = readMeta();
  const entry = meta.keys[name];
  if (!entry) {
    const err = new Error(`Key "${name}" not found`);
    err.code = 'KEY_NOT_FOUND';
    throw err;
  }
  const newVersion = Math.max(...entry.versions) + 1;
  const raw = crypto.randomBytes(32);
  const encrypted = encryptAtRest(raw);
  fs.writeFileSync(keyVersionFilePath(name, newVersion), JSON.stringify(encrypted));
  raw.fill(0);

  entry.versions.push(newVersion);
  entry.activeVersion = newVersion;
  entry.rotatedAt = new Date().toISOString();
  writeMeta(meta);
  return { name, version: newVersion };
}

/** Get the raw key bytes for a given name+version. Caller must wipe the buffer after use. */
function getKeyMaterial(name, version) {
  const meta = readMeta();
  const entry = meta.keys[name];
  if (!entry || !entry.versions.includes(version)) {
    const err = new Error(`Key "${name}" version ${version} not found`);
    err.code = 'KEY_VERSION_NOT_FOUND';
    throw err;
  }
  const filePath = keyVersionFilePath(name, version);
  const record = JSON.parse(fs.readFileSync(filePath, 'utf8'));
  return decryptAtRest(record); // Buffer, 32 bytes
}

function getActiveVersion(name) {
  const meta = readMeta();
  const entry = meta.keys[name];
  if (!entry) {
    const err = new Error(`Key "${name}" not found`);
    err.code = 'KEY_NOT_FOUND';
    throw err;
  }
  return entry.activeVersion;
}

function listKeys() {
  const meta = readMeta();
  return Object.entries(meta.keys).map(([name, v]) => ({
    name,
    activeVersion: v.activeVersion,
    versions: v.versions,
    createdAt: v.createdAt,
    rotatedAt: v.rotatedAt || null,
  }));
}

module.exports = {
  createKey,
  rotateKey,
  getKeyMaterial,
  getActiveVersion,
  listKeys,
};
