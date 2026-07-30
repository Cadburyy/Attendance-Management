const fs = require('fs');
const crypto = require('crypto');

// 1. The plaintext DEK you JUST extracted!
const plaintextDek = Buffer.from('zLjVpEf2sn5S9YQp+zd+bBob3LM8PzTYV5AFELFYiPg=', 'base64');

// 2. Read the exact same payload.json from your Laravel folder
const payload = JSON.parse(fs.readFileSync('../payload.json', 'utf8'));

try {
    // 3. Extract the locked image parts from the envelope
    const encryptedData = Buffer.from(payload.data, 'base64');
    const iv = Buffer.from(payload.iv, 'base64');
    const tag = Buffer.from(payload.tag, 'base64');

    // 4. Set up the AES-256-GCM decipher using the DEK
    const decipher = crypto.createDecipheriv('aes-256-gcm', plaintextDek, iv);
    decipher.setAuthTag(tag);

    // 5. Decrypt the actual image data!
    let decryptedImage = decipher.update(encryptedData);
    decryptedImage = Buffer.concat([decryptedImage, decipher.final()]);

    // 6. Save the decrypted data to a file so you can see it
    fs.writeFileSync('unlocked-photo.txt', decryptedImage);

    console.log("=====================================================");
    console.log("🎉 SUCCESS! THE ENVELOPE IS COMPLETELY UNWRAPPED! 🎉");
    console.log("=====================================================");
    console.log("The raw image data has been saved to 'unlocked-photo.txt'.");
    
    // Let's print the first 100 characters to see what format it is
    console.log("Preview: " + decryptedImage.toString('utf8').substring(0, 100) + "...");
    
} catch (error) {
    console.error("Failed to decrypt the image data.");
    console.error(error.message);
}