<?php

// This shows ONLY the changed methods from UserController.php — swap these
// in place of encryptWithDEK() / decryptWithDEK() / loadKek().
//
// Everything else in the controller (store, update, showPicture's structure)
// stays the same. The only difference: the KEK-wrapping step now goes over
// HTTP to the KMS service instead of reading a local file.

use App\Services\KmsClient;

class UserController extends Controller
{
    private const PICTURE_KMS_KEY = 'picture-kek';

    private function encryptWithDEK($data)
    {
        $kms = new KmsClient();

        $dek = random_bytes(32);
        $dataIv = random_bytes(12);
        $dataTag = '';
        $encryptedData = openssl_encrypt(
            $data, 'aes-256-gcm', $dek, OPENSSL_RAW_DATA, $dataIv, $dataTag
        );

        // Instead of loadKek() + openssl_encrypt() locally, ask the KMS
        // service to wrap the DEK. The KEK itself never enters this process.
        $wrapped = $kms->encrypt(self::PICTURE_KMS_KEY, $dek);

        sodium_memzero($dek);

        return json_encode([
            'data'        => base64_encode($encryptedData),
            'iv'          => base64_encode($dataIv),
            'tag'         => base64_encode($dataTag),
            'edek'        => $wrapped['ciphertext'],
            'dek_iv'      => $wrapped['iv'],
            'dek_tag'     => $wrapped['tag'],
            'kek_version' => $wrapped['keyVersion'],
        ]);
    }

    private function decryptWithDEK(array $payload)
    {
        $kms = new KmsClient();

        // Ask the KMS service to unwrap the DEK. If the ciphertext, IV, or
        // tag don't match (tampering, wrong version, etc), this throws.
        try {
            $dek = $kms->decrypt(
                self::PICTURE_KMS_KEY,
                (int) $payload['kek_version'],
                $payload['edek'],
                $payload['dek_iv'],
                $payload['dek_tag']
            );
        } catch (\RuntimeException $e) {
            abort(403, 'Failed to unwrap DEK via KMS. Incorrect key version or tampered data.');
        }

        $decrypted = openssl_decrypt(
            base64_decode($payload['data']), 'aes-256-gcm', $dek,
            OPENSSL_RAW_DATA, base64_decode($payload['iv']), base64_decode($payload['tag'])
        );

        sodium_memzero($dek);

        if ($decrypted === false) {
            abort(403, 'Failed to decrypt picture data — data may be tampered.');
        }

        return $decrypted;
    }
}
