# Implementation Plan for SM-PHP-BC

## Phase 1: Infrastructure & Project Setup
**Goal**: Set up the environment and implement basic utility classes required for cryptographic operations.

### 1.1 Project Initialization
- [ ] Initialize `composer.json` with PSR-4 autoloading (`SmBc\\` -> `src/`).
- [ ] Setup `phpunit.xml` for testing.
- [ ] Setup `phpcs` for code style (PSR-12).

### 1.2 Core Utilities
- [ ] **Pack**: Byte-to-int/long conversions (Big Endian).
    - Port from `sm-js-bc/src/util/Pack.ts`.
- [ ] **Arrays**: Array manipulation utilities (fill, copy, compare).
    - Port from `sm-js-bc/src/util/Arrays.ts`.
- [ ] **Integers**: Integer bitwise operations (rotate).
    - Port from `sm-js-bc/src/util/Integers.ts`.
- [ ] **Hex**: Hex string encoding/decoding.

### 1.3 Big Integer Support
- [ ] **BigInteger**: Wrapper around PHP `GMP` extension to match Bouncy Castle's `BigInteger` API (or `sm-js-bc`'s native BigInt usage).
    - Key methods: `add`, `subtract`, `multiply`, `divide`, `mod`, `modPow`, `modInverse`, `testBit`, `setBit`, `toByteArray`, `fromByteArray`.

### 1.4 Exceptions
- [ ] `CryptoException`
- [ ] `DataLengthException`
- [ ] `InvalidCipherTextException`

## Phase 2: SM3 Hash Algorithm
**Goal**: Implement the SM3 message digest algorithm.

### 2.1 Interfaces & Abstract Classes
- [ ] `Digest` Interface.
- [ ] `ExtendedDigest` Interface.
- [ ] `Memoable` Interface.
- [ ] `GeneralDigest` Abstract Class (block processing logic).

### 2.2 SM3 Implementation
- [ ] `SM3Digest`: The core algorithm.
    - Port logic from `sm-js-bc/src/crypto/digests/SM3Digest.ts`.
    - Implement `processBlock`, `processWord`, padding logic.
    - Constants `T`, `IV`.
    - Boolean functions `FF`, `GG`, permutations `P0`, `P1`.

### 2.3 Testing
- [ ] Port standard test vectors from `sm-js-bc/test/unit/crypto/digests/SM3Digest.test.ts`.
- [ ] Verify empty string, short string, long string.

## Phase 3: Elliptic Curve (EC) Math
**Goal**: Implement the mathematical foundation for SM2.

### 3.1 Field Math
- [ ] `FiniteField` Interface.
- [ ] `ECFieldElement`: Abstract class and Fp implementation.
    - Modular arithmetic on the field.

### 3.2 Curve & Points
- [ ] `ECCurve`: Abstract class and Fp implementation.
    - Define SM2 parameters (p, a, b, n, G).
- [ ] `ECPoint`: Point arithmetic (add, twice, negate, multiply).
    - Coordinate systems (Affine, Jacobian if optimized).

### 3.3 Multiplication
- [ ] `ECMultiplier` Interface.
- [ ] `FixedPointCombMultiplier`: Optimization for scalar multiplication (if needed, or start with basic).

## Phase 4: SM2 Cryptography
**Goal**: Implement SM2 Sign, Encrypt, and Key Exchange.

### 4.1 Parameters
- [ ] `CipherParameters`.
- [ ] `ECKeyParameters` (Private/Public).
- [ ] `ECDomainParameters`.
- [ ] `ParametersWithID`.
- [ ] `ParametersWithRandom`.

### 4.2 Key Generation
- [ ] `ECKeyPairGenerator`.

### 4.3 Signatures
- [x] `DSAKCalculator` & `RandomDSAKCalculator`. ✅ **COMPLETED 2025-12-06**
- [x] `DSAEncoding` & `StandardDSAEncoding`. ✅ **COMPLETED 2025-12-06**
- [x] `SM2Signer`. ✅ **COMPLETED 2025-12-06**
    - `sign`, `verify`.
    - User ID (ZA) handling.
    - Full test suite with 18 test cases (100% pass rate).

### 4.4 Encryption (Asymmetric)
- [x] `SM2Engine`. ✅ **COMPLETED 2025-12-06**
    - Mode: C1C2C3 / C1C3C2.
    - KDF (Key Derivation Function) based on SM3.
    - Full test suite with 23 test cases.
    - See `SM2ENGINE_IMPLEMENTATION.md` for details.

### 4.5 Key Exchange
- [ ] `SM2KeyExchange`.

## Phase 5: SM4 Block Cipher
**Goal**: Implement SM4 symmetric encryption.

### 5.1 Core Engine
- [x] `SM4Engine`. ✅ **COMPLETED 2025-12-06**
    - Block size: 16 bytes.
    - 32 rounds encryption/decryption.
    - Key expansion (forward/reverse).
    - Full test suite with 11 test cases.
    - GB/T 32907-2016 test vectors validated.

### 5.1.1 Padding
- [x] `PKCS7Padding`. ✅ **COMPLETED 2025-12-06**
    - Full test suite with 12 test cases.

### 5.2 Modes of Operation
- [ ] `ECB` (Electronic Codebook) - Can be manually implemented.
- [x] `CBC` (Cipher Block Chaining). ✅ **COMPLETED 2025-12-06**
    - Full IV support.
    - Proper chaining encryption/decryption.
    - Full test suite with 9 test cases.
- [x] `CTR` (Counter). ✅ **COMPLETED 2025-12-06**
    - Stream cipher mode.
    - No padding required.
    - Byte-level processing.
    - Full test suite with 13 test cases.
- [ ] `GCM` (Galois/Counter Mode).

### 5.3 Padding
- [ ] `BlockCipherPadding` Interface.
- [ ] `PKCS7Padding`.

## Phase 6: Finalization
- [ ] Comprehensive Documentation.
- [ ] Full Test Suite execution.
- [ ] CI/CD Configuration (optional).

## Roadmap
1.  **Day 1**: Phase 1 (Setup, Utils) & Phase 2 (SM3).
2.  **Day 2**: Phase 3 (EC Math) & Phase 4 (SM2 Signer).
3.  **Day 3**: Phase 4 (SM2 Encrypt/Exchange).
4.  **Day 4**: Phase 5 (SM4).
5.  **Day 5**: Review & Refine.
