# Standalone KMS Service — Architecture & Security Notes

## What this is

A small, self-hosted Key Management Service exposed over HTTP. Unlike the
earlier "DIY KMS" (a PHP function reading a key file inside the same
process as the rest of the app), this is a **separate service** with its
own process boundary, its own authentication, and its own audit trail.

The application (Laravel) never has file-level access to the KEK. It sends
an HTTP request containing plaintext-to-be-wrapped or ciphertext-to-be-
unwrapped, and gets back the result. The raw key material lives and dies
entirely inside the KMS process.

## Architecture

```
┌─────────────────┐        HTTPS + Bearer token       ┌──────────────────────┐
│   Laravel app    │ ───────────────────────────────► │     KMS service        │
│                  │                                   │  (separate process)    │
│  - never sees    │ ◄─────────────────────────────── │  - holds KEKs           │
│    the KEK       │      wrapped/unwrapped DEK        │  - authenticates every  │
│  - holds DEKs    │                                   │    request              │
│    only briefly, │                                   │  - logs every operation │
│    in memory     │                                   │  - never exposes raw    │
└─────────────────┘                                   │    KEK bytes over the  │
                                                        │    wire                 │
                                                        └──────────────────────┘
```

### Component map

| File | Responsibility |
|---|---|
| `lib/keystore.js` | Stores KEKs, encrypted at rest under a root secret. Handles create/rotate/read. |
| `lib/auth.js` | API key issuance, hashing, and per-key permission checks (IAM-lite). |
| `lib/audit.js` | Append-only, hash-chained log of every operation. Tamper-evident. |
| `lib/server.js` | The HTTP API. Every route requires auth + authorization before touching keys. |
| `cli.js` | Admin-only bootstrap tool — run locally on the KMS host, not over the network. |
| `client/KmsClient.php` | Laravel-side HTTP client. Drop-in replacement for the old `loadKek()`/`decryptWithDEK()`. |

## Security properties this gives you

1. **Process isolation.** A vulnerability in the Laravel app (SQLi, RCE,
   arbitrary file read, etc.) does not automatically expose the KEK,
   because the KEK never exists inside that process or its filesystem.
   The attacker would additionally need to compromise the KMS service
   itself, or steal a valid API credential.

2. **Scoped, revocable credentials.** Each API key is limited to specific
   operations (`encrypt`, `decrypt`, `rotate`, `create_key`, `audit`) and
   optionally to specific named keys. If the Laravel app's credential
   leaks, you revoke *that one credential* — the KEK itself is untouched
   and doesn't need to be rotated.

3. **Authenticated encryption end to end.** Every wrap/unwrap uses
   AES-256-GCM. Tampering with ciphertext, IV, or tag is detected and
   rejected — verified in testing (see "What was actually tested" below).

4. **Tamper-evident audit log.** Every operation — success or failure,
   which key, which version, which credential, from which IP — is
   appended to a hash-chained log. Editing or deleting a past entry breaks
   the hash chain for every entry after it, so tampering with the log
   itself is detectable, not just tampering with the data. Also verified
   in testing.

5. **True key rotation.** `rotate-key` creates a new version and makes it
   active; old versions remain available for decrypting old records.
   Nothing needs to be re-encrypted synchronously.

## What was actually tested

This isn't just a design on paper — the following was run and confirmed
working during development:

- ✅ Health check responds
- ✅ Encrypt → decrypt round-trips correctly (plaintext recovered exactly)
- ✅ Decrypting **tampered ciphertext** correctly fails with an error
  (proves GCM authentication is active, not silently returning garbage)
- ✅ Requests with **no credential** are rejected with 401
- ✅ Key rotation produces a new version while retaining the old one
- ✅ Audit log verifies as intact after normal operations
- ✅ Manually editing one audit log entry **breaks the hash chain**, and
  `verify-audit` correctly detects and reports it

## Honest limitation — read this before your defense

**The root secret problem is not fully solved, and no software-only KMS
solves it.** `KMS_ROOT_SECRET` (an environment variable on the KMS host)
is used to encrypt the KEK files at rest. If someone gets that secret
*and* file access to the KMS host, they can still decrypt every KEK.

