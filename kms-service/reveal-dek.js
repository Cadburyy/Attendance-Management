const fs = require('fs');
const crypto = require('crypto');

// 1. The raw KEK we proved earlier
const rawKek = Buffer.from('4Z6AkmZtDbK55KVDYx6tX4UgUUk4ZcFX75+8CTPmyIQ=', 'base64');

// 2. Automatically read the payload from the Laravel folder
// (Assuming kms-service is inside your Attendance-Management folder)
let payload;
try {
    const fileData = fs.readFileSync('../payload.json', 'utf8');
    payload = JSON.parse(fileData);
} catch (error) {
    console.error("Could not find or parse ../payload.json. Did you run the tinker command?");
    process.exit(1);
}

try {
    // 3. Extract the exact strings straight from the JSON
    const edek = Buffer.from(payload.edek, 'base64');
    const dek_iv = Buffer.from(payload.dek_iv, 'base64');
    const dek_tag = Buffer.from(payload.dek_tag, 'base64');

    // 4. Decrypt
    const decipher = crypto.createDecipheriv('aes-256-gcm', rawKek, dek_iv);
    decipher.setAuthTag(dek_tag);

    let dek = decipher.update(edek);
    dek = Buffer.concat([dek, decipher.final()]);

    console.log("SUCCESS! Here is your plaintext DEK:");
    console.log("--------------------------------");
    console.log("DEK (Base64):", dek.toString('base64'));
    console.log("DEK (Hex)   :", dek.toString('hex'));
    console.log("--------------------------------");
    
} catch (error) {
    console.error("Failed to decrypt DEK. The KEK doesn't match this payload.");
    console.error(error.message);
}