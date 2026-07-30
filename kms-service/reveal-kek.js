const fs = require('fs');
const crypto = require('crypto');

const keyFilePath = './data/keys/picture-kek.v1.json';
const keyData = JSON.parse(fs.readFileSync(keyFilePath, 'utf8'));

const rootSecretStr = process.env.KMS_ROOT_SECRET;
if (!rootSecretStr) {
    console.error("Error: KMS_ROOT_SECRET environment variable is not set.");
    process.exit(1);
}

try {
    // THIS IS THE FIX: Derive the key exactly how lib/keystore.js does it
    const rootKey = crypto.scryptSync(rootSecretStr, 'kms-root-salt-v1', 32);
    
    const iv = Buffer.from(keyData.iv, 'base64');
    const tag = Buffer.from(keyData.tag, 'base64');
    const ciphertext = Buffer.from(keyData.ct, 'base64');

    const decipher = crypto.createDecipheriv('aes-256-gcm', rootKey, iv);
    decipher.setAuthTag(tag);

    let decrypted = decipher.update(ciphertext);
    decrypted = Buffer.concat([decrypted, decipher.final()]);

    console.log("SUCCESS! Here is your raw KEK:");
    console.log("--------------------------------");
    console.log("Base64 :", decrypted.toString('base64'));
    console.log("Hex    :", decrypted.toString('hex'));
    console.log("--------------------------------");
} catch (error) {
    console.error("Decryption failed! The KMS_ROOT_SECRET is likely incorrect.");
    console.error(error.message);
}