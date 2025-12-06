<?php

declare(strict_types=1);

namespace SmBc\Crypto\Modes;

use SmBc\Crypto\BlockCipher;
use SmBc\Crypto\Params\CipherParameters;
use SmBc\Crypto\Params\ParametersWithIV;
use SmBc\Exceptions\DataLengthException;

/**
 * Implements Output Feedback (OFB) mode on top of a simple block cipher.
 * 
 * In OFB mode, the block cipher encrypts the previous output to produce the next keystream block.
 * The keystream is then XORed with the plaintext/ciphertext.
 * 
 * Key characteristics:
 * - Encryption and decryption are identical operations (XOR with keystream)
 * - Converts block cipher into stream cipher
 * - Does not propagate errors
 * - Feedback is from encrypted output, not ciphertext
 */
class OFBBlockCipher implements BlockCipher
{
    private int $byteCount;
    private string $IV;
    private string $ofbV;        // Output feedback register
    private string $ofbOutV;     // Encrypted output
    
    private readonly int $blockSize;
    private readonly BlockCipher $cipher;
    
    /**
     * Create an OFB mode cipher.
     * 
     * @param BlockCipher $cipher The block cipher to use as the basis of the feedback mode
     * @param int $bitBlockSize The block size in bits (must be multiple of 8, between 8 and cipher block size * 8)
     */
    public function __construct(BlockCipher $cipher, int $bitBlockSize)
    {
        $cipherBlockSize = $cipher->getBlockSize();
        
        if ($bitBlockSize > $cipherBlockSize * 8 || $bitBlockSize < 8 || $bitBlockSize % 8 !== 0) {
            throw new \InvalidArgumentException("OFB{$bitBlockSize} not supported");
        }
        
        $this->cipher = $cipher;
        $this->blockSize = $bitBlockSize / 8;
        
        $this->IV = str_repeat("\x00", $cipherBlockSize);
        $this->ofbV = str_repeat("\x00", $cipherBlockSize);
        $this->ofbOutV = str_repeat("\x00", $cipherBlockSize);
        
        $this->byteCount = 0;
    }
    
    /**
     * Initialize the cipher and possibly the initialization vector (IV).
     * 
     * Note: The encrypting parameter is ignored for OFB mode since encryption
     * and decryption are identical operations.
     * 
     * @param bool $encrypting Ignored (OFB encryption and decryption are identical)
     * @param CipherParameters $params The parameters (should be ParametersWithIV for first init)
     */
    public function init(bool $encrypting, CipherParameters $params): void
    {
        if ($params instanceof ParametersWithIV) {
            $iv = $params->getIV();
            
            if (strlen($iv) < strlen($this->IV)) {
                // Prepend the supplied IV with zeros (per FIPS PUB 81)
                $this->IV = str_repeat("\x00", strlen($this->IV) - strlen($iv)) . $iv;
            } else {
                $this->IV = substr($iv, 0, strlen($this->IV));
            }
            
            $this->reset();
            
            // If null, it's an IV change only
            $underlyingParams = $params->getParameters();
            if ($underlyingParams !== null) {
                // OFB always encrypts the feedback register, regardless of mode
                $this->cipher->init(true, $underlyingParams);
            }
        } else {
            $this->reset();
            
            // If it's null, key is to be reused
            if ($params !== null) {
                // OFB always encrypts the feedback register
                $this->cipher->init(true, $params);
            }
        }
    }
    
    /**
     * Get the algorithm name.
     */
    public function getAlgorithmName(): string
    {
        return $this->cipher->getAlgorithmName() . '/OFB' . ($this->blockSize * 8);
    }
    
    /**
     * Get the block size in bytes.
     */
    public function getBlockSize(): int
    {
        return $this->blockSize;
    }
    
    /**
     * Process a block of input.
     * 
     * @param string $input The input buffer
     * @param int $inOff The offset into input where data starts
     * @param string &$output The output buffer
     * @param int $outOff The offset into output where result will be written
     * @return int The number of bytes processed
     */
    public function processBlock(string $input, int $inOff, string &$output, int $outOff): int
    {
        $this->processBytes($input, $inOff, $this->blockSize, $output, $outOff);
        return $this->blockSize;
    }
    
    /**
     * Process a stream of bytes.
     * 
     * @param string $input The input buffer
     * @param int $inOff The offset into input where data starts
     * @param int $len The number of bytes to process
     * @param string &$output The output buffer
     * @param int $outOff The offset into output where result will be written
     * @return int The number of bytes processed
     */
    public function processBytes(string $input, int $inOff, int $len, string &$output, int $outOff): int
    {
        if ($inOff + $len > strlen($input)) {
            throw new DataLengthException('input buffer too short');
        }
        
        if ($outOff + $len > strlen($output)) {
            throw new DataLengthException('output buffer too short');
        }
        
        for ($i = 0; $i < $len; $i++) {
            $output[$outOff + $i] = chr($this->calculateByte(ord($input[$inOff + $i])));
        }
        
        return $len;
    }
    
    /**
     * Reset the feedback register back to the IV and reset the underlying cipher.
     */
    public function reset(): void
    {
        $this->ofbV = $this->IV;
        $this->byteCount = 0;
        $this->cipher->reset();
    }
    
    /**
     * Get the current IV/output feedback register state.
     */
    public function getCurrentIV(): string
    {
        return $this->ofbV;
    }
    
    /**
     * Calculate a single output byte.
     * 
     * @param int $inputByte The input byte
     * @return int The output byte (XOR of input with keystream)
     */
    private function calculateByte(int $inputByte): int
    {
        // Generate new keystream block if needed
        if ($this->byteCount === 0) {
            $this->cipher->processBlock($this->ofbV, 0, $this->ofbOutV, 0);
        }
        
        // XOR input with keystream
        $outputByte = ord($this->ofbOutV[$this->byteCount++]) ^ $inputByte;
        
        // Update feedback register when block is complete
        if ($this->byteCount === $this->blockSize) {
            $this->byteCount = 0;
            
            // Shift ofbV left by blockSize bytes and append encrypted output
            $this->ofbV = substr($this->ofbV, $this->blockSize) . 
                          substr($this->ofbOutV, 0, $this->blockSize);
        }
        
        return $outputByte;
    }
}
