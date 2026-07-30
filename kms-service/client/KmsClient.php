<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * KmsClient
 * ---------
 * Talks to the standalone KMS service over HTTP. This class NEVER has
 * access to the KEK — it only ever sends/receives already-encrypted DEKs.
 * That's the entire point: even if this Laravel app is fully compromised,
 * the attacker gets your app's own credential (scoped, revocable, logged),
 * not the KEK itself.
 *
 * Usage in UserController (replaces loadKek() + manual openssl calls):
 *
 *   $kms = new KmsClient();
 *   $wrapped = $kms->encrypt('picture-kek', $dek);      // wrap a DEK
 *   $dek     = $kms->decrypt('picture-kek', $version, $wrapped);  // unwrap it
 */
class KmsClient
{
    private string $baseUrl;
    private string $credential;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('KMS_BASE_URL', 'http://localhost:4567'), '/');
        $this->credential = env('KMS_API_CREDENTIAL');

        if (!$this->credential) {
            throw new \RuntimeException('KMS_API_CREDENTIAL is not set in .env');
        }
    }

    private function client()
    {
        return Http::withToken($this->credential)
            ->timeout(5)
            ->baseUrl($this->baseUrl);
    }

    /**
     * Wraps raw key material (e.g. a 32-byte DEK) using the named KEK's
     * currently active version. Returns everything needed to unwrap it
     * later, including which key version was used.
     *
     * @param string $keyName   e.g. 'picture-kek'
     * @param string $plaintext raw bytes (e.g. the DEK)
     * @return array{ciphertext:string, iv:string, tag:string, keyVersion:int}
     */
    public function encrypt(string $keyName, string $plaintext): array
    {
        $response = $this->client()->post('/v1/encrypt', [
            'keyName' => $keyName,
            'plaintextBase64' => base64_encode($plaintext),
        ]);

        if (!$response->successful()) {
            Log::error('KMS encrypt failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('KMS encrypt operation failed');
        }

        return $response->json();
    }

    /**
     * Unwraps a previously-wrapped ciphertext back into raw plaintext bytes.
     * Throws if the ciphertext/tag don't match (tampering, wrong key, etc).
     *
     * @return string raw plaintext bytes (e.g. the DEK) — caller should
     *                 wipe this from memory as soon as it's used, the same
     *                 way the original code called sodium_memzero()
     */
    public function decrypt(string $keyName, int $keyVersion, string $ciphertext, string $iv, string $tag): string
    {
        $response = $this->client()->post('/v1/decrypt', [
            'keyName' => $keyName,
            'keyVersion' => $keyVersion,
            'ciphertext' => $ciphertext,
            'iv' => $iv,
            'tag' => $tag,
        ]);

        if (!$response->successful()) {
            Log::warning('KMS decrypt failed', ['status' => $response->status(), 'keyName' => $keyName, 'keyVersion' => $keyVersion]);
            throw new \RuntimeException('KMS decrypt operation failed (tampered data, wrong key, or wrong version)');
        }

        return base64_decode($response->json('plaintextBase64'));
    }

    /** Admin/ops helper — not used on the hot path of encrypt/decrypt. */
    public function rotate(string $keyName): array
    {
        $response = $this->client()->post("/v1/keys/{$keyName}/rotate");
        if (!$response->successful()) {
            throw new \RuntimeException('KMS rotate operation failed');
        }
        return $response->json();
    }
}
