# Next Steps for SM-PHP-BC

## Current Status (2025-12-06)

### ✅ Completed Features

1. **Infrastructure** (100%)
   - ✅ Pack (byte operations)
   - ✅ Arrays (array utilities)
   - ✅ Integers (bitwise operations)
   - ✅ BigInteger (large number arithmetic with GMP)
   - ✅ SecureRandom (random number generation)

2. **SM3 Digest** (100%)
   - ✅ Full SM3 hash implementation
   - ✅ Memoable interface support
   - ✅ Comprehensive test suite

3. **Elliptic Curve Math** (100%)
   - ✅ ECFieldElement (Fp)
   - ✅ ECCurve (Fp)
   - ✅ ECPoint (Fp with Jacobian coordinates)
   - ✅ Point arithmetic (add, double, multiply)

4. **SM2 Encryption** (100%)
   - ✅ SM2Engine implementation
   - ✅ Both C1C2C3 and C1C3C2 modes
   - ✅ KDF based on SM3
   - ✅ 23 comprehensive tests (100% pass rate)

### 📊 Test Statistics

```
Total Tests: 63
Pass Rate: 100%
Code Coverage: High
```

---

## 🎯 Priority 1: SM2 Signer Implementation

### Overview
Implement SM2 digital signature algorithm for signing and verification.

### Required Components

#### 1. DSAKCalculator Interface & Implementation
**Files to create:**
- `src/Crypto/Signers/DSAKCalculator.php` (interface)
- `src/Crypto/Signers/RandomDSAKCalculator.php` (implementation)

**Purpose:** Generate random k values for signature generation

**Reference:** `sm-js-bc/src/crypto/signers/DSAKCalculator.ts`

#### 2. DSA Encoding
**Files to create:**
- `src/Crypto/Signers/DSAEncoding.php` (interface)
- `src/Crypto/Signers/StandardDSAEncoding.php` (DER encoding)

**Purpose:** Encode/decode signature (r, s) values

**Reference:** `sm-js-bc/src/crypto/signers/StandardDSAEncoding.ts`

#### 3. SM2Signer
**File to create:**
- `src/Crypto/Signers/SM2Signer.php`

**Key Methods:**
```php
init(bool $forSigning, CipherParameters $param): void
update($input, int $offset = 0, int $length = 0): void
generateSignature(): string
verifySignature(string $signature): bool
reset(): void
```

**Key Features:**
- User ID handling (default: "1234567812345678")
- Z_A calculation (digest of user ID and public key)
- Sign with private key
- Verify with public key
- SM3 digest integration

**Reference:** `sm-js-bc/src/crypto/signers/SM2Signer.ts` (413 lines)

#### 4. Test Suite
**File to create:**
- `tests/Unit/Crypto/Signers/SM2SignerTest.php`

**Test Coverage:**
- Basic sign/verify round-trip
- Different message lengths
- User ID variations
- Invalid signature detection
- Standard test vectors from GM/T 0003.2-2012

**Reference:** `sm-js-bc/test/unit/crypto/SM2Signer.test.ts`

### Implementation Complexity

**Estimated Effort:** 3-4 hours

**Complexity Factors:**
1. DSA K calculator logic
2. DER encoding/decoding for signatures
3. Z_A calculation algorithm
4. Modular inverse computation
5. Signature generation loop (retry logic)
6. Comprehensive test vectors

### Implementation Order

1. **Phase 1:** DSAKCalculator interface and RandomDSAKCalculator (~30 min)
2. **Phase 2:** DSAEncoding and StandardDSAEncoding (~45 min)
3. **Phase 3:** SM2Signer core implementation (~90 min)
4. **Phase 4:** Test suite with test vectors (~60 min)

---

## 🎯 Priority 2: SM2 Key Exchange

### Overview
Implement SM2 key agreement protocol for secure key establishment.

### Required Components

**File to create:**
- `src/Crypto/Agreement/SM2KeyExchange.php`

**Key Methods:**
```php
init(CipherParameters $param): void
calculateAgreement(CipherParameters $pubKey): string
```

**Reference:** `sm-js-bc/src/crypto/agreement/SM2KeyExchange.ts`

**Estimated Effort:** 2-3 hours

---

## 🎯 Priority 3: SM4 Block Cipher

### Overview
Implement SM4 symmetric block cipher (128-bit block, 128-bit key).

### Required Components

#### 1. SM4Engine
**File to create:**
- `src/Crypto/Engines/SM4Engine.php`

**Features:**
- 128-bit block encryption/decryption
- Key expansion algorithm
- 32 rounds of transformation

#### 2. Block Cipher Modes
**Files to create:**
- `src/Crypto/Modes/ECBBlockCipher.php`
- `src/Crypto/Modes/CBCBlockCipher.php`
- `src/Crypto/Modes/CTRBlockCipher.php`
- `src/Crypto/Modes/GCMBlockCipher.php` (optional, complex)

#### 3. Padding
**Files to create:**
- `src/Crypto/Paddings/BlockCipherPadding.php` (interface)
- `src/Crypto/Paddings/PKCS7Padding.php`

**Reference:** `sm-js-bc/src/crypto/engines/SM4Engine.ts`

**Estimated Effort:** 4-6 hours

---

## 📝 Recommended Implementation Sequence

### Short Term (Next Session)
1. ✅ **SM2Signer** - Highest priority, completes SM2 suite
   - Most commonly needed feature
   - Required for digital signatures
   - Test vectors available

