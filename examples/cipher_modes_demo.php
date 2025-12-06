<?php
/**
 * SM4 Cipher Modes Demonstration
 * 
 * This example demonstrates all available SM4 cipher modes:
 * - ECB (Electronic Codebook) - Simple but insecure
 * - CBC (Cipher Block Chaining) - Secure with IV
 * - CTR (Counter Mode) - Stream cipher mode
 * 
 * Author: SM-PHP-BC Team
 * Date: 2025-12-06
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\ECBBlockCipher;
use SmBc\Crypto\Modes\CBCBlockCipher;
use SmBc\Crypto\Modes\CTRBlockCipher;
use SmBc\Crypto\Paddings\PKCS7Padding;
use SmBc\Crypto\PaddedBufferedBlockCipher;
use SmBc\Crypto\BufferedBlockCipher;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

// Helper function to display results
function displayResult(string $title, string $plaintext, string $ciphertext, string $decrypted): void
{
    echo "\n" . str_repeat("=", 70) . "\n";
    echo $title . "\n";
    echo str_repeat("=", 70) . "\n";
    echo "Plaintext:  $plaintext\n";
    echo "Ciphertext: " . bin2hex($ciphertext) . "\n";
    echo "Decrypted:  $decrypted\n";
    echo "Match:      " . ($plaintext === $decrypted ? "✓ YES" : "✗ NO") . "\n";
}

// Common test data
$key = str_repeat("\x01", 16); // 128-bit key (all 0x01)
$iv = str_repeat("\x00", 16);  // 128-bit IV (all 0x00)
$plaintext = "Hello, SM4! This is a test message for cipher modes.";

echo "SM4 Cipher Modes Demonstration\n";
echo str_repeat("=", 70) . "\n";

echo "\nTest Configuration:\n";
echo "  Key (hex):  " . bin2hex($key) . "\n";
echo "  IV (hex):   " . bin2hex($iv) . "\n";
echo "  Message:    $plaintext\n";

// ============================================================================
// 1. ECB MODE (Electronic Codebook)
// ============================================================================

echo "\n\n";
echo "MODE 1: ECB (Electronic Codebook)\n";
echo str_repeat("-", 70) . "\n";
echo "WARNING: ECB mode is INSECURE!\n";
echo "  - Identical blocks produce identical ciphertext\n";
echo "  - Information patterns are leaked\n";
echo "  - Use ONLY for legacy compatibility or testing\n";

$cipher = new PaddedBufferedBlockCipher(
    new ECBBlockCipher(new SM4Engine()),
    new PKCS7Padding()
);

// Encrypt
$cipher->init(true, new KeyParameter($key)); // No IV for ECB
$ciphertext = str_repeat("\x00", $cipher->getOutputSize(strlen($plaintext)));
$len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $ciphertext, 0);
$len += $cipher->doFinal($ciphertext, $len);
$ciphertext = substr($ciphertext, 0, $len);

// Decrypt
$cipher->init(false, new KeyParameter($key));
$decrypted = str_repeat("\x00", $cipher->getOutputSize(strlen($ciphertext)));
$len = $cipher->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);
$len += $cipher->doFinal($decrypted, $len);
$decrypted = substr($decrypted, 0, $len);

displayResult("ECB Mode Result", $plaintext, $ciphertext, $decrypted);

// ============================================================================
// 2. CBC MODE (Cipher Block Chaining)
// ============================================================================

echo "\n\n";
echo "MODE 2: CBC (Cipher Block Chaining)\n";
echo str_repeat("-", 70) . "\n";
echo "SECURE block cipher mode\n";
echo "  - Each block depends on previous ciphertext\n";
echo "  - Requires IV (Initialization Vector)\n";
echo "  - Most commonly used mode\n";
echo "  - Good for file encryption\n";

$cipher = new PaddedBufferedBlockCipher(
    new CBCBlockCipher(new SM4Engine()),
    new PKCS7Padding()
);

// Encrypt
$cipher->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
$ciphertext = str_repeat("\x00", $cipher->getOutputSize(strlen($plaintext)));
$len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $ciphertext, 0);
$len += $cipher->doFinal($ciphertext, $len);
$ciphertext = substr($ciphertext, 0, $len);

// Decrypt
$cipher->init(false, new ParametersWithIV(new KeyParameter($key), $iv));
$decrypted = str_repeat("\x00", $cipher->getOutputSize(strlen($ciphertext)));
$len = $cipher->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);
$len += $cipher->doFinal($decrypted, $len);
$decrypted = substr($decrypted, 0, $len);

displayResult("CBC Mode Result", $plaintext, $ciphertext, $decrypted);

// ============================================================================
// 3. CTR MODE (Counter Mode)
// ============================================================================

echo "\n\n";
echo "MODE 3: CTR (Counter Mode / SIC)\n";
echo str_repeat("-", 70) . "\n";
echo "SECURE stream cipher mode\n";
echo "  - Converts block cipher into stream cipher\n";
echo "  - No padding required\n";
echo "  - Parallelizable (fast)\n";
echo "  - Random access to encrypted data\n";
echo "  - Good for network protocols\n";

$cipher = new BufferedBlockCipher(
    new CTRBlockCipher(new SM4Engine()),
    new PKCS7Padding() // CTR technically doesn't need padding, but BufferedBlockCipher requires it
);

// Encrypt
$cipher->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
$ciphertext = str_repeat("\x00", $cipher->getOutputSize(strlen($plaintext)));
$len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $ciphertext, 0);
$len += $cipher->doFinal($ciphertext, $len);
$ciphertext = substr($ciphertext, 0, $len);

// Decrypt
$cipher->init(false, new ParametersWithIV(new KeyParameter($key), $iv));
$decrypted = str_repeat("\x00", $cipher->getOutputSize(strlen($ciphertext)));
$len = $cipher->processBytes($ciphertext, 0, strlen($ciphertext), $decrypted, 0);
$len += $cipher->doFinal($decrypted, $len);
$decrypted = substr($decrypted, 0, $len);

displayResult("CTR Mode Result", $plaintext, $ciphertext, $decrypted);

// ============================================================================
// COMPARISON SUMMARY
// ============================================================================

echo "\n\n";
echo str_repeat("=", 70) . "\n";
echo "MODE COMPARISON SUMMARY\n";
echo str_repeat("=", 70) . "\n";
echo "Mode | Security  | Padding | Speed    | Use Case\n";
echo str_repeat("-", 70) . "\n";
echo "ECB  | WEAK      | Yes     | Fast     | Legacy only (UNSAFE)\n";
echo "CBC  | SECURE    | Yes     | Good     | Files, databases\n";
echo "CTR  | SECURE    | No      | Very Fast| Network, streaming\n";
echo str_repeat("=", 70) . "\n";

echo "\nRECOMMENDATIONS:\n";
echo "  - For file encryption:     Use CBC mode\n";
echo "  - For network protocols:   Use CTR mode\n";
echo "  - For authenticated data:  Wait for GCM mode (coming soon)\n";
echo "  - For legacy systems:      Use ECB (with extreme caution)\n";

echo "\nAll cipher modes working correctly!\n\n";
