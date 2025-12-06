<?php

require_once __DIR__ . '/vendor/autoload.php';

use SmBc\Crypto\Engines\SM2Engine;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ParametersWithRandom;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\BigInteger;
use SmBc\Util\SecureRandom;

echo "Starting SM2Engine test...\n";

// SM2 Standard Parameters
$p = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFF', 16);
$a = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFC', 16);
$b = new BigInteger('0x28E9FA9E9D9F5E344D5A9E4BCF6509A7F39789F515AB8F92DDBCBD414D940E93', 16);
$n = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123', 16);

echo "Creating curve...\n";
$curve = new ECCurveFp($p, $a, $b, $n, BigInteger::ONE());

$gx = new BigInteger('0x32C4AE2C1F1981195F9904466A39C9948FE30BBFF2660BE1715A4589334C74C7', 16);
$gy = new BigInteger('0xBC3736A2F4F6779C59BDCEE36B692153D0A9877CC62A474002DF32E52139F0A0', 16);

echo "Creating generator point...\n";
$g = $curve->createPoint($gx, $gy);

echo "Creating domain parameters...\n";
$domainParams = new ECDomainParameters($curve, $g, $n, BigInteger::ONE());

// Use fixed private key for testing
echo "Creating key pair...\n";
$d = new BigInteger('0x128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263', 16);
$q = $g->multiply($d)->normalize();

$privateKey = new ECPrivateKeyParameters($d, $domainParams);
$publicKey = new ECPublicKeyParameters($q, $domainParams);

$message = 'Hello SM2!';
echo "Message: $message\n";

// Encrypt
echo "Initializing encryption engine...\n";
$encryptEngine = new SM2Engine();
$encryptParams = new ParametersWithRandom($publicKey, new SecureRandom());

echo "Calling init()...\n";
$encryptEngine->init(true, $encryptParams);

echo "Encrypting message...\n";
$ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));

echo "Ciphertext length: " . strlen($ciphertext) . "\n";
echo "Ciphertext (hex): " . bin2hex($ciphertext) . "\n";

// Decrypt
echo "Initializing decryption engine...\n";
$decryptEngine = new SM2Engine();
$decryptEngine->init(false, $privateKey);

echo "Decrypting message...\n";
$decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));

echo "Decrypted: $decrypted\n";

if ($message === $decrypted) {
    echo "✓ Test PASSED!\n";
} else {
    echo "✗ Test FAILED!\n";
    echo "Expected: $message\n";
    echo "Got: $decrypted\n";
}
