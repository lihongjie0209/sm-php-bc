# SM2/SM3/SM4 PHP Implementation Instructions

## Project Overview
This project aims to provide a pure PHP implementation of the Chinese National Standard (SM) cryptographic algorithms (SM2, SM3, SM4), strictly following the architecture and logic of the Bouncy Castle Java (`bc-java`) library, similar to the TypeScript implementation (`sm-js-bc`).

## Core Requirements

1.  **Reference Implementation**:
    *   Primary Reference: `sm-js-bc` (TypeScript implementation).
    *   Secondary Reference: `bc-java` (Bouncy Castle Java).
    *   The goal is a one-to-one port of the logic and structure.

2.  **Tech Stack**:
    *   Language: PHP 8.1+ (Strict typing).
    *   Dependency Manager: Composer.
    *   Testing Framework: PHPUnit.
    *   Style Standard: PSR-12.

3.  **Design Principles**:
    *   **Structure**: Mirror the package structure of `bc-java` / `sm-js-bc` (e.g., `Crypto\Digests`, `Crypto\Engines`, `Math\EC`).
    *   **Logic**: Port internal logic exactly to ensure identical behavior.
    *   **Dependencies**: Minimize external runtime dependencies. Use `ext-gmp` for BigInteger operations if necessary for performance, or wrap it to match `BigInteger` API.
    *   **Namespaces**: Use `SmBc\` as the root namespace.

4.  **Testing Strategy**:
    *   **Test-First (TDD)**: Write tests before implementation.
    *   **Test Vectors**: Reuse test vectors from `sm-js-bc` (located in `test/test-vectors` or inline in tests).
    *   **Consistency**: Ensure outputs match exactly with `sm-js-bc` and standard vectors.

## Algorithm Scope

### SM3 (Hash Algorithm)
*   `SM3Digest`: Implementation of the SM3 digest algorithm.
*   Block size: 512 bits.
*   Digest size: 256 bits.

### SM2 (Elliptic Curve)
*   **Infrastructure**: Point arithmetic, Field elements, Curve definitions.
*   **Signer**: `SM2Signer` (Signature generation/verification).
*   **Engine**: `SM2Engine` (Public key encryption/decryption).
*   **KeyExchange**: `SM2KeyExchange` (Key agreement).

### SM4 (Block Cipher)
*   **Engine**: `SM4Engine` (Basic block cipher).
*   **Modes**: ECB, CBC, CTR, GCM.
*   **Padding**: PKCS7Padding.

## Development Workflow
1.  **Initialize**: Setup project structure and tools.
2.  **Plan**: Detailed implementation phases.
3.  **Implement**:
    *   Create documentation for the module.
    *   Create test case (port from JS/Java).
    *   Implement code to pass test.
    *   Refactor.
4.  **Verify**: Run full test suite.

## File Structure Convention
```
sm-php-bc/
├── src/
│   ├── Crypto/
│   │   ├── Digests/
│   │   ├── Engines/
│   │   ├── Signers/
│   │   ├── Params/
│   │   └── ...
│   ├── Math/
│   │   ├── EC/
│   │   └── ...
│   ├── Util/
│   └── Exceptions/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Data/ (Test vectors)
├── docs/
├── composer.json
└── phpunit.xml
```
