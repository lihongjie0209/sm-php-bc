<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\CTRBlockCipher;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

echo "SM4 CTR Mode Encryption Example\n";
echo "================================\n\n";

// Setup
$key = random_bytes(16); // 128-bit key
$iv = random_bytes(16);  // 128-bit nonce/counter
$plaintext = "Hello, SM4 CTR Mode! This is a test message. CTR mode doesn't need padding!";

echo "Key (hex): " . bin2hex($key) . "\n";
echo "IV (hex):  " . bin2hex($iv) . "\n";
echo "Plaintext: $plaintext\n";
echo "Plaintext length: " . strlen($plaintext) . " bytes\n\n";

// Create cipher
$engine = new SM4Engine();
$ctrCipher = new CTRBlockCipher($engine);

// Encryption
echo "Encrypting...\n";
$keyParam = new KeyParameter($key);
$params = new ParametersWithIV($keyParam, $iv);
$ctrCipher->init(true, $params);

// CTR mode doesn't need padding - can handle any length
$ciphertext = str_repeat("\x00", strlen($plaintext));
$ctrCipher->processBytes($plaintext, 0, strlen($plaintext), $ciphertext, 0);

echo "Ciphertext (hex): " . bin2hex($ciphertext) . "\n";
echo "Ciphertext length: " . strlen($ciphertext) . " bytes (same as plaintext)\n\n";

// Decryption
echo "Decrypting...\n";
$ctrCipher->reset();

// Decrypt
$decrypted = str_repeat("\x00", strlen($ciphertext));
$ctrCipher->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);

echo "Decrypted: $decrypted\n";
echo "Decrypted length: " . strlen($decrypted) . " bytes\n\n";

// Verify
if ($plaintext === $decrypted) {
    echo "✓ SUCCESS: Decrypted text matches original plaintext!\n";
} else {
    echo "✗ FAILURE: Decrypted text does not match!\n";
}

echo "\n";

// Test CTR's streaming capability
echo "Testing CTR Streaming (various lengths):\n";
echo "----------------------------------------\n";

$testMessages = [
    "A",                                    // 1 byte
    "Hello!",                               // 6 bytes
    "Hello World!",                         // 12 bytes
    str_repeat("X", 16),                    // Exactly 1 block
    str_repeat("Test", 5),                  // 20 bytes
    "This is a 25-byte text!",              // 25 bytes
    str_repeat("Streaming", 10),            // 90 bytes
];

foreach ($testMessages as $i => $msg) {
    $ctrCipher->reset();
    $ctrCipher->init(true, $params);
    
    // Encrypt
    $ct = str_repeat("\x00", strlen($msg));
    $ctrCipher->processBytes($msg, 0, strlen($msg), $ct, 0);
    
    // Decrypt
    $ctrCipher->reset();
    $dt = str_repeat("\x00", strlen($ct));
    $ctrCipher->processBytes($ct, 0, strlen($ct), $dt, 0);
    
    $status = ($msg === $dt) ? "✓" : "✗";
    echo "Test " . ($i + 1) . ": $status " . strlen($msg) . " bytes (no padding needed)\n";
}

echo "\n";

// Test byte-by-byte processing
echo "Testing Byte-by-Byte Processing:\n";
echo "---------------------------------\n";

$message = "Byte-by-byte test!";
$ctrCipher->reset();
$ctrCipher->init(true, $params);

// Encrypt byte by byte
$encrypted = '';
for ($i = 0; $i < strlen($message); $i++) {
    $encrypted .= chr($ctrCipher->processByte(ord($message[$i])));
}

// Decrypt byte by byte
$ctrCipher->reset();
$decryptedBytes = '';
for ($i = 0; $i < strlen($encrypted); $i++) {
    $decryptedBytes .= chr($ctrCipher->processByte(ord($encrypted[$i])));
}

if ($message === $decryptedBytes) {
    echo "✓ Byte-by-byte: $message\n";
} else {
    echo "✗ Byte-by-byte processing failed!\n";
}

echo "\n";

// Demonstrate CTR advantages
echo "CTR Mode Advantages:\n";
echo "--------------------\n";
echo "✓ No padding required (handles any length)\n";
echo "✓ Encryption and decryption use same operation\n";
echo "✓ Can be parallelized (in theory)\n";
echo "✓ Random access to any part of ciphertext\n";
echo "✓ Converts block cipher to stream cipher\n";

echo "\nAll tests completed!\n";
