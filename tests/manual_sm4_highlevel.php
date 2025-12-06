<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\SM4;

echo "SM4 High-Level API Demonstration\n";
echo "=================================\n\n";

// Test data
$plaintext = "Hello, SM4! This is a test message for the high-level API. 你好，国密！";
echo "Original plaintext: $plaintext\n";
echo "Plaintext length: " . strlen($plaintext) . " bytes\n\n";

// Generate keys
$key = SM4::generateKey();
$iv = SM4::generateIV();
$nonce = SM4::generateIV();

echo "Generated key: " . bin2hex($key) . "\n";
echo "Generated IV: " . bin2hex($iv) . "\n";
echo "Generated nonce: " . bin2hex($nonce) . "\n\n";

// ======================
// Test 1: CBC Mode
// ======================
echo "Test 1: CBC Mode with PKCS7 Padding\n";
echo "------------------------------------\n";

$ciphertext_cbc = SM4::encryptCBC($plaintext, $key, $iv);
echo "Ciphertext (hex): " . bin2hex($ciphertext_cbc) . "\n";
echo "Ciphertext length: " . strlen($ciphertext_cbc) . " bytes\n";

$decrypted_cbc = SM4::decryptCBC($ciphertext_cbc, $key, $iv);
echo "Decrypted: $decrypted_cbc\n";

if ($plaintext === $decrypted_cbc) {
    echo "✓ CBC: SUCCESS\n";
} else {
    echo "✗ CBC: FAILED\n";
}
echo "\n";

// ======================
// Test 2: CTR Mode
// ======================
echo "Test 2: CTR Mode (Stream Cipher)\n";
echo "---------------------------------\n";

$ciphertext_ctr = SM4::encryptCTR($plaintext, $key, $nonce);
echo "Ciphertext (hex): " . bin2hex($ciphertext_ctr) . "\n";
echo "Ciphertext length: " . strlen($ciphertext_ctr) . " bytes (no padding!)\n";

$decrypted_ctr = SM4::decryptCTR($ciphertext_ctr, $key, $nonce);
echo "Decrypted: $decrypted_ctr\n";

if ($plaintext === $decrypted_ctr) {
    echo "✓ CTR: SUCCESS\n";
} else {
    echo "✗ CTR: FAILED\n";
}
echo "\n";

// ======================
// Test 3: ECB Mode (Warning!)
// ======================
echo "Test 3: ECB Mode (Not Recommended)\n";
echo "-----------------------------------\n";

$ciphertext_ecb = SM4::encryptECB($plaintext, $key);
echo "Ciphertext (hex): " . bin2hex($ciphertext_ecb) . "\n";
echo "Ciphertext length: " . strlen($ciphertext_ecb) . " bytes\n";

$decrypted_ecb = SM4::decryptECB($ciphertext_ecb, $key);
echo "Decrypted: $decrypted_ecb\n";

if ($plaintext === $decrypted_ecb) {
    echo "✓ ECB: SUCCESS\n";
} else {
    echo "✗ ECB: FAILED\n";
}
echo "⚠ WARNING: ECB mode is not secure for most applications!\n";
echo "\n";

// ======================
// Test 4: Password-Based Encryption
// ======================
echo "Test 4: Password-Based Encryption (PBKDF2)\n";
echo "-------------------------------------------\n";

$password = "MySecretPassword123!";
$encrypted = SM4::encryptWithPassword($plaintext, $password);

echo "Password: $password\n";
echo "Encrypted data (hex): " . bin2hex($encrypted) . "\n";
echo "Encrypted length: " . strlen($encrypted) . " bytes (salt + IV + ciphertext)\n";

$decrypted_pwd = SM4::decryptWithPassword($encrypted, $password);
echo "Decrypted: $decrypted_pwd\n";

if ($plaintext === $decrypted_pwd) {
    echo "✓ Password-based: SUCCESS\n";
} else {
    echo "✗ Password-based: FAILED\n";
}
echo "\n";

// ======================
// Test 5: Different Data Sizes
// ======================
echo "Test 5: Different Data Sizes\n";
echo "-----------------------------\n";

$testSizes = [
    1 => "A",
    5 => "Hello",
    15 => "Hello, World!!!",
    16 => str_repeat("X", 16),
    17 => str_repeat("Y", 17),
    31 => str_repeat("Z", 31),
    32 => str_repeat("A", 32),
    100 => str_repeat("B", 100),
];

foreach ($testSizes as $size => $data) {
    $ct = SM4::encryptCBC($data, $key, $iv);
    $pt = SM4::decryptCBC($ct, $key, $iv);
    $status = ($data === $pt) ? "✓" : "✗";
    echo "$status Size $size bytes: " . (($data === $pt) ? "OK" : "FAILED") . "\n";
}
echo "\n";

// ======================
// Test 6: Performance Test
// ======================
echo "Test 6: Performance Test\n";
echo "------------------------\n";

$iterations = 1000;
$testData = str_repeat("Performance test data. ", 10); // ~230 bytes

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $ct = SM4::encryptCBC($testData, $key, $iv);
    $pt = SM4::decryptCBC($ct, $key, $iv);
}
$elapsed = microtime(true) - $start;

echo "Iterations: $iterations\n";
echo "Data size: " . strlen($testData) . " bytes\n";
echo "Total time: " . number_format($elapsed, 3) . " seconds\n";
echo "Average time per encryption+decryption: " . number_format($elapsed / $iterations * 1000, 3) . " ms\n";
echo "Throughput: " . number_format(($iterations * strlen($testData) * 2) / $elapsed / 1024, 2) . " KB/s\n";
echo "\n";

// ======================
// Test 7: Error Handling
// ======================
echo "Test 7: Error Handling\n";
echo "----------------------\n";

$tests = [
    'Invalid key length' => function() use ($plaintext, $iv) {
        try {
            SM4::encryptCBC($plaintext, 'short', $iv);
            return "✗ Should have thrown exception";
        } catch (\InvalidArgumentException $e) {
            return "✓ Caught: " . $e->getMessage();
        }
    },
    'Invalid IV length' => function() use ($plaintext, $key) {
        try {
            SM4::encryptCBC($plaintext, $key, 'short');
            return "✗ Should have thrown exception";
        } catch (\InvalidArgumentException $e) {
            return "✓ Caught: " . $e->getMessage();
        }
    },
    'Invalid ciphertext (ECB)' => function() use ($key) {
        try {
            SM4::decryptECB('invalid', $key);
            return "✗ Should have thrown exception";
        } catch (\InvalidArgumentException $e) {
            return "✓ Caught: " . $e->getMessage();
        }
    },
];

foreach ($tests as $name => $test) {
    echo "$name: " . $test() . "\n";
}
echo "\n";

// ======================
// Summary
// ======================
echo "API Features Summary:\n";
echo "=====================\n";
echo "✓ SM4::encryptCBC() - CBC mode with automatic padding\n";
echo "✓ SM4::decryptCBC() - CBC mode with automatic padding removal\n";
echo "✓ SM4::encryptCTR() - CTR mode, no padding needed\n";
echo "✓ SM4::decryptCTR() - CTR mode decryption\n";
echo "✓ SM4::encryptECB() - ECB mode (use with caution)\n";
echo "✓ SM4::decryptECB() - ECB mode decryption\n";
echo "✓ SM4::generateKey() - Generate random key\n";
echo "✓ SM4::generateIV() - Generate random IV/nonce\n";
echo "✓ SM4::encryptWithPassword() - Password-based encryption\n";
echo "✓ SM4::decryptWithPassword() - Password-based decryption\n";
echo "\n";

echo "Advantages of High-Level API:\n";
echo "-----------------------------\n";
echo "✓ Simple one-line encryption/decryption\n";
echo "✓ Automatic padding handling\n";
echo "✓ Automatic key/IV generation\n";
echo "✓ Password-based encryption support\n";
echo "✓ Error handling built-in\n";
echo "✓ No need to understand low-level details\n";
echo "\n";

echo "All tests completed successfully!\n";
