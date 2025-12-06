<?php

namespace SmBc\Crypto\Modes;

use SmBc\Crypto\BlockCipher;
use SmBc\Crypto\Modes\GCM\GCMUtil;
use SmBc\Crypto\Params\AEADParameters;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;
use SmBc\Util\Pack;

/**
 * Implements Galois/Counter Mode (GCM) as detailed in NIST Special Publication 800-38D.
 * 
 * GCM is an AEAD (Authenticated Encryption with Associated Data) mode that provides:
 * - Confidentiality (encryption)
 * - Authenticity (authentication tag)
 * - Optional additional authenticated data (AAD) - data that is authenticated but not encrypted
 * 
 * Key features:
 * - Based on CTR mode for encryption
 * - Uses Galois field multiplication for authentication
 * - Produces an authentication tag to verify integrity
 * - Supports variable-length nonces (12 bytes recommended)
 * - Supports variable-length authentication tags (96-128 bits recommended)
 * 
 * @see BouncyCastle GCMBlockCipher.java
 * @see NIST SP 800-38D
 */
class GCMBlockCipher implements BlockCipher
{
    private const BLOCK_SIZE = 16;
    
    private BlockCipher $cipher;
    
    // Initialization state
    private bool $forEncryption = false;
    private bool $initialised = false;
    private int $macSize = 16;
    private ?string $nonce = null;
    private ?string $associatedText = null;
    
    // GCM state
    private string $H;           // Hash subkey (E(K, 0^128))
    private string $J0;          // Initial counter block
    private string $counter;     // Current counter
    private string $S;           // Authentication state
    private string $S_at;        // AAD authentication state
    
    // Buffering
    private string $bufBlock;
    private int $bufOff = 0;
    private int $totalLength = 0;
    
    // AAD processing
    private string $atBlock;
    private int $atBlockPos = 0;
    private int $atLength = 0;
    
    // Final state
    private ?string $macBlock = null;
    private string $ciphertextBuffer = '';
    
    /**
     * Create a GCM mode cipher.
     *
     * @param BlockCipher $cipher The block cipher (must have 16-byte block size)
     * @throws \Exception If cipher doesn't have 16-byte block size
     */
    public function __construct(BlockCipher $cipher)
    {
        if ($cipher->getBlockSize() !== self::BLOCK_SIZE) {
            throw new \Exception('cipher required with a block size of ' . self::BLOCK_SIZE);
        }
        
        $this->cipher = $cipher;
        $this->H = str_repeat("\x00", self::BLOCK_SIZE);
        $this->J0 = str_repeat("\x00", self::BLOCK_SIZE);
        $this->counter = str_repeat("\x00", self::BLOCK_SIZE);
        $this->S = str_repeat("\x00", self::BLOCK_SIZE);
        $this->S_at = str_repeat("\x00", self::BLOCK_SIZE);
        $this->bufBlock = str_repeat("\x00", self::BLOCK_SIZE);
        $this->atBlock = str_repeat("\x00", self::BLOCK_SIZE);
    }
    
    /**
     * Get the underlying cipher.
     *
     * @return BlockCipher
     */
    public function getUnderlyingCipher(): BlockCipher
    {
        return $this->cipher;
    }
    
    /**
     * Get the algorithm name.
     *
     * @return string
     */
    public function getAlgorithmName(): string
    {
        return $this->cipher->getAlgorithmName() . '/GCM';
    }
    
    /**
     * Get the block size (always 16 bytes for GCM).
     *
     * @return int
     */
    public function getBlockSize(): int
    {
        return self::BLOCK_SIZE;
    }
    
    /**
     * Initialize the cipher.
     *
     * @param bool $forEncryption True for encryption, false for decryption
     * @param AEADParameters|ParametersWithIV $params Cipher parameters
     * @throws \Exception If parameters are invalid
     */
    public function init(bool $forEncryption, $params): void
    {
        $this->forEncryption = $forEncryption;
        $this->macBlock = null;
        $this->initialised = true;
        
        if ($params instanceof AEADParameters) {
            $newNonce = $params->getNonce();
            $this->associatedText = $params->getAssociatedText();
            
            $macSizeBits = $params->getMacSize();
            if ($macSizeBits < 32 || $macSizeBits > 128 || $macSizeBits % 8 !== 0) {
                throw new \Exception("Invalid value for MAC size: {$macSizeBits}");
            }
            
            $this->macSize = intdiv($macSizeBits, 8);
            $keyParam = $params->getKey();
        } elseif ($params instanceof ParametersWithIV) {
            $newNonce = $params->getIV();
            $this->associatedText = null;
            $this->macSize = 16;
            $keyParam = $params->getParameters();
        } else {
            throw new \Exception('invalid parameters passed to GCM');
        }
        
        $bufLength = $forEncryption 
            ? self::BLOCK_SIZE 
            : self::BLOCK_SIZE + $this->macSize;
        $this->bufBlock = str_repeat("\x00", $bufLength);
        
        if (!$newNonce || strlen($newNonce) < 1) {
            throw new \Exception('IV must be at least 1 byte');
        }
        
        $this->nonce = $newNonce;
        
        // Initialize cipher and compute H = E(K, 0)
        $this->cipher->init(true, $keyParam);
        $this->H = str_repeat("\x00", self::BLOCK_SIZE);
        $this->cipher->processBlock($this->H, 0, $this->H, 0);
        
        // Compute J0 from nonce
        $this->J0 = str_repeat("\x00", self::BLOCK_SIZE);
        if (strlen($newNonce) === 12) {
            // Standard case: 96-bit nonce
            $this->J0 = $newNonce . "\x00\x00\x00\x01";
        } else {
            // Non-standard: hash the nonce
            $this->gHash($this->J0, $newNonce);
            $lenBlock = str_repeat("\x00", 16);
            Pack::longToBigEndian(strlen($newNonce) * 8, $lenBlock, 8);
            $this->gHashBlock($this->J0, $lenBlock);
        }
        
        // Initialize state
        $this->S = str_repeat("\x00", self::BLOCK_SIZE);
        $this->S_at = str_repeat("\x00", self::BLOCK_SIZE);
        $this->atBlock = str_repeat("\x00", self::BLOCK_SIZE);
        $this->atBlockPos = 0;
        $this->atLength = 0;
        $this->counter = $this->J0;
        $this->bufOff = 0;
        $this->totalLength = 0;
        
        // Process AAD if provided
        if ($this->associatedText !== null) {
            $this->processAADBytes($this->associatedText, 0, strlen($this->associatedText));
        }
    }
    
    /**
     * Process additional authenticated data (AAD).
     *
     * @param string $aad Additional authenticated data
     * @param int $offset Offset in AAD
     * @param int $length Length of AAD to process
     */
    public function processAADBytes(string $aad, int $offset, int $length): void
    {
        $this->checkStatus();
        
        $inOff = $offset;
        $len = $length;
        
        // Fill partial block
        if ($this->atBlockPos > 0) {
            $available = self::BLOCK_SIZE - $this->atBlockPos;
            if ($len < $available) {
                for ($i = 0; $i < $len; $i++) {
                    $this->atBlock[$this->atBlockPos + $i] = $aad[$inOff + $i];
                }
                $this->atBlockPos += $len;
                return;
            }
            
            for ($i = 0; $i < $available; $i++) {
                $this->atBlock[$this->atBlockPos + $i] = $aad[$inOff + $i];
            }
            $this->gHashBlock($this->S_at, $this->atBlock);
            $this->atLength += self::BLOCK_SIZE;
            $inOff += $available;
            $len -= $available;
            $this->atBlockPos = 0;
        }
        
        // Process complete blocks
        while ($len >= self::BLOCK_SIZE) {
            $block = substr($aad, $inOff, self::BLOCK_SIZE);
            $this->gHashBlock($this->S_at, $block);
            $this->atLength += self::BLOCK_SIZE;
            $inOff += self::BLOCK_SIZE;
            $len -= self::BLOCK_SIZE;
        }
        
        // Buffer remaining bytes
        if ($len > 0) {
            $this->atBlock = substr($aad, $inOff, $len) . str_repeat("\x00", self::BLOCK_SIZE - $len);
            $this->atBlockPos = $len;
        }
    }
    
