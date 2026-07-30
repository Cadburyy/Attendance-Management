#!/usr/bin/env node
'use strict';

/**
 * Admin CLI — run these directly on the server hosting the KMS, not over
 * the network. This is how you bootstrap the very first API key (since the
 * HTTP API requires auth to do anything, including creating API keys).
 *
 * Usage:
 *   node cli.js create-api-key --label "laravel-app" --ops encrypt,decrypt --keys picture-kek
 *   node cli.js revoke-api-key --id ak_abc123
 *   node cli.js list-api-keys
 *   node cli.js create-key --name picture-kek
 *   node cli.js rotate-key --name picture-kek
 *   node cli.js list-keys
 *   node cli.js verify-audit
 */

const keystore = require('./lib/keystore');
const auth = require('./lib/auth');
const audit = require('./lib/audit');

function parseArgs(argv) {
  const out = {};
  for (let i = 0; i < argv.length; i++) {
    if (argv[i].startsWith('--')) {
      const key = argv[i].slice(2);
      const val = argv[i + 1] && !argv[i + 1].startsWith('--') ? argv[++i] : true;
      out[key] = val;
    }
  }
  return out;
}

function main() {
  if (!process.env.KMS_ROOT_SECRET) {
    console.error('ERROR: KMS_ROOT_SECRET env var must be set before running this CLI.');
    process.exit(1);
  }

  const [, , cmd, ...rest] = process.argv;
  const args = parseArgs(rest);

  switch (cmd) {
    case 'create-api-key': {
      const operations = args.ops ? String(args.ops).split(',') : ['encrypt', 'decrypt'];
      const keyNames = args.keys ? String(args.keys).split(',') : null;
      const { id, secret } = auth.createApiKey({ label: args.label, operations, keyNames });
      console.log('API key created. SAVE THE SECRET NOW — it will never be shown again.\n');
      console.log('  id:     ', id);
      console.log('  secret: ', secret);
      console.log('\n  Credential to use in Laravel .env:');
      console.log(`  KMS_API_CREDENTIAL=${id}.${secret}`);
      break;
    }
    case 'revoke-api-key': {
      auth.revokeApiKey(args.id);
      console.log(`Revoked ${args.id}`);
      break;
    }
    case 'list-api-keys': {
      console.table(auth.listApiKeys());
      break;
    }
    case 'create-key': {
      const result = keystore.createKey(args.name);
      console.log('Created key:', result);
      break;
    }
    case 'rotate-key': {
      const result = keystore.rotateKey(args.name);
      console.log('Rotated key:', result);
      break;
    }
    case 'list-keys': {
      console.table(keystore.listKeys());
      break;
    }
    case 'verify-audit': {
      console.log(audit.verifyChain());
      break;
    }
    default:
      console.log(`Unknown command: ${cmd}\n`);
      console.log('Commands: create-api-key, revoke-api-key, list-api-keys, create-key, rotate-key, list-keys, verify-audit');
      process.exit(1);
  }
}

main();
