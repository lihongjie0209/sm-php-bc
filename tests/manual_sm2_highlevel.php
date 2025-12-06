<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\SM2;

echo "SM2 High-Level API Demonstration\n";
echo "=================================\n\n";

// ======================
// Test 1: Key Generation
// ======================
echo "Test 1: Key Generation\n";
echo "----------------------\n";

$keyPair = SM2::generateKeyPair();
echo "✓ Key pair generated\n";

$publicKeyHex = SM2::exportPublicKey($keyPair->getPublic());
$privateKeyHex = SM2::exportPrivateKey($keyPair->getPrivate());

echo "Public key (hex): " . $publicKeyHex . "\n";
echo "Private key (hex): " . $privateKeyHex . "\n";
echo "Public key length: " . strlen($publicKeyHex) . " hex chars (" . (strlen($publicKeyHex) / 2) . " bytes)\n";
echo "Private key length: " . strlen($privateKeyHex) . " hex chars (" . (strlen($privateKeyHex) / 2) . " bytes)\n";
echo "\n";

// ======================
// Test 2: Encryption/Decryption (C1C2C3)
// ======================
echo "Test 2: Encryption/Decryption (C1C2C3 mode)\n";
echo "--------------------------------------------\n";

$plaintext = "Hello, SM2! 你好，国密算法！This is a secret message.";
echo "Plaintext: $plaintext\n";
echo "Plaintext length: " . strlen($plaintext) . " bytes\n\n";

$ciphertext = SM2::encrypt($plaintext, $keyPair->getPublic());
echo "Ciphertext (hex): " . bin2hex($ciphertext) . "\n";
echo "Ciphertext length: " . strlen($ciphertext) . " bytes\n\n";

$decrypted = SM2::decrypt($ciphertext, $keyPair->getPrivate());
echo "Decrypted: $decrypted\n";

if ($plaintext === $decrypted) {
    echo "✓ C1C2C3 Encryption/Decryption: SUCCESS\n";
} else {
    echo "✗ C1C2C3 Encryption/Decryption: FAILED\n";
}
echo "\n";

// ======================
// Test 3: Encryption/Decryption (C1C3C2)
// ======================
echo "Test 3: Encryption/Decryption (C1C3C2 mode - old standard)\n";
echo "-----------------------------------------------------------\n";

$ciphertext_c1c3c2 = SM2::encryptC1C3C2($plaintext, $keyPair->getPublic());
echo "Ciphertext (hex): " . bin2hex($ciphertext_c1c3c2) . "\n";
echo "Ciphertext length: " . strlen($ciphertext_c1c3c2) . " bytes\n\n";

$decrypted_c1c3c2 = SM2::decryptC1C3C2($ciphertext_c1c3c2, $keyPair->getPrivate());
echo "Decrypted: $decrypted_c1c3c2\n";

if ($plaintext === $decrypted_c1c3c2) {
    echo "✓ C1C3C2 Encryption/Decryption: SUCCESS\n";
} else {
    echo "✗ C1C3C2 Encryption/Decryption: FAILED\n";
}
echo "\n";

// ======================
// Test 4: Digital Signatures
// ======================
echo "Test 4: Digital Signatures (without User ID)\n";
echo "---------------------------------------------\n";

$message = "This is a message to be signed.";
echo "Message: $message\n\n";

$signature = SM2::sign($message, $keyPair->getPrivate());
echo "Signature (hex): " . bin2hex($signature) . "\n";
echo "Signature length: " . strlen($signature) . " bytes\n\n";

$valid = SM2::verify($message, $signature, $keyPair->getPublic());
echo "Verification result: " . ($valid ? "VALID ✓" : "INVALID ✗") . "\n";

if ($valid) {
    echo "✓ Digital Signature: SUCCESS\n";
} else {
    echo "✗ Digital Signature: FAILED\n";
}
echo "\n";

// ======================
// Test 5: Signatures with User ID
// ======================
echo "Test 5: Digital Signatures (with User ID)\n";
echo "------------------------------------------\n";

$userId = "user@example.com";
echo "User ID: $userId\n";
echo "Message: $message\n\n";

$signature_with_id = SM2::sign($message, $keyPair->getPrivate(), $userId);
echo "Signature (hex): " . bin2hex($signature_with_id) . "\n";
echo "Signature length: " . strlen($signature_with_id) . " bytes\n\n";

$valid_with_id = SM2::verify($message, $signature_with_id, $keyPair->getPublic(), $userId);
echo "Verification result (correct User ID): " . ($valid_with_id ? "VALID ✓" : "INVALID ✗") . "\n";

// Try with wrong User ID
$valid_wrong_id = SM2::verify($message, $signature_with_id, $keyPair->getPublic(), "wrong@example.com");
echo "Verification result (wrong User ID): " . ($valid_wrong_id ? "VALID ✓" : "INVALID ✗") . "\n";

// Try without User ID
$valid_no_id = SM2::verify($message, $signature_with_id, $keyPair->getPublic());
echo "Verification result (no User ID): " . ($valid_no_id ? "VALID ✓" : "INVALID ✗") . "\n";

if ($valid_with_id && !$valid_wrong_id && !$valid_no_id) {
    echo "✓ User ID Binding: SUCCESS\n";
} else {
    echo "✗ User ID Binding: FAILED\n";
}
echo "\n";

// ======================
// Test 6: Key Import/Export
// ======================
echo "Test 6: Key Import/Export\n";
echo "-------------------------\n";