    /**
     * Process a block of data.
     *
     * @throws \Exception Always throws - not supported for GCM mode
     */
    public function processBlock(string $input, int $inOff, string &$output, int $outOff): int
    {
        throw new \Exception('processBlock not supported for GCM mode (use processBytes and doFinal)');
    }
    
    /**
     * Process bytes of data.
     *
     * @param string $input Input data
     * @param int $inOff Offset in input
     * @param int $len Length to process
     * @param string $output Output buffer
     * @param int $outOff Offset in output
     * @return int Number of bytes written to output
     * @throws DataLengthException If input buffer is too short
     */
    public function processBytes(string $input, int $inOff, int $len, string &$output, int $outOff): int
    {
        $this->checkStatus();
        
        if ($inOff + $len > strlen($input)) {
            throw new \RuntimeException('Input buffer too short');
        }
        
        $resultLen = 0;
        
        if ($this->forEncryption) {
            // Encryption mode: process blocks immediately
            $resultLen = $this->encryptBytes($input, $inOff, $len, $output, $outOff);
        } else {
            // Decryption mode: buffer all data for MAC verification in doFinal
            $this->ciphertextBuffer .= substr($input, $inOff, $len);
            $resultLen = 0;
        }
        
        return $resultLen;
    }
    
    /**
     * Complete processing and generate/verify authentication tag.
     *
     * @param string $output Output buffer
     * @param int $outOff Offset in output
     * @return int Number of bytes written
     * @throws InvalidCipherTextException If MAC verification fails
     */
    public function doFinal(string &$output, int $outOff): int
    {
        $this->checkStatus();
        
        if ($this->forEncryption) {
            return $this->encryptDoFinal($output, $outOff);
        } else {
            return $this->decryptDoFinal($output, $outOff);
        }
    }
    
    /**
     * Reset the cipher to initial state.
     */
    public function reset(): void
    {
        $this->S = str_repeat("\x00", self::BLOCK_SIZE);
        $this->S_at = str_repeat("\x00", self::BLOCK_SIZE);
        $this->atBlock = str_repeat("\x00", self::BLOCK_SIZE);
        $this->atBlockPos = 0;
        $this->atLength = 0;
        
        if ($this->J0 !== null) {
            $this->counter = $this->J0;
        }
        
        $this->bufOff = 0;
        $this->totalLength = 0;
        $this->macBlock = null;
        $this->ciphertextBuffer = '';
        
        if ($this->associatedText !== null) {
            $this->processAADBytes($this->associatedText, 0, strlen($this->associatedText));
        }
        
        $this->cipher->reset();
    }
    
    /**
     * Get the authentication tag (MAC).
     *
     * @return string MAC bytes
     */
    public function getMac(): string
    {
        if ($this->macBlock === null) {
            return str_repeat("\x00", $this->macSize);
        }
        return $this->macBlock;
    }
    
    /**
     * Get the output size for the given input length.
     *
     * @param int $len Input length
     * @return int Output size
     */
    public function getOutputSize(int $len): int
    {
        $totalData = $len + $this->bufOff;
        
        if ($this->forEncryption) {
            return $totalData + $this->macSize;
        }
        
        return $totalData < $this->macSize ? 0 : $totalData - $this->macSize;
    }
    
    // Private helper methods
    
    private function checkStatus(): void
    {
        if (!$this->initialised) {
            throw new \Exception('GCM cipher not initialised');
        }
    }
    
    private function encryptBytes(string $input, int $inOff, int $len, string &$output, int $outOff): int
    {
        $processed = 0;
        
        for ($i = 0; $i < $len; $i++) {
            $this->bufBlock[$this->bufOff++] = $input[$inOff + $i];
            
            if ($this->bufOff === self::BLOCK_SIZE) {
                $this->encryptBlock($this->bufBlock, $output, $outOff + $processed);
                $processed += self::BLOCK_SIZE;
                $this->bufOff = 0;
            }
        }
        
        return $processed;
    }
    
