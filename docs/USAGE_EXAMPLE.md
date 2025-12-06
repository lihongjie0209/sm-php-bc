# SM-PHP-BC Usage Examples

## SM2 Encryption/Decryption

### Basic Usage

```php
<?php

require_once 'vendor/autoload.php';

use SmBc\Crypto\Engines\SM2Engine;
use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Crypto\Params\ParametersWithRandom;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\BigInteger;
use SmBc\Util\SecureRandom;

// SM2 Standard Curve Parameters
$p = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFF', 16);
$a = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFC', 16);
$b = new BigInteger('0x28E9FA9E9D9F5E344D5A9E4BCF6509A7F39789F515AB8F92DDBCBD414D940E93', 16);
$n = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123', 16);

$curve = new ECCurveFp($p, $a, $b, $n, BigInteger::ONE());

$gx = new BigInteger('0x32C4AE2C1F1981195F9904466A39C9948FE30BBFF2660BE1715A4589334C74C7', 16);
$gy = new BigInteger('0xBC3736A2F4F6779C59BDCEE36B692153D0A9877CC62A474002DF32E52139F0A0', 16);
$g = $curve->createPoint($gx, $gy);

$domainParams = new ECDomainParameters($curve, $g, $n, BigInteger::ONE());

// Generate or load key pair
$d = new BigInteger('0x128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263', 16);
$q = $g->multiply($d)->normalize();

$privateKey = new ECPrivateKeyParameters($d, $domainParams);
$publicKey = new ECPublicKeyParameters($q, $domainParams);

// Message to encrypt
$message = 'Hello, SM2 Encryption!';

// === ENCRYPTION ===
$encryptEngine = new SM2Engine();  // Default mode: C1C2C3
$encryptParams = new ParametersWithRandom($publicKey, new SecureRandom());
$encryptEngine->init(true, $encryptParams);

$ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));

echo "Encrypted (hex): " . bin2hex($ciphertext) . "\n";
echo "Ciphertext length: " . strlen($ciphertext) . " bytes\n";

// === DECRYPTION ===
$decryptEngine = new SM2Engine();
$decryptEngine->init(false, $privateKey);

$decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));

echo "Decrypted: $decrypted\n";

// Verify
if ($message === $decrypted) {
    echo "✓ Encryption/Decryption successful!\n";
}
```

### Using C1C3C2 Mode

```php
<?php

// ... (same setup as above)

// Specify C1C3C2 mode (C1||C3||C2 format)
$encryptEngine = new SM2Engine(null, SM2Engine::MODE_C1C3C2);
$encryptParams = new ParametersWithRandom($publicKey, new SecureRandom());
$encryptEngine->init(true, $encryptParams);

$ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));

// Decrypt with same mode
$decryptEngine = new SM2Engine(null, SM2Engine::MODE_C1C3C2);
$decryptEngine->init(false, $privateKey);

$decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
```

### Custom SM3 Digest

```php
<?php

use SmBc\Crypto\Digests\SM3Digest;

// Create custom digest instance
$customDigest = new SM3Digest();

// Use custom digest in engine
$encryptEngine = new SM2Engine($customDigest, SM2Engine::MODE_C1C2C3);
// ... rest of encryption code
```

### Error Handling

```php
<?php

try {
    $encryptEngine = new SM2Engine();
    $encryptParams = new ParametersWithRandom($publicKey, new SecureRandom());
    $encryptEngine->init(true, $encryptParams);
    
    $ciphertext = $encryptEngine->processBlock($message, 0, strlen($message));
    
    // Try to decrypt
    $decryptEngine = new SM2Engine();
    $decryptEngine->init(false, $privateKey);
    
    $decrypted = $decryptEngine->processBlock($ciphertext, 0, strlen($ciphertext));
    
} catch (InvalidArgumentException $e) {
    echo "Invalid parameter: " . $e->getMessage() . "\n";
} catch (RuntimeException $e) {
    echo "Encryption/Decryption error: " . $e->getMessage() . "\n";
    // Could be: corrupted ciphertext, invalid key, etc.
}
```

### Batch Encryption (Reusing Engine)

```php
<?php

$encryptEngine = new SM2Engine();
$encryptParams = new ParametersWithRandom($publicKey, new SecureRandom());
$encryptEngine->init(true, $encryptParams);

$messages = [
    'Message 1',
    'Message 2',
    'Message 3'
];

$ciphertexts = [];

foreach ($messages as $msg) {
    $ct = $encryptEngine->processBlock($msg, 0, strlen($msg));
    $ciphertexts[] = $ct;
    echo "Encrypted: " . bin2hex($ct) . "\n";
}

// Decrypt all
$decryptEngine = new SM2Engine();
$decryptEngine->init(false, $privateKey);

foreach ($ciphertexts as $i => $ct) {
    $decrypted = $decryptEngine->processBlock($ct, 0, strlen($ct));
    echo "Decrypted[$i]: $decrypted\n";
}
```

### Getting Output Size

```php
<?php

$encryptEngine = new SM2Engine();
$encryptParams = new ParametersWithRandom($publicKey, new SecureRandom());
$encryptEngine->init(true, $encryptParams);

$messageLen = strlen($message);
$outputSize = $encryptEngine->getOutputSize($messageLen);

echo "For message length $messageLen bytes:\n";
echo "Ciphertext will be $outputSize bytes\n";
// Output size = C1(65 bytes) + message length + C3(32 bytes) = message_length + 97
```

## SM3 Hashing

```php
<?php

use SmBc\Crypto\Digests\SM3Digest;

$digest = new SM3Digest();
$msg = "abc";
$digest->updateBytes($msg, 0, strlen($msg));

$output = str_repeat("\x00", 32);
$digest->doFinal($output, 0);

echo bin2hex($output); 
// Output: 66c7f0f462eeedd9d1f2d46bdc10e4e24167c4875cf2f7a2297da02b8f4ba8e0
```

## Notes

### Cipher Modes
- **C1C2C3 (default):** Ciphertext format is `C1 || C2 || C3`
- **C1C3C2:** Ciphertext format is `C1 || C3 || C2`

Where:
- `C1`: Ephemeral public key (65 bytes, uncompressed point: 0x04 || x || y)
- `C2`: Encrypted message (same length as plaintext)
- `C3`: MAC/Hash for integrity (32 bytes, SM3 output)

### Security Notes
1. Always use `SecureRandom` for encryption
2. Never reuse the same random `k` value
3. Protect private keys appropriately
4. Validate public keys before use
5. Use constant-time comparison for sensitive data

### Performance
- Encryption involves 2 EC point multiplications
- Decryption involves 1 EC point multiplication
- KDF uses SM3 iteratively (one hash per 32 bytes of message)
- Typical performance: ~1-10ms per operation on modern hardware

### Compatibility
- Output format is compatible with other SM2 implementations
- Test vectors from GM/T 0003-2012 standard
- Cross-compatible with sm-js-bc (TypeScript) implementation
