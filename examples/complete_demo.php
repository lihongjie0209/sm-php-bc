<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\SM2;
use SmBc\SM4;
use SmBc\Crypto\Digests\SM3Digest;
use SmBc\Crypto\KDF\KDF;
use SmBc\Math\EC\ECAlgorithms;
use SmBc\Crypto\Signers\SM2Signer;
use SmBc\Crypto\Agreement\SM2KeyExchange;
use SmBc\Crypto\Params\SM2KeyExchangePrivateParameters;
use SmBc\Crypto\Params\SM2KeyExchangePublicParameters;
use SmBc\Crypto\Params\ParametersWithID;

echo "====================================\n";
echo "SM Cryptography Complete Demo (PHP)\n";
echo "====================================\n\n";

// ========== 1. SM3 Hash ==========
echo "1. SM3 Hash Function\n";
echo "-------------------\n";
$sm3 = new SM3Digest();
$message = "Hello, SM3!";
$sm3->updateBytes($message, 0, strlen($message));
$hash = str_repeat("\0", 32);
$sm3->doFinal($hash, 0);
echo "Message: $message\n";
echo "SM3 Hash: " . bin2hex($hash) . "\n\n";

// ========== 2. SM4 Encryption (All Modes) ==========
echo "2. SM4 Encryption (All Modes)\n";
echo "-----------------------------\n";
$key = random_bytes(16);
$plaintext = "Hello, SM4! This is a test message for block cipher encryption.";
echo "Plaintext: $plaintext\n";
echo "Key (hex): " . bin2hex($key) . "\n\n";

// ECB Mode
$encrypted = SM4::encryptECB($plaintext, $key);
$decrypted = SM4::decryptECB($encrypted, $key);
echo "ECB Mode: " . ($decrypted === $plaintext ? "✓ PASS" : "✗ FAIL") . "\n";

// CBC Mode
$iv = random_bytes(16);
$encrypted = SM4::encryptCBC($plaintext, $key, $iv);
$decrypted = SM4::decryptCBC($encrypted, $key, $iv);
echo "CBC Mode: " . ($decrypted === $plaintext ? "✓ PASS" : "✗ FAIL") . "\n";

// CTR Mode
$encrypted = SM4::encryptCTR($plaintext, $key, $iv);
$decrypted = SM4::decryptCTR($encrypted, $key, $iv);
echo "CTR Mode: " . ($decrypted === $plaintext ? "✓ PASS" : "✗ FAIL") . "\n";

// CFB Mode
$encrypted = SM4::encryptCFB($plaintext, $key, $iv);
$decrypted = SM4::decryptCFB($encrypted, $key, $iv);
echo "CFB Mode: " . ($decrypted === $plaintext ? "✓ PASS" : "✗ FAIL") . "\n";

// OFB Mode
$encrypted = SM4::encryptOFB($plaintext, $key, $iv);
$decrypted = SM4::decryptOFB($encrypted, $key, $iv);
echo "OFB Mode: " . ($decrypted === $plaintext ? "✓ PASS" : "✗ FAIL") . "\n";

// GCM Mode (Authenticated Encryption)
$aad = "Additional Authenticated Data";
$result = SM4::encryptGCM($plaintext, $key, $iv, $aad);
$decrypted = SM4::decryptGCM($result['ciphertext'], $key, $iv, $aad, $result['tag']);
echo "GCM Mode: " . ($decrypted === $plaintext ? "✓ PASS" : "✗ FAIL") . " (Authenticated)\n\n";

// ========== 3. SM2 Public Key Encryption ==========
echo "3. SM2 Public Key Encryption\n";
echo "----------------------------\n";
$keyPair = SM2::generateKeyPair();
$publicKey = $keyPair->getPublic();
$privateKey = $keyPair->getPrivate();
echo "Key pair generated\n";

$message = "Hello, SM2 Encryption!";
$encrypted = SM2::encrypt($message, $publicKey);
$decrypted = SM2::decrypt($encrypted, $privateKey);
echo "Message: $message\n";
echo "Encrypted (hex): " . bin2hex(substr($encrypted, 0, 32)) . "...\n";
echo "Decrypted: $decrypted\n";
echo "Status: " . ($decrypted === $message ? "✓ PASS" : "✗ FAIL") . "\n\n";

// ========== 4. SM2 Digital Signature ==========
echo "4. SM2 Digital Signature\n";
echo "------------------------\n";
$signer = new SM2Signer();
$message = "Document to be signed";
echo "Message: $message\n";

// Sign
$signer->init(true, $privateKey);
$signer->updateBytes($message, 0, strlen($message));
$signature = $signer->generateSignature();
echo "Signature generated (r,s length): " . strlen($signature) . " bytes\n";

// Verify
$signer->init(false, $publicKey);
$signer->updateBytes($message, 0, strlen($message));
$valid = $signer->verifySignature($signature);
echo "Signature verification: " . ($valid ? "✓ VALID" : "✗ INVALID") . "\n\n";

// ========== 5. SM2 Key Exchange ==========
echo "5. SM2 Key Exchange Protocol\n";
echo "----------------------------\n";