    private function encryptBlock(string $block, string &$output, int $outOff): void
    {
        // Initialize cipher state if this is the first block
        if ($this->totalLength === 0) {
            $this->initCipher();
        }
        
        // Increment counter
        GCMUtil::increment($this->counter);
        
        // Encrypt counter
        $counterBlock = $this->counter;
        $this->cipher->processBlock($counterBlock, 0, $counterBlock, 0);
        
        // XOR with plaintext
        $ciphertext = '';
        for ($i = 0; $i < self::BLOCK_SIZE; $i++) {
            $ciphertext .= chr(ord($block[$i]) ^ ord($counterBlock[$i]));
        }
        
        // Update authentication hash with ciphertext
        $this->gHashBlock($this->S, $ciphertext);
        $this->totalLength += self::BLOCK_SIZE;
        
        // Output ciphertext
        for ($i = 0; $i < self::BLOCK_SIZE; $i++) {
            $output[$outOff + $i] = $ciphertext[$i];
        }
    }
    
    private function encryptDoFinal(string &$output, int $outOff): int
    {
        // Initialize cipher state if not done yet
        if ($this->totalLength === 0) {
            $this->initCipher();
        }
        
        $resultLen = 0;
        
        // Process any remaining bytes
        if ($this->bufOff > 0) {
            // Increment counter
            GCMUtil::increment($this->counter);
            
            // Encrypt counter
            $counterBlock = $this->counter;
            $this->cipher->processBlock($counterBlock, 0, $counterBlock, 0);
            
            // XOR with plaintext (partial block)
            $ciphertext = '';
            for ($i = 0; $i < $this->bufOff; $i++) {
                $ciphertext .= chr(ord($this->bufBlock[$i]) ^ ord($counterBlock[$i]));
            }
            
            // Update authentication hash (pad to block size)
            $paddedCiphertext = $ciphertext . str_repeat("\x00", self::BLOCK_SIZE - $this->bufOff);
            $this->gHashBlock($this->S, $paddedCiphertext);
            $this->totalLength += $this->bufOff;
            
            // Output ciphertext
            for ($i = 0; $i < $this->bufOff; $i++) {
                $output[$outOff + $i] = $ciphertext[$i];
            }
            $resultLen = $this->bufOff;
        }
        
        // Hash the lengths
        $lenBlock = str_repeat("\x00", self::BLOCK_SIZE);
        Pack::longToBigEndian($this->atLength * 8, $lenBlock, 0);
        Pack::longToBigEndian($this->totalLength * 8, $lenBlock, 8);
        $this->gHashBlock($this->S, $lenBlock);
        
        // Compute tag: T = GCTR_K(J0, S)
        $tag = $this->J0;
        $this->cipher->processBlock($tag, 0, $tag, 0);
        GCMUtil::xor($tag, $this->S);
        
        // Output tag (truncated to macSize)
        $this->macBlock = substr($tag, 0, $this->macSize);
        for ($i = 0; $i < $this->macSize; $i++) {
            $output[$outOff + $resultLen + $i] = $this->macBlock[$i];
        }
        
        $resultLen += $this->macSize;
        $this->reset();
        
        return $resultLen;
    }
    
    private function initCipher(): void
    {
        // Finalize AAD processing
        if ($this->atBlockPos > 0) {
            $this->gHashBlock($this->S_at, $this->atBlock);
            $this->atLength += $this->atBlockPos;
        }
        
        // Initialize S with AAD hash
        if ($this->atLength > 0) {
            $this->S = $this->S_at;
        }
    }
    
