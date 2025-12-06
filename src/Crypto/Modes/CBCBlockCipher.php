<?php

declare(strict_types=1);

namespace SmBc\Crypto\Modes;

use SmBc\Crypto\BlockCipher;
use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\ParametersWithIV;
use RuntimeException;

/**
 * Cipher Block Chaining (CBC) mode.
 * 
 * In CBC mode, each plaintext block is XORed with the previous ciphertext block
 * before being encrypted. This creates a dependency chain between blocks.
 * 
 * Based on: org.bouncycastle.crypto.modes.CBCBlockCipher
 */
class CBCBlockCipher implements BlockCipher
{
    private BlockCipher $cipher;
    private int $blockSize;
    
    private string $IV;
    private string $cbcV;
    private string $cbcNextV;
    
    private bool $encrypting = false;

    /**
     * Basic constructor.
     * 
     * @param BlockCipher $cipher The block cipher to be used as the basis of chaining
     */
    public function __construct(BlockCipher $cipher)
    {
        $this->cipher = $cipher;
        $this->blockSize = $cipher->getBlockSize();
        
        $this->IV = str_repeat("\x00", $this->blockSize);
        $this->cbcV = str_repeat("\x00", $this->blockSize);
        $this->cbcNextV = str_repeat("\x00", $this->blockSize);
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
     * Initialize the cipher and, possibly, the initialization vector (IV).
     * If an IV isn't passed as part of the parameter, the IV will be all zeros.
     * 
     * @param bool $forEncryption True for encryption, false for decryption
     * @param CipherParameters $params The key and other data required by the cipher
     */
    public function init(bool $forEncryption, CipherParameters $params): void
    {
        $oldEncrypting = $this->encrypting;
        $this->encrypting = $forEncryption;

        if ($params instanceof ParametersWithIV) {
            $iv = $params->getIV();

            if (strlen($iv) !== $this->blockSize) {
                throw new RuntimeException(
                    'Initialization vector must be the same length as block size'
                );
            }

            $this->IV = $iv;
            $params = $params->getParameters();
        } else {
            $this->IV = str_repeat("\x00", $this->blockSize);
        }

        $this->reset();

        // If params is null, it's an IV change only (key is to be reused)
        if ($params !== null) {
            $this->cipher->init($forEncryption, $params);
        } elseif ($oldEncrypting !== $forEncryption) {
            throw new RuntimeException(
                'Cannot change encrypting state without providing key'
            );
        }
    }

    /**
     * Return the algorithm name and mode.
     * 
     * @return string The name of the underlying algorithm followed by "/CBC"
     */
    public function getAlgorithmName(): string
    {
        return $this->cipher->getAlgorithmName() . '/CBC';
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
     * Process one block of input from the array in and write it to the out array.
     * 
     * @param string $input The array containing the input data
     * @param int $inOff Offset into the in array the data starts at
     * @param string $output The array the output data will be copied into
     * @param int $outOff The offset into the out array the output will start at
     * @return int The number of bytes processed and produced
     */
    public function processBlock(string $input, int $inOff, string &$output, int $outOff): int
    {
        return $this->encrypting
            ? $this->encryptBlock($input, $inOff, $output, $outOff)
            : $this->decryptBlock($input, $inOff, $output, $outOff);
    }

    /**
     * Reset the chaining vector back to the IV and reset the underlying cipher.
     */
    public function reset(): void
    {
        $this->cbcV = $this->IV;
        $this->cbcNextV = str_repeat("\x00", $this->blockSize);
        $this->cipher->reset();
    }

    /**
     * Do the appropriate chaining step for CBC mode encryption.
     * 
     * @param string $input The array containing the data to be encrypted
     * @param int $inOff Offset into the in array the data starts at
     * @param string $output The array the encrypted data will be copied into
     * @param int $outOff The offset into the out array the output will start at
     * @return int The number of bytes processed and produced
     */
    private function encryptBlock(string $input, int $inOff, string &$output, int $outOff): int
    {
        if ($inOff + $this->blockSize > strlen($input)) {
            throw new RuntimeException('Input buffer too short');
        }

        // XOR the cbcV and the input, then encrypt the cbcV
        for ($i = 0; $i < $this->blockSize; $i++) {
            $this->cbcV[$i] = chr(ord($this->cbcV[$i]) ^ ord($input[$inOff + $i]));
        }

        $length = $this->cipher->processBlock($this->cbcV, 0, $output, $outOff);

        // Copy ciphertext to cbcV
        $this->cbcV = substr($output, $outOff, $this->blockSize);

        return $length;
    }

    /**
     * Do the appropriate chaining step for CBC mode decryption.
     * 
     * @param string $input The array containing the data to be decrypted
     * @param int $inOff Offset into the in array the data starts at
     * @param string $output The array the decrypted data will be copied into
     * @param int $outOff The offset into the out array the output will start at
     * @return int The number of bytes processed and produced
     */
    private function decryptBlock(string $input, int $inOff, string &$output, int $outOff): int
    {
        if ($inOff + $this->blockSize > strlen($input)) {
            throw new RuntimeException('Input buffer too short');
        }

        // Save input ciphertext for next round
        $this->cbcNextV = substr($input, $inOff, $this->blockSize);

        $length = $this->cipher->processBlock($input, $inOff, $output, $outOff);

        // XOR the cbcV and the output
        for ($i = 0; $i < $this->blockSize; $i++) {
            $output[$outOff + $i] = chr(ord($output[$outOff + $i]) ^ ord($this->cbcV[$i]));
        }

        // Update cbcV for next round
        $this->cbcV = $this->cbcNextV;

        return $length;
    }
}
