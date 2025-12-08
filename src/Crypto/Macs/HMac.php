<?php

declare(strict_types=1);

namespace SmBc\Crypto\Macs;

use SmBc\Crypto\Mac;
use SmBc\Crypto\Digest;
use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\KeyParameter;
use InvalidArgumentException;
use RuntimeException;

/**
 * HMAC implementation based on a hash function (RFC 2104)
 * 
 * This implementation follows the Bouncy Castle Java design and supports
 * any underlying digest algorithm.
 * 
 * Based on: org.bouncycastle.crypto.macs.HMac
 *           sm-js-bc/src/crypto/macs/HMac.ts
 */
class HMac implements Mac
{
    private const IPAD = 0x36;
    private const OPAD = 0x5C;

    private Digest $digest;
    private int $digestSize;
    private int $blockLength;

    private string $inputPad;
    private string $outputBuf;

    /**
     * Create an HMAC instance with the given digest
     * 
     * @param Digest $digest The underlying hash function (e.g., SM3Digest)
     */
    public function __construct(Digest $digest)
    {
        $this->digest = $digest;
        $this->digestSize = $digest->getDigestSize();
        
        // Get the block length from the digest
        // If the digest has getByteLength() method, use it
        if (method_exists($digest, 'getByteLength')) {
            $this->blockLength = $digest->getByteLength();
        } else {
            // Default to 64 bytes (SHA-1, SHA-256, SM3)
            $this->blockLength = 64;
        }

        $this->inputPad = str_repeat("\x00", $this->blockLength);
        $this->outputBuf = str_repeat("\x00", $this->blockLength + $this->digestSize);
    }

    /**
     * Get the algorithm name
     * 
     * @return string The algorithm name in format "HMac/{digest-name}"
     */
    public function getAlgorithmName(): string
    {
        return 'HMac/' . $this->digest->getAlgorithmName();
    }

    /**
     * Get the MAC size (same as the underlying digest size)
     * 
     * @return int The MAC size in bytes
     */
    public function getMacSize(): int
    {
        return $this->digestSize;
    }

    /**
     * Initialize the HMAC with a key
     * 
     * @param CipherParameters $params The key parameter
     * @throws InvalidArgumentException If params is not a KeyParameter
     */
    public function init(CipherParameters $params): void
    {
        $this->digest->reset();

        if (!($params instanceof KeyParameter)) {
            throw new InvalidArgumentException('HMac requires KeyParameter');
        }

        $key = $params->getKey();
        $keyLength = strlen($key);

        // If the key is longer than the block size, hash it first
        if ($keyLength > $this->blockLength) {
            $this->digest->updateBytes($key, 0, $keyLength);
            $hashedKey = str_repeat("\x00", $this->digestSize);
            $this->digest->doFinal($hashedKey, 0);
            
            // Pad the hashed key to block length
            $this->inputPad = $hashedKey . str_repeat("\x00", $this->blockLength - $this->digestSize);
            $keyLength = $this->digestSize;
        } else {
            // Copy the key to inputPad and pad with zeros
            $this->inputPad = $key . str_repeat("\x00", $this->blockLength - $keyLength);
        }
        
        // Copy inputPad to outputBuf (first blockLength bytes)
        $this->outputBuf = $this->inputPad . str_repeat("\x00", $this->digestSize);

        // XOR the key with ipad for the input padding
        $this->xorPad($this->inputPad, $this->blockLength, self::IPAD);
        
        // XOR the key with opad for the output padding
        $this->xorPad($this->outputBuf, $this->blockLength, self::OPAD);

        // Initialize the inner hash
        $this->digest->updateBytes($this->inputPad, 0, strlen($this->inputPad));
    }

    /**
     * XOR a pad with a specific byte value
     * 
     * @param string &$pad The padding buffer
     * @param int $len The length to XOR
     * @param int $n The byte value to XOR with
     */
    private function xorPad(string &$pad, int $len, int $n): void
    {
        $padBytes = unpack('C*', substr($pad, 0, $len));
        for ($i = 1; $i <= $len; $i++) {
            $padBytes[$i] ^= $n;
        }
        $pad = pack('C*', ...$padBytes) . substr($pad, $len);
    }

    /**
     * Update the MAC with a single byte
     * 
     * @param int $input The input byte
     */
    public function update(int $input): void
    {
        $this->digest->update($input);
    }

    /**
     * Update the MAC with multiple bytes
     * 
     * @param string $input The input byte array
     * @param int $inOff The offset into the input array
     * @param int $len The number of bytes to process
     */
    public function updateBytes(string $input, int $inOff, int $len): void
    {
        $this->digest->updateBytes($input, $inOff, $len);
    }

    /**
     * Complete the MAC calculation
     * 
     * @param string &$out The output buffer
     * @param int $outOff The offset into the output buffer
     * @return int The number of bytes written
     * @throws RuntimeException If the output buffer is too small
     */
    public function doFinal(string &$out, int $outOff): int
    {
        if (strlen($out) - $outOff < $this->digestSize) {
            throw new RuntimeException('Output buffer too small');
        }

        // Complete the inner hash: H(K ⊕ ipad || message)
        $innerHash = str_repeat("\x00", $this->digestSize);
        $this->digest->doFinal($innerHash, 0);
        
        // Store inner hash in outputBuf after the opad
        for ($i = 0; $i < $this->digestSize; $i++) {
            $this->outputBuf[$this->blockLength + $i] = $innerHash[$i];
        }

        // Compute the outer hash: H(K ⊕ opad || inner_hash)
        $this->digest->updateBytes($this->outputBuf, 0, $this->blockLength + $this->digestSize);
        $result = $this->digest->doFinal($out, $outOff);

        // Reset for next use
        // Re-initialize the inner hash with the input pad
        $this->digest->updateBytes($this->inputPad, 0, strlen($this->inputPad));

        return $result;
    }

    /**
     * Reset the MAC to its initialized state
     */
    public function reset(): void
    {
        // Reset the underlying digest
        $this->digest->reset();

        // Re-initialize with the input pad (K ⊕ ipad)
        $this->digest->updateBytes($this->inputPad, 0, strlen($this->inputPad));
    }
}
