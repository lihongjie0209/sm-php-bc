<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\CBCBlockCipher;
use SmBc\Crypto\Paddings\PKCS7Padding;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

echo "SM4 CBC Mode Encryption Example\n";
echo "================================\n\n";

// Setup
$key = random_bytes(16); // 128-bit key
$iv = random_bytes(16);  // 128-bit IV
$plaintext = "Hello, SM4 CBC Mode! This is a test message for encryption.";

echo "Key (hex): " . bin2hex($key) . "\n";
echo "IV (hex):  " . bin2hex($iv) . "\n";
echo "Plaintext: $plaintext\n";
echo "Plaintext length: " . strlen($plaintext) . " bytes\n\n";

// Create cipher
$engine = new SM4Engine();
$cbcCipher = new CBCBlockCipher($engine);
$padding = new PKCS7Padding();

// Encryption
echo "Encrypting...\n";
$keyParam = new KeyParameter($key);
$params = new ParametersWithIV($keyParam, $iv);
$cbcCipher->init(true, $params);

// Pad the plaintext
$blockSize = $cbcCipher->getBlockSize();
$remainder = strlen($plaintext) % $blockSize;
if ($remainder === 0) {
    // Need a full block of padding
    $paddedLength = strlen($plaintext) + $blockSize;
} else {
    $paddedLength = $blockSize * (int)ceil(strlen($plaintext) / $blockSize);
}
$padded = str_pad($plaintext, $paddedLength, "\x00");
$padding->addPadding($padded, strlen($plaintext));

echo "Padded length: " . strlen($padded) . " bytes\n";

// Encrypt block by block
$ciphertext = str_repeat("\x00", strlen($padded));
for ($i = 0; $i < strlen($padded); $i += $blockSize) {
    $cbcCipher->processBlock($padded, $i, $ciphertext, $i);
}

echo "Ciphertext (hex): " . bin2hex($ciphertext) . "\n";
echo "Ciphertext length: " . strlen($ciphertext) . " bytes\n\n";

// Decryption
echo "Decrypting...\n";
$cbcCipher->init(false, $params);

// Decrypt block by block
$decrypted = str_repeat("\x00", strlen($ciphertext));
for ($i = 0; $i < strlen($ciphertext); $i += $blockSize) {
    $cbcCipher->processBlock($ciphertext, $i, $decrypted, $i);
}

// Remove padding
$lastBlock = substr($decrypted, -$blockSize);
$padCount = $padding->padCount($lastBlock);
$decryptedPlaintext = substr($decrypted, 0, strlen($decrypted) - $padCount);

echo "Decrypted: $decryptedPlaintext\n";
echo "Decrypted length: " . strlen($decryptedPlaintext) . " bytes\n\n";

// Verify
if ($plaintext === $decryptedPlaintext) {
    echo "✓ SUCCESS: Decrypted text matches original plaintext!\n";
} else {
    echo "✗ FAILURE: Decrypted text does not match!\n";
}

echo "\n";

// Test with different block counts
echo "Testing different message lengths:\n";
echo "-----------------------------------\n";

$testMessages = [
    "A",                           // 1 byte
    "Hello World!",                // 12 bytes
    str_repeat("X", 16),           // Exactly 1 block
    str_repeat("Test ", 10),       // 50 bytes
    str_repeat("LongMessage", 20), // 220 bytes
];

foreach ($testMessages as $i => $msg) {
    $remainder = strlen($msg) % $blockSize;
    if ($remainder === 0) {
        $paddedLen = strlen($msg) + $blockSize;
    } else {
        $paddedLen = $blockSize * (int)ceil(strlen($msg) / $blockSize);
    }
    $padded = str_pad($msg, $paddedLen, "\x00");
    $padding->addPadding($padded, strlen($msg));
    
    // Encrypt
    $cbcCipher->reset();
    $cbcCipher->init(true, $params);
    $ct = str_repeat("\x00", strlen($padded));
    for ($j = 0; $j < strlen($padded); $j += $blockSize) {
        $cbcCipher->processBlock($padded, $j, $ct, $j);
    }
    
    // Decrypt
    $cbcCipher->init(false, $params);
    $dt = str_repeat("\x00", strlen($ct));
    for ($j = 0; $j < strlen($ct); $j += $blockSize) {
        $cbcCipher->processBlock($ct, $j, $dt, $j);
    }
    
    $lastBlock = substr($dt, -$blockSize);
    $padCount = $padding->padCount($lastBlock);
    $result = substr($dt, 0, strlen($dt) - $padCount);
    
    $status = ($msg === $result) ? "✓" : "✗";
    echo "Test " . ($i + 1) . ": $status " . strlen($msg) . " bytes -> " . strlen($ct) . " bytes encrypted\n";
}

echo "\nAll tests completed!\n";