This is not a flaw specific to this implementation — it's a structural
property of software-only key management. Real managed KMS products
(AWS KMS, GCP Cloud KMS) solve this with a **Hardware Security Module**: a
physical device that generates and holds keys internally and is designed
so that raw key material *cannot* be extracted from it even by the cloud
provider's own operators. We don't have that hardware, so we don't have
that guarantee.

**What we do have that's still a real improvement over the original
design:**

- The root secret only ever protects KEK *files*. It is never used to
  directly encrypt a photo, and it never touches a DEK's plaintext photo
  data path.
- It lives exclusively in the KMS process's environment — the Laravel
  app, its `.env`, its filesystem, and its codebase have **no path** to
  it at all, not even indirectly.
- Compromising the Laravel application (the much larger, more
  externally-exposed attack surface, since it accepts file uploads,
  handles authentication, renders views, etc.) no longer compromises the
  KEK. An attacker would need to separately compromise the KMS host.
- Every use of the root-secret-derived key is auditable — a real HSM
  gives you hardware-level non-extractability, but it does **not**
  automatically give you an audit trail of every use; you'd still build
  that layer yourself, which is what `audit.js` does here.

### How to phrase this precisely in your thesis

> "This implementation adopts the architectural pattern of a managed KMS —
> a separate trust boundary, authenticated and authorized API access, and
> a tamper-evident audit log — without hardware security module backing.
> The root secret protecting the key store remains a software-level
> secret, which is an inherent limitation of any KMS implementation that
> does not rely on dedicated cryptographic hardware. The security
> contribution of this design is therefore the reduction of attack
> surface and blast radius — isolating key material from the
> application's much larger and more exposed surface area — rather than
> the elimination of the root-secret-protection problem entirely."

That sentence pre-empts the obvious committee question and shows you
understand the tradeoff rather than having missed it.

## Running it

```bash
cd kms-service
npm install

export KMS_ROOT_SECRET="$(openssl rand -base64 32)"   # generate once, store safely, back it up
# Put this same value wherever you'll run the CLI/server from — it must
# match every time, or existing keys become unreadable.

# Bootstrap a named key and an API credential for your Laravel app
node cli.js create-key --name picture-kek
node cli.js create-api-key --label "laravel-app" --ops encrypt,decrypt --keys picture-kek
# ^ copy the printed KMS_API_CREDENTIAL=... line into Laravel's .env

# Start the service
export KMS_PORT=4567
node lib/server.js
```

In Laravel's `.env`:
```
KMS_BASE_URL=http://localhost:4567
KMS_API_CREDENTIAL=ak_xxxxxxxxxxxx.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Copy `client/KmsClient.php` into `app/Services/KmsClient.php`, and replace
`encryptWithDEK()` / `decryptWithDEK()` / `loadKek()` in `UserController`
with the versions in `client/UserController-kms-methods.php`.

## Admin CLI reference

```bash
node cli.js create-key --name <name>
node cli.js rotate-key --name <name>
node cli.js list-keys
node cli.js create-api-key --label <label> --ops encrypt,decrypt,rotate,create_key,audit --keys keyA,keyB
node cli.js revoke-api-key --id ak_xxxxxxxxxxxx
node cli.js list-api-keys
node cli.js verify-audit
```

## Production hardening notes (not implemented here, worth mentioning as future work)

- Run the KMS service on a **separate host/VM** from the Laravel app, with
  a firewall rule only allowing the app server's IP to reach it.
- Serve over HTTPS with a real certificate (currently plain HTTP — fine
  for local dev/thesis demo, not for production).
- Store `KMS_ROOT_SECRET` in a secrets manager or hardware token rather
  than a plain env var, if available.
- Add rate limiting on `/v1/decrypt` to slow down brute-force attempts
  against a stolen credential.
- Ship audit log entries off-host in real time (e.g. to a write-only log
  aggregator) so an attacker who does get the KMS host can't simply
  delete `audit.log.jsonl` to hide their tracks.