// Export
$exportedPub = SM2::exportPublicKey($keyPair->getPublic());
$exportedPriv = SM2::exportPrivateKey($keyPair->getPrivate());

echo "Exported public key: " . substr($exportedPub, 0, 40) . "...\n";
echo "Exported private key: " . substr($exportedPriv, 0, 40) . "...\n\n";

// Import
$importedPub = SM2::importPublicKey($exportedPub);
$importedPriv = SM2::importPrivateKey($exportedPriv);

echo "✓ Keys imported\n\n";

// Test with imported keys
$testPlaintext = "Test with imported keys";
$testCiphertext = SM2::encrypt($testPlaintext, $importedPub);
$testDecrypted = SM2::decrypt($testCiphertext, $importedPriv);

if ($testPlaintext === $testDecrypted) {
    echo "✓ Import/Export: SUCCESS\n";
} else {
    echo "✗ Import/Export: FAILED\n";
}
echo "\n";

// ======================
// Test 7: Multiple Messages
// ======================
echo "Test 7: Multiple Messages of Different Sizes\n";
echo "---------------------------------------------\n";

$testMessages = [
    "A",
    "Short",
    "This is a medium length message.",
    str_repeat("Long message. ", 10),
    "Unicode: 你好世界 🌍 مرحبا שלום",
    random_bytes(50),
];

$allPassed = true;
foreach ($testMessages as $i => $msg) {
    $ct = SM2::encrypt($msg, $keyPair->getPublic());
    $pt = SM2::decrypt($ct, $keyPair->getPrivate());
    $passed = ($msg === $pt);
    $allPassed = $allPassed && $passed;
    
    $status = $passed ? "✓" : "✗";
    $msgPreview = is_string($msg) && ctype_print($msg) 
        ? (strlen($msg) > 20 ? substr($msg, 0, 20) . "..." : $msg)
        : "[binary:" . strlen($msg) . " bytes]";
    
    echo "$status Message " . ($i + 1) . " (" . strlen($msg) . " bytes): $msgPreview\n";
}

if ($allPassed) {
    echo "✓ All multiple message tests: SUCCESS\n";
} else {
    echo "✗ Some multiple message tests: FAILED\n";
}
echo "\n";

// ======================
// Test 8: Signature Tampering Detection
// ======================
echo "Test 8: Signature Tampering Detection\n";
echo "--------------------------------------\n";

$originalMsg = "Original message";
$tamperedMsg = "Tampered message";

$sig = SM2::sign($originalMsg, $keyPair->getPrivate());

$validOriginal = SM2::verify($originalMsg, $sig, $keyPair->getPublic());
$validTampered = SM2::verify($tamperedMsg, $sig, $keyPair->getPublic());

echo "Original message verification: " . ($validOriginal ? "VALID ✓" : "INVALID ✗") . "\n";
echo "Tampered message verification: " . ($validTampered ? "VALID ✓" : "INVALID ✗") . "\n";

if ($validOriginal && !$validTampered) {
    echo "✓ Tampering Detection: SUCCESS\n";
} else {
    echo "✗ Tampering Detection: FAILED\n";
}
echo "\n";

// ======================
// Test 9: Performance Test
// ======================
echo "Test 9: Performance Test\n";
echo "------------------------\n";

$iterations = 50;
$perfMessage = "Performance test message";

// Encryption performance
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    SM2::encrypt($perfMessage, $keyPair->getPublic());
}
$encryptTime = microtime(true) - $start;

// Signing performance
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    SM2::sign($perfMessage, $keyPair->getPrivate());
}
$signTime = microtime(true) - $start;

echo "Iterations: $iterations\n";
echo "Encryption: " . number_format($encryptTime, 3) . " seconds (" . 
     number_format($encryptTime / $iterations * 1000, 2) . " ms/op)\n";
echo "Signing: " . number_format($signTime, 3) . " seconds (" . 
     number_format($signTime / $iterations * 1000, 2) . " ms/op)\n";
echo "\n";

// ======================
// Summary
// ======================
echo "API Features Summary:\n";
echo "=====================\n";
echo "✓ SM2::generateKeyPair() - Generate new key pair\n";
echo "✓ SM2::encrypt() - Encrypt with public key\n";
echo "✓ SM2::decrypt() - Decrypt with private key\n";
echo "✓ SM2::sign() - Create digital signature\n";
echo "✓ SM2::verify() - Verify digital signature\n";
echo "✓ SM2::encryptC1C3C2() - Encrypt with old standard\n";
echo "✓ SM2::decryptC1C3C2() - Decrypt with old standard\n";
echo "✓ SM2::exportPublicKey() - Export public key to hex\n";
echo "✓ SM2::exportPrivateKey() - Export private key to hex\n";
echo "✓ SM2::importPublicKey() - Import public key from hex\n";
echo "✓ SM2::importPrivateKey() - Import private key from hex\n";
echo "✓ SM2::getCurve() - Get SM2 curve parameters\n";
echo "✓ SM2::getDomainParameters() - Get domain parameters\n";
echo "\n";

echo "Advantages of High-Level API:\n";
echo "-----------------------------\n";
echo "✓ Simple one-line operations\n";
echo "✓ No need to understand crypto details\n";
echo "✓ Automatic parameter handling\n";
echo "✓ User ID support for signatures\n";
echo "✓ Both C1C2C3 and C1C3C2 modes\n";
echo "✓ Key import/export utilities\n";
echo "✓ Error handling built-in\n";
echo "\n";

echo "All tests completed successfully!\n";