### Medium Term
2. **SM4Engine** - Second priority
   - Symmetric encryption is frequently needed
   - Simpler than key exchange
   - Useful standalone

3. **SM2KeyExchange** - Third priority
   - Specialized use case
   - Less frequently needed
   - Depends on Signer concepts

### Long Term
4. **Additional Features**
   - Key pair generation helpers
   - PEM/DER import/export
   - Integration examples
   - Performance optimizations

---

## 🛠️ Development Guidelines

### Before Starting
1. ✅ Use project PHP: `D:\code\sm-bc\bin\php\php.exe`
2. ✅ Review reference implementation in `sm-js-bc`
3. ✅ Port test cases first (TDD approach)
4. ✅ Follow PSR-12 coding standards
5. ✅ Add comprehensive PHPDoc comments

### During Development
1. ✅ Implement incrementally
2. ✅ Run tests frequently
3. ✅ Verify outputs match reference
4. ✅ Handle errors gracefully
5. ✅ Document non-obvious logic

### After Completion
1. ✅ Run full test suite
2. ✅ Update README.md
3. ✅ Update IMPLEMENTATION_PLAN.md
4. ✅ Create usage examples
5. ✅ Generate documentation

---

## 📚 Reference Materials

### Standards
- **GM/T 0003.1-2012:** SM2 Elliptic Curve Cryptography (Part 1: General)
- **GM/T 0003.2-2012:** SM2 Digital Signature Algorithm (Part 2)
- **GM/T 0003.3-2012:** SM2 Key Exchange Protocol (Part 3)
- **GM/T 0003.4-2012:** SM2 Public Key Encryption (Part 4)
- **GM/T 0003.5-2012:** SM2 Parameter Definition (Part 5)
- **GM/T 0004-2012:** SM3 Cryptographic Hash Algorithm
- **GM/T 0002-2012:** SM4 Block Cipher Algorithm

### Reference Implementations
- **Primary:** `sm-js-bc` (TypeScript) - Most complete
- **Secondary:** Bouncy Castle Java - Original reference
- **Tertiary:** `sm-py-bc` (Python) - Parallel implementation

### Test Vectors
- Located in `sm-js-bc/test/` directory
- Official GM/T standards
- Cross-implementation compatibility tests

---

## 🎯 Success Criteria

### Per Feature
- ✅ All tests pass (100%)
- ✅ Matches reference output
- ✅ PSR-12 compliant
- ✅ Fully documented
- ✅ No external crypto dependencies

### Overall Project
- ✅ Complete SM2 suite (Engine ✅, Signer ⏳, KeyExchange ⏳)
- ✅ Complete SM3 suite ✅
- ⏳ Complete SM4 suite
- ✅ Production-ready code
- ✅ Comprehensive test coverage
- ✅ Clear documentation

---

## 💡 Tips for SM2Signer Implementation

### Key Challenges

1. **Z_A Calculation**
   - Must hash: ENTL_A || ID_A || a || b || x_G || y_G || x_A || y_A
   - All values must be correctly sized
   - Byte order matters

2. **Signature Loop**
   - Must retry if r = 0 or s = 0
   - Must check r + k ≠ n
   - Need proper random k generation

3. **Modular Inverse**
   - Required for s calculation: (1 + d)^(-1)
   - Can use GMP's gmp_invert function
   - Handle edge cases

4. **DER Encoding**
   - Standard ASN.1 DER format for (r, s)
   - Handle variable-length integers
   - Proper padding for negative values

### Quick Start Code

```php
<?php

namespace SmBc\Crypto\Signers;

use SmBc\Crypto\Digest;
use SmBc\Crypto\Digests\SM3Digest;
use SmBc\Crypto\Params\CipherParameters;
// ... other imports

class SM2Signer implements Signer
{
    private const DEFAULT_USER_ID = '1234567812345678';
    
    private Digest $digest;
    private DSAKCalculator $dsaKCalculator;
    private bool $forSigning = false;
    private ?string $userID = null;
    private ?string $z = null;
    
    public function __construct(
        ?Digest $digest = null,
        ?DSAKCalculator $dsaKCalculator = null
    ) {
        $this->digest = $digest ?? new SM3Digest();
        $this->dsaKCalculator = $dsaKCalculator ?? new RandomDSAKCalculator();
        $this->userID = self::DEFAULT_USER_ID;
    }
    
    // ... implementation
}
```

---

## 📞 Support & Resources

### Documentation
- `SM2ENGINE_IMPLEMENTATION.md` - SM2 Engine details
- `TEST_RESULTS.md` - Test results and analysis
- `USAGE_EXAMPLE.md` - Usage examples
- `QUICK_START.md` - Quick start guide

### Commands
```bash
# Run all tests
D:\code\sm-bc\bin\php\php.exe vendor\bin\phpunit tests\Unit

# Run specific test
D:\code\sm-bc\bin\php\php.exe vendor\bin\phpunit tests\Unit\Crypto\Signers\SM2SignerTest.php

# Verbose output
D:\code\sm-bc\bin\php\php.exe vendor\bin\phpunit tests\Unit --testdox --verbose
```

---

**Status:** Ready for SM2Signer implementation  
**Next Action:** Implement DSAKCalculator and RandomDSAKCalculator  
**Expected Completion:** 3-4 hours for full SM2Signer suite

**Last Updated:** 2025-12-06  
**Document Version:** 1.0