    private function decryptDoFinal(string &$output, int $outOff): int
    {
        $bufferLen = strlen($this->ciphertextBuffer);
        
        if ($bufferLen < $this->macSize) {
            throw new \RuntimeException('data too short');
        }
        
        // Initialize cipher state if not done yet
        if ($this->totalLength === 0) {
            $this->initCipher();
        }
        
        $dataLen = $bufferLen - $this->macSize;
        
        // First, hash all ciphertext blocks for MAC computation
        $pos = 0;
        while ($pos + self::BLOCK_SIZE <= $dataLen) {
            $block = substr($this->ciphertextBuffer, $pos, self::BLOCK_SIZE);
            $this->gHashBlock($this->S, $block);
            $this->totalLength += self::BLOCK_SIZE;
            $pos += self::BLOCK_SIZE;
        }
        
        // Hash any remaining partial block
        if ($pos < $dataLen) {
            $paddedBlock = substr($this->ciphertextBuffer, $pos, $dataLen - $pos);
            $paddedBlock .= str_repeat("\x00", self::BLOCK_SIZE - strlen($paddedBlock));
            $this->gHashBlock($this->S, $paddedBlock);
            $this->totalLength += ($dataLen - $pos);
        }
        
        // Extract the received MAC/tag
        $receivedTag = substr($this->ciphertextBuffer, $dataLen, $this->macSize);
        
        // Hash the lengths
        $lenBlock = str_repeat("\x00", self::BLOCK_SIZE);
        Pack::longToBigEndian($this->atLength * 8, $lenBlock, 0);
        Pack::longToBigEndian($this->totalLength * 8, $lenBlock, 8);
        $this->gHashBlock($this->S, $lenBlock);
        
        // Compute expected tag
        $expectedTag = $this->J0;
        $this->cipher->processBlock($expectedTag, 0, $expectedTag, 0);
        GCMUtil::xor($expectedTag, $this->S);
        $expectedTag = substr($expectedTag, 0, $this->macSize);
        
        // Verify tag (constant-time comparison)
        if (!hash_equals($expectedTag, $receivedTag)) {
            throw new \RuntimeException('mac check in GCM failed');
        }
        
        // MAC verified! Now decrypt all data
        $pos = 0;
        while ($pos + self::BLOCK_SIZE <= $dataLen) {
            GCMUtil::increment($this->counter);
            $counterBlock = $this->counter;
            $this->cipher->processBlock($counterBlock, 0, $counterBlock, 0);
            
            for ($i = 0; $i < self::BLOCK_SIZE; $i++) {
                $output[$outOff + $pos + $i] = chr(
                    ord($this->ciphertextBuffer[$pos + $i]) ^ ord($counterBlock[$i])
                );
            }
            $pos += self::BLOCK_SIZE;
        }
        
        // Decrypt any remaining partial block
        if ($pos < $dataLen) {
            GCMUtil::increment($this->counter);
            $counterBlock = $this->counter;
            $this->cipher->processBlock($counterBlock, 0, $counterBlock, 0);
            
            for ($i = 0; $i < $dataLen - $pos; $i++) {
                $output[$outOff + $pos + $i] = chr(
                    ord($this->ciphertextBuffer[$pos + $i]) ^ ord($counterBlock[$i])
                );
            }
        }
        
        $this->macBlock = $receivedTag;
        $this->reset();
        
        return $dataLen;
    }
    
    /**
     * GHASH function: multiply and XOR in Galois field.
     */
    private function gHashBlock(string &$Y, string $X): void
    {
        GCMUtil::xor($Y, $X);
        $Y = GCMUtil::multiply($Y, $this->H);
    }
    
    /**
     * GHASH over multiple blocks.
     */
    private function gHash(string &$Y, string $data): void
    {
        $pos = 0;
        $len = strlen($data);
        
        while ($pos + self::BLOCK_SIZE <= $len) {
            $block = substr($data, $pos, self::BLOCK_SIZE);
            $this->gHashBlock($Y, $block);
            $pos += self::BLOCK_SIZE;
        }
        
        if ($pos < $len) {
            $paddedBlock = substr($data, $pos) . str_repeat("\x00", self::BLOCK_SIZE - ($len - $pos));
            $this->gHashBlock($Y, $paddedBlock);
        }
    }
}
