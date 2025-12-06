# Quick Start Guide - SM-PHP-BC

## Prerequisites

- PHP 8.1 or higher
- ext-gmp extension enabled
- Composer installed

## Installation

```bash
cd D:\code\sm-bc\sm-php-bc
# Dependencies should already be installed
# If not, run: composer install
```

## Running Tests

### All Tests
```bash
D:\code\sm-bc\bin\php\php.exe vendor\bin\phpunit tests\Unit
```

### SM2Engine Tests Only
```bash
D:\code\sm-bc\bin\php\php.exe vendor\bin\phpunit tests\Unit\Crypto\Engines\SM2EngineTest.php
```

### With Test Names (Detailed Output)
```bash
D:\code\sm-bc\bin\php\php.exe vendor\bin\phpunit tests\Unit --testdox
```

### Manual Test Script
```bash
D:\code\sm-bc\bin\php\php.exe tests\manual_sm2engine.php
```

## Quick Usage Example

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

// Setup SM2 curve parameters
$p = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFF');
$a = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFC');
$b = new BigInteger('0x28E9FA9E9D9F5E344D5A9E4BCF6509A7F39789F515AB8F92DDBCBD414D940E93');
$n = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123');
$curve = new ECCurveFp($p, $a, $b, $n, BigInteger::ONE());

$gx = new BigInteger('0x32C4AE2C1F1981195F9904466A39C9948FE30BBFF2660BE1715A4589334C74C7');
$gy = new BigInteger('0xBC3736A2F4F6779C59BDCEE36B692153D0A9877CC62A474002DF32E52139F0A0');
$g = $curve->createPoint($gx, $gy);

$domainParams = new ECDomainParameters($curve, $g, $n, BigInteger::ONE());

// Generate key pair (or load existing keys)
$d = new BigInteger('0x128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263');
$q = $g->multiply($d)->normalize();

$privateKey = new ECPrivateKeyParameters($d, $domainParams);
$publicKey = new ECPublicKeyParameters($q, $domainParams);

// Encrypt
$message = 'Hello, SM2!';
$engine = new SM2Engine(); // Default C1C2C3 mode
$params = new ParametersWithRandom($publicKey, new SecureRandom());
$engine->init(true, $params);
$ciphertext = $engine->processBlock($message, 0, strlen($message));

// Decrypt
$engine->init(false, $privateKey);
$decrypted = $engine->processBlock($ciphertext, 0, strlen($ciphertext));

echo "Original:  $message\n";
echo "Decrypted: $decrypted\n";
```

## Available Cipher Modes

```php
// C1C2C3 mode (default)
$engine = new SM2Engine();
// or explicitly:
$engine = new SM2Engine(null, SM2Engine::MODE_C1C2C3);

// C1C3C2 mode
$engine = new SM2Engine(null, SM2Engine::MODE_C1C3C2);
```

## Common Tasks

### SM3 Hashing

```php
use SmBc\Crypto\Digests\SM3Digest;

$digest = new SM3Digest();
$data = 'Hello, World!';
$digest->updateBytes($data, 0, strlen($data));

$hash = str_repeat("\x00", 32);
$digest->doFinal($hash, 0);

echo "Hash: " . bin2hex($hash) . "\n";
```

### Error Handling

```php
try {
    $engine = new SM2Engine();
    $params = new ParametersWithRandom($publicKey, new SecureRandom());
    $engine->init(true, $params);
    $ciphertext = $engine->processBlock($message, 0, strlen($message));
} catch (InvalidArgumentException $e) {
    echo "Invalid argument: " . $e->getMessage();
} catch (RuntimeException $e) {
    echo "Runtime error: " . $e->getMessage();
}
```

## Troubleshooting

### PHP Not Found
Use the project-specific PHP:
```bash
D:\code\sm-bc\bin\php\php.exe
```

### ext-gmp Not Loaded
Check `php.ini` and ensure:
```ini
extension=gmp
```

### Autoload Issues
Regenerate autoload:
```bash
composer dump-autoload
```

### Test Failures
Run with verbose output:
```bash
D:\code\sm-bc\bin\php\php.exe vendor\bin\phpunit tests\Unit --verbose
```

## Documentation

- **SM2ENGINE_IMPLEMENTATION.md** - Detailed implementation docs
- **TEST_RESULTS.md** - Test results and analysis
- **USAGE_EXAMPLE.md** - Comprehensive usage examples
- **IMPLEMENTATION_PLAN.md** - Project roadmap

## Project Status

- ✅ SM3 Digest
- ✅ EC Math (Curves, Points, Field Elements)
- ✅ SM2 Engine (Encryption/Decryption)
- ⏳ SM2 Signer (Planned)
- ⏳ SM2 KeyExchange (Planned)
- ⏳ SM4 Block Cipher (Planned)

## Support

For issues or questions:
1. Check existing documentation
2. Review test cases for examples
3. Examine reference implementation: `sm-js-bc`

## License

[See repository license file]

---

**Last Updated:** 2025-12-06  
**Version:** 1.0.0  
**PHP Version:** 8.3.28+
