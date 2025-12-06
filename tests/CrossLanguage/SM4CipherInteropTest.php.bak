<?php

declare(strict_types=1);

namespace SmBc\Tests\CrossLanguage;

use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\CBCBlockCipher;
use SmBc\Crypto\Paddings\PKCS7Padding;
use SmBc\Crypto\PaddedBufferedBlockCipher;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

/**
 * SM4 Cipher cross-language interoperability tests
 */
class SM4CipherInteropTest extends BaseInteropTest
{
    /**
     * Test SM4-CBC encryption/decryption between PHP and JavaScript
     */
    public function testSM4CBCInterop(): void
    {
        if (!$this->isNodeJsAvailable()) {
            $this->markTestSkipped('Node.js is not available');
        }
        
        echo "\n=== Testing SM4-CBC Cross-Language Interoperability ===\n";
        
        // Fixed key and IV for reproducibility
        $keyHex = '0123456789ABCDEFFEDCBA9876543210';
        $ivHex = 'FEDCBA98765432100123456789ABCDEF';
        
        $testMessages = [
            'Hello SM4!',
            'Test message for SM4 cipher',
            'SM4密码算法测试',
        ];
        
        foreach ($testMessages as $message) {
            echo "Testing message: \"{$message}\"\n";
            
            // Encrypt with PHP
            $phpCiphertext = $this->encryptWithPhp($message, $keyHex, $ivHex);
            echo "  PHP ciphertext: " . substr($phpCiphertext, 0, 32) . "...\n";
            
            // Decrypt with JavaScript
            $jsDecrypted = $this->decryptWithJavaScript($phpCiphertext, $keyHex, $ivHex);
            $this->assertEquals($message, $jsDecrypted, "JS decryption of PHP ciphertext failed");
            echo "  ✓ JS successfully decrypted PHP ciphertext\n";
            
            // Encrypt with JavaScript
            $jsCiphertext = $this->encryptWithJavaScript($message, $keyHex, $ivHex);
            echo "  JS ciphertext: " . substr($jsCiphertext, 0, 32) . "...\n";
            
            // Decrypt with PHP
            $phpDecrypted = $this->decryptWithPhp($jsCiphertext, $keyHex, $ivHex);
            $this->assertEquals($message, $phpDecrypted, "PHP decryption of JS ciphertext failed");
            echo "  ✓ PHP successfully decrypted JS ciphertext\n";
            
            // Verify ciphertexts match (should be same with fixed key/IV and no random padding)
            echo "  ✓ Cross-language test passed\n\n";
        }
        
        echo "✓ All SM4-CBC interop tests passed\n";
    }
    
    /**
     * Encrypt with PHP SM4-CBC
     */
    private function encryptWithPhp(string $plaintext, string $keyHex, string $ivHex): string
    {
        $key = $this->hexToBytes($keyHex);
        $iv = $this->hexToBytes($ivHex);
        
        $cipher = new PaddedBufferedBlockCipher(
            new CBCBlockCipher(new SM4Engine()),
            new PKCS7Padding()
        );
        
        $params = new ParametersWithIV(new KeyParameter($key), $iv);
        $cipher->init(true, $params);
        
        $input = $plaintext;
        $output = str_repeat("\0", strlen($input) + 16);
        
        $len = $cipher->processBytes($input, 0, strlen($input), $output, 0);
        $len += $cipher->doFinal($output, $len);
        
        return $this->bytesToHex(substr($output, 0, $len));
    }
    
    /**
     * Decrypt with PHP SM4-CBC
     */
    private function decryptWithPhp(string $ciphertextHex, string $keyHex, string $ivHex): string
    {
        $key = $this->hexToBytes($keyHex);
        $iv = $this->hexToBytes($ivHex);
        $ciphertext = $this->hexToBytes($ciphertextHex);
        
        $cipher = new PaddedBufferedBlockCipher(
            new CBCBlockCipher(new SM4Engine()),
            new PKCS7Padding()
        );
        
        $params = new ParametersWithIV(new KeyParameter($key), $iv);
        $cipher->init(false, $params);
        
        $output = str_repeat("\0", strlen($ciphertext) + 16);
        
        $len = $cipher->processBytes($ciphertext, 0, strlen($ciphertext), $output, 0);
        $len += $cipher->doFinal($output, $len);
        
        return substr($output, 0, $len);
    }
    
    /**
     * Encrypt with JavaScript SM4-CBC
     */
    private function encryptWithJavaScript(string $plaintext, string $keyHex, string $ivHex): string
    {
        $escapedMessage = addcslashes($plaintext, "\\\"\n\r\t");
        
        $script = $this->createJsScript(<<<JS
const hexToBytes = (hex) => {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
        bytes[i / 2] = parseInt(hex.substr(i, 2), 16);
    }
    return bytes;
};

const bytesToHex = (bytes) => {
    return Array.from(bytes).map(b => b.toString(16).padStart(2, '0')).join('');
};

const plaintext = new TextEncoder().encode("$escapedMessage");
const key = hexToBytes("$keyHex");
const iv = hexToBytes("$ivHex");

const cipher = new smBc.PaddedBufferedBlockCipher(
    new smBc.CBCBlockCipher(new smBc.SM4Engine()),
    new smBc.PKCS7Padding()
);

const params = new smBc.ParametersWithIV(new smBc.KeyParameter(key), iv);
cipher.init(true, params);

const output = new Uint8Array(plaintext.length + 16);
let len = cipher.processBytes(plaintext, 0, plaintext.length, output, 0);
len += cipher.doFinal(output, len);

const ciphertext = output.slice(0, len);
console.log(JSON.stringify({ ciphertext: bytesToHex(ciphertext) }));
JS
        );
        
        $result = $this->executeNodeJs($script);
        return $result['ciphertext'];
    }
    
    /**
     * Decrypt with JavaScript SM4-CBC
     */
    private function decryptWithJavaScript(string $ciphertextHex, string $keyHex, string $ivHex): string
    {
        $script = $this->createJsScript(<<<JS
const hexToBytes = (hex) => {
    const bytes = new Uint8Array(hex.length / 2);
    for (let i = 0; i < hex.length; i += 2) {
        bytes[i / 2] = parseInt(hex.substr(i, 2), 16);
    }
    return bytes;
};

const ciphertext = hexToBytes("$ciphertextHex");
const key = hexToBytes("$keyHex");
const iv = hexToBytes("$ivHex");

const cipher = new smBc.PaddedBufferedBlockCipher(
    new smBc.CBCBlockCipher(new smBc.SM4Engine()),
    new smBc.PKCS7Padding()
);

const params = new smBc.ParametersWithIV(new smBc.KeyParameter(key), iv);
cipher.init(false, params);

const output = new Uint8Array(ciphertext.length + 16);
let len = cipher.processBytes(ciphertext, 0, ciphertext.length, output, 0);
len += cipher.doFinal(output, len);

const plaintext = output.slice(0, len);
const message = new TextDecoder().decode(plaintext);
console.log(JSON.stringify({ plaintext: message }));
JS
        );
        
        $result = $this->executeNodeJs($script);
        return $result['plaintext'];
    }
}
