'use strict';

/**
 * KMS HTTP API
 * ------------
 * This is the ONLY way to interact with keys. Raw key material never leaves
 * this process: callers send plaintext to be wrapped and get back
 * ciphertext, or send ciphertext to be unwrapped and get back plaintext —
 * they never see the KEK itself, ever, at any point.
 *
 * Endpoints:
 *   POST   /v1/keys                      create a new named key (version 1)
 *   POST   /v1/keys/:name/rotate         rotate to a new version
 *   GET    /v1/keys                      list keys + active versions (admin)
 *   POST   /v1/encrypt                   wrap a short plaintext (e.g. a DEK)
 *   POST   /v1/decrypt                   unwrap a ciphertext back to plaintext
 *   GET    /v1/audit                     read the audit log (admin)
 *   GET    /v1/audit/verify              verify the audit log hash chain
 *
 * Auth: header  Authorization: Bearer <api_key_id>.<secret>
 */

const express = require('express');
const crypto = require('crypto');
const keystore = require('./keystore');
const audit = require('./audit');
const auth = require('./auth');

const app = express();
app.use(express.json({ limit: '1mb' }));

// ---- auth middleware -------------------------------------------------

function requireAuth(operation) {
  return (req, res, next) => {
    const header = req.headers['authorization'] || '';
    const credential = header.startsWith('Bearer ') ? header.slice(7) : null;
    const entry = auth.authenticate(credential);

    if (!entry) {
      audit.record({
        apiKeyId: null,
        operation,
        keyName: req.body && req.body.keyName,
        success: false,
        detail: 'authentication failed',
        ip: req.ip,
      });
      return res.status(401).json({ error: 'unauthorized' });
    }

    const keyName = req.body?.keyName || req.params?.name;
    if (!auth.isAuthorized(entry, operation, keyName)) {
      audit.record({
        apiKeyId: entry.id,
        operation,
        keyName,
        success: false,
        detail: 'forbidden: operation or key not permitted for this API key',
        ip: req.ip,
      });
      return res.status(403).json({ error: 'forbidden' });
    }

    req.apiKeyEntry = entry;
    next();
  };
}

// ---- key management ----------------------------------------------------

app.post('/v1/keys', requireAuth('create_key'), (req, res) => {
  const { name } = req.body;
  if (!name) return res.status(400).json({ error: 'name is required' });
  try {
    const result = keystore.createKey(name);
    audit.record({
      apiKeyId: req.apiKeyEntry.id,
      operation: 'create_key',
      keyName: name,
      keyVersion: result.version,
      success: true,
      ip: req.ip,
    });
    res.status(201).json(result);
  } catch (err) {
    audit.record({
      apiKeyId: req.apiKeyEntry.id,
      operation: 'create_key',
      keyName: name,
      success: false,
      detail: err.message,
      ip: req.ip,
    });
    res.status(err.code === 'KEY_EXISTS' ? 409 : 500).json({ error: err.message });
  }
});

app.post('/v1/keys/:name/rotate', requireAuth('rotate'), (req, res) => {
  const { name } = req.params;
  try {
    const result = keystore.rotateKey(name);
    audit.record({
      apiKeyId: req.apiKeyEntry.id,
      operation: 'rotate',
      keyName: name,
      keyVersion: result.version,
      success: true,
      ip: req.ip,
    });
    res.json(result);
  } catch (err) {
    audit.record({
      apiKeyId: req.apiKeyEntry.id,
      operation: 'rotate',
      keyName: name,
      success: false,
      detail: err.message,
      ip: req.ip,
    });
    res.status(err.code === 'KEY_NOT_FOUND' ? 404 : 500).json({ error: err.message });
  }
});

app.get('/v1/keys', requireAuth('audit'), (req, res) => {
  res.json({ keys: keystore.listKeys() });
});

// ---- encrypt / decrypt (the actual envelope operations) ---------------

app.post('/v1/encrypt', requireAuth('encrypt'), (req, res) => {
  const { keyName, plaintextBase64 } = req.body;
  if (!keyName || !plaintextBase64) {
    return res.status(400).json({ error: 'keyName and plaintextBase64 are required' });
  }
  let dek;
  try {
    const version = keystore.getActiveVersion(keyName);
    dek = keystore.getKeyMaterial(keyName, version);

    const iv = crypto.randomBytes(12);
    const cipher = crypto.createCipheriv('aes-256-gcm', dek, iv);
    const plaintext = Buffer.from(plaintextBase64, 'base64');
    const ct = Buffer.concat([cipher.update(plaintext), cipher.final()]);
    const tag = cipher.getAuthTag();

    audit.record({
      apiKeyId: req.apiKeyEntry.id,
      operation: 'encrypt',
      keyName,
      keyVersion: version,
      success: true,
      ip: req.ip,
    });

    res.json({
      ciphertext: ct.toString('base64'),
      iv: iv.toString('base64'),
      tag: tag.toString('base64'),
      keyVersion: version,
    });
  } catch (err) {
    audit.record({
      apiKeyId: req.apiKeyEntry.id,
      operation: 'encrypt',
      keyName,
      success: false,
      detail: err.message,
      ip: req.ip,
    });
    res.status(err.code === 'KEY_NOT_FOUND' ? 404 : 500).json({ error: err.message });
  } finally {
    if (dek) dek.fill(0); // wipe key material from memory immediately
  }
});

app.post('/v1/decrypt', requireAuth('decrypt'), (req, res) => {
  const { keyName, keyVersion, ciphertext, iv, tag } = req.body;
  if (!keyName || !keyVersion || !ciphertext || !iv || !tag) {
    return res.status(400).json({ error: 'keyName, keyVersion, ciphertext, iv, tag are required' });
  }
  let dek;
  try {
    dek = keystore.getKeyMaterial(keyName, keyVersion);
    const decipher = crypto.createDecipheriv('aes-256-gcm', dek, Buffer.from(iv, 'base64'));
    decipher.setAuthTag(Buffer.from(tag, 'base64'));
    const pt = Buffer.concat([
      decipher.update(Buffer.from(ciphertext, 'base64')),
      decipher.final(),
    ]);

    audit.record({
      apiKeyId: req.apiKeyEntry.id,
      operation: 'decrypt',
      keyName,
      keyVersion,
      success: true,
      ip: req.ip,
    });

    res.json({ plaintextBase64: pt.toString('base64') });
  } catch (err) {
    audit.record({
      apiKeyId: req.apiKeyEntry.id,
      operation: 'decrypt',
      keyName,
      keyVersion,
      success: false,
      detail: err.code === 'KEY_VERSION_NOT_FOUND' ? err.message : 'decryption failed (bad key, tampered ciphertext, or wrong tag)',
      ip: req.ip,
    });
    const status = err.code === 'KEY_VERSION_NOT_FOUND' ? 404 : 400;
    res.status(status).json({ error: 'decryption failed' });
  } finally {
    if (dek) dek.fill(0);
  }
});

// ---- audit log ----------------------------------------------------------

app.get('/v1/audit', requireAuth('audit'), (req, res) => {
  res.json({ entries: audit.readAll() });
});

app.get('/v1/audit/verify', requireAuth('audit'), (req, res) => {
  res.json(audit.verifyChain());
});

// ---- health ---------------------------------------------------------

app.get('/health', (req, res) => res.json({ ok: true }));

const PORT = process.env.KMS_PORT || 4567;
app.listen(PORT, () => {
  console.log(`KMS service listening on port ${PORT}`);
});

module.exports = app;
