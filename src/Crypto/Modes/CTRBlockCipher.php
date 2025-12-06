<?php

declare(strict_types=1);

namespace SmBc\Crypto\Modes;

use SmBc\Crypto\BlockCipher;
use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\ParametersWithIV;
use RuntimeException;

/**
 * Counter (CTR) mode.
 * 
 * CTR mode turns a block cipher into a stream cipher by encrypting
 * successive counter values and XORing them with the plaintext/ciphertext.
 * 
 * Features:
 * - Parallelizable encryption/decryption
 * - Random access to any block
 * - No padding required
 * - Encryption and decryption use the same operation
 * 
 * Based on: NIST SP 800-38A
 */
class CTRBlockCipher implements BlockCipher
{
    private BlockCipher $cipher;
    private int $blockSize;
    
    private string $IV;
    private string $counter;
    private string $counterOut;
    private int $byteCount;

    /**
     * Basic constructor.
     * 
     * @param BlockCipher $cipher The block cipher to be used
     */
    public function __construct(BlockCipher $cipher)
    {
        $this->cipher = $cipher;
        $this->blockSize = $cipher->getBlockSize();
        
        $this->IV = str_repeat("\x00", $this->blockSize);
        $this->counter = str_repeat("\x00", $this->blockSize);
        $this->counterOut = str_repeat("\x00", $this->blockSize);
        $this->byteCount = 0;
    }

    /**
     * Return the underlying block cipher that we are wrapping.
     * 
     * @return BlockCipher The underlying block cipher
     */
    public function getUnderlyingCipher(): BlockCipher
    {
        return $this->cipher;
    }

    /**
     * Initialize the cipher and the counter.
     * The IV is used as the initial counter value.
     * 
     * @param bool $forEncryption Not used in CTR mode (same operation for enc/dec)
     * @param CipherParameters $params The key and IV
     */
    public function init(bool $forEncryption, CipherParameters $params): void
    {
        if ($params instanceof ParametersWithIV) {
            $iv = $params->getIV();

            if (strlen($iv) !== $this->blockSize) {
                throw new RuntimeException(
                    'CTR mode requires IV same length as block size'
                );
            }

            $this->IV = $iv;
            $params = $params->getParameters();
        } else {
            throw new RuntimeException('CTR mode requires IV');
        }

        $this->reset();

        // Always initialize for encryption since CTR uses encryption for both
        $this->cipher->init(true, $params);
    }

    /**
     * Return the algorithm name and mode.
     * 
     * @return string The name of the underlying algorithm followed by "/CTR"
     */
    public function getAlgorithmName(): string
    {
        return $this->cipher->getAlgorithmName() . '/CTR';
    }

    /**
     * Return the block size of the underlying cipher.
     * 
     * @return int The block size
     */
    public function getBlockSize(): int
    {
        return $this->cipher->getBlockSize();
    }

    /**
     * Process one block of input.
     * 
     * In CTR mode, this encrypts the counter and XORs with input.
     * 
     * @param string $input The input data
     * @param int $inOff Offset into the input
     * @param string $output The output buffer
     * @param int $outOff Offset into the output
     * @return int The number of bytes processed
     */
    public function processBlock(string $input, int $inOff, string &$output, int $outOff): int
    {
        if ($inOff + $this->blockSize > strlen($input)) {
            throw new RuntimeException('Input buffer too short');
        }

        if ($outOff + $this->blockSize > strlen($output)) {
            throw new RuntimeException('Output buffer too short');
        }

        // Encrypt the counter
        $this->cipher->processBlock($this->counter, 0, $this->counterOut, 0);

        // XOR with input
        for ($i = 0; $i < $this->blockSize; $i++) {
            $output[$outOff + $i] = chr(
                ord($input[$inOff + $i]) ^ ord($this->counterOut[$i])
            );
        }

        // Increment counter for next block
        $this->incrementCounter();

        return $this->blockSize;
    }

    /**
     * Process a single byte.
     * 
     * @param int $in The input byte
     * @return int The output byte
     */
    public function processByte(int $in): int
    {
        // Generate keystream byte if needed
        if ($this->byteCount === 0) {
            $this->cipher->processBlock($this->counter, 0, $this->counterOut, 0);
            $this->incrementCounter();
        }

        $out = $in ^ ord($this->counterOut[$this->byteCount]);
        $this->byteCount = ($this->byteCount + 1) % $this->blockSize;

        return $out;
    }

    /**
     * Process an array of bytes.
     * 
     * @param string $input The input data
     * @param int $inOff Offset into input
     * @param int $length Number of bytes to process
     * @param string $output The output buffer
     * @param int $outOff Offset into output
     * @return int The number of bytes processed
     */
    public function processBytes(string $input, int $inOff, int $length, string &$output, int $outOff): int
    {
        if ($inOff + $length > strlen($input)) {
            throw new RuntimeException('Input buffer too short');
        }

        if ($outOff + $length > strlen($output)) {
            throw new RuntimeException('Output buffer too short');
        }

        for ($i = 0; $i < $length; $i++) {
            // Generate keystream byte if needed
            if ($this->byteCount === 0) {
                $this->cipher->processBlock($this->counter, 0, $this->counterOut, 0);
                $this->incrementCounter();
            }

            $output[$outOff + $i] = chr(
                ord($input[$inOff + $i]) ^ ord($this->counterOut[$this->byteCount])
            );
            
            $this->byteCount = ($this->byteCount + 1) % $this->blockSize;
        }

        return $length;
    }

    /**
     * Reset the cipher back to the initial counter value.
     */
    public function reset(): void
    {
        $this->counter = $this->IV;
        $this->counterOut = str_repeat("\x00", $this->blockSize);
        $this->byteCount = 0;
        $this->cipher->reset();
    }

    /**
     * Increment the counter by 1.
     * Uses big-endian increment.
     */
    private function incrementCounter(): void
    {
        // Increment as big-endian
        for ($i = $this->blockSize - 1; $i >= 0; $i--) {
            $this->counter[$i] = chr((ord($this->counter[$i]) + 1) & 0xFF);
            
            // If not overflow, we're done
            if (ord($this->counter[$i]) !== 0) {
                break;
            }
        }
    }
}