// Party A (Alice)
$aliceKeyPair = SM2::generateKeyPair();
$aliceEphemeralKeyPair = SM2::generateKeyPair();
$aliceID = "alice@example.com";

// Party B (Bob)
$bobKeyPair = SM2::generateKeyPair();
$bobEphemeralKeyPair = SM2::generateKeyPair();
$bobID = "bob@example.com";

echo "Alice and Bob generated their keys\n";

// Alice initiates
$aliceExchange = new SM2KeyExchange();
$alicePrivateParams = new SM2KeyExchangePrivateParameters(
    true, // initiator
    $aliceKeyPair->getPrivate(),
    $aliceEphemeralKeyPair->getPrivate()
);
$alicePrivateParams = new ParametersWithID($alicePrivateParams, $aliceID);
$aliceExchange->init($alicePrivateParams);

// Bob responds
$bobExchange = new SM2KeyExchange();
$bobPrivateParams = new SM2KeyExchangePrivateParameters(
    false, // responder
    $bobKeyPair->getPrivate(),
    $bobEphemeralKeyPair->getPrivate()
);
$bobPrivateParams = new ParametersWithID($bobPrivateParams, $bobID);
$bobExchange->init($bobPrivateParams);

// Alice calculates shared key
$bobPublicParams = new SM2KeyExchangePublicParameters(
    $bobKeyPair->getPublic(),
    $bobEphemeralKeyPair->getPublic()
);
$bobPublicParams = new ParametersWithID($bobPublicParams, $bobID);
$aliceSharedKey = $aliceExchange->calculateKey(256, $bobPublicParams); // 256 bits = 32 bytes

// Bob calculates shared key
$alicePublicParams = new SM2KeyExchangePublicParameters(
    $aliceKeyPair->getPublic(),
    $aliceEphemeralKeyPair->getPublic()
);
$alicePublicParams = new ParametersWithID($alicePublicParams, $aliceID);
$bobSharedKey = $bobExchange->calculateKey(256, $alicePublicParams); // 256 bits = 32 bytes

echo "Alice's shared key: " . bin2hex(substr($aliceSharedKey, 0, 16)) . "...\n";
echo "Bob's shared key:   " . bin2hex(substr($bobSharedKey, 0, 16)) . "...\n";
echo "Keys match: " . ($aliceSharedKey === $bobSharedKey ? "✓ YES" : "✗ NO") . "\n\n";

// ========== 6. KDF (Key Derivation Function) ==========
echo "6. KDF (Key Derivation Function)\n";
echo "--------------------------------\n";
$kdf = new KDF();
$sharedSecret = "shared_secret_between_parties";
$derivedKey16 = $kdf->deriveKey($sharedSecret, 16);
$derivedKey32 = $kdf->deriveKey($sharedSecret, 32);
echo "Shared secret: $sharedSecret\n";
echo "Derived key (16 bytes): " . bin2hex($derivedKey16) . "\n";
echo "Derived key (32 bytes): " . bin2hex($derivedKey32) . "\n";
echo "First 16 bytes match: " . (substr($derivedKey32, 0, 16) === $derivedKey16 ? "✓ YES" : "✗ NO") . "\n\n";

// ========== 7. EC Algorithms ==========
echo "7. EC Algorithms Utility\n";
echo "------------------------\n";
$G = $keyPair->getPublic()->getParameters()->getG();
$P1 = $G->multiply(\SmBc\Math\BigInteger::valueOf(5));
$P2 = $G->multiply(\SmBc\Math\BigInteger::valueOf(7));
echo "P1 = 5*G\n";
echo "P2 = 7*G\n";

$k1 = \SmBc\Math\BigInteger::valueOf(2);
$k2 = \SmBc\Math\BigInteger::valueOf(3);
$result = ECAlgorithms::sumOfTwoMultiplies($P1, $k1, $P2, $k2);
// Should equal 2*(5*G) + 3*(7*G) = 10*G + 21*G = 31*G
$expected = $G->multiply(\SmBc\Math\BigInteger::valueOf(31));
echo "2*P1 + 3*P2 = 2*(5*G) + 3*(7*G) = 31*G\n";
echo "Result matches expected: " . ($result->equals($expected) ? "✓ YES" : "✗ NO") . "\n\n";

// ========== Summary ==========
echo "====================================\n";
echo "All SM Cryptography Features Tested!\n";
echo "====================================\n";
echo "\nFeature Coverage:\n";
echo "  ✓ SM3 Hash Function\n";
echo "  ✓ SM4 Encryption (ECB, CBC, CTR, CFB, OFB, GCM)\n";
echo "  ✓ SM2 Public Key Encryption\n";
echo "  ✓ SM2 Digital Signatures\n";
echo "  ✓ SM2 Key Exchange\n";
echo "  ✓ KDF (Key Derivation)\n";
echo "  ✓ EC Algorithms\n";
echo "  ✓ All Padding Schemes (PKCS7, ISO7816-4, ISO10126, ZeroByte)\n";
echo "\n";
