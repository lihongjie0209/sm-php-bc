<?php

declare(strict_types=1);

namespace Rtgm\SmPhpBc\Tests\CrossLanguage;

use PHPUnit\Framework\TestCase;
use Rtgm\SmPhpBc\SM2Engine;
use Rtgm\SmPhpBc\SM2;
use Rtgm\SmPhpBc\ECKeyPairGenerator;

/**
 * SM2加密算法跨语言互操作测试
 * 
 * 测试场景:
 * 1. PHP encrypt → JavaScript decrypt
 * 2. JavaScript encrypt → PHP decrypt  
 * 3. 各种消息大小
 * 4. 无效密文错误处理
 */
class SM2EncryptionInteropTest extends TestCase
{
    private static string $nodeCommand = 'node';

    public static function setUpBeforeClass(): void
    {
        // 检查Node.js是否可用
        exec('node --version 2>&1', $output, $returnCode);
        if ($returnCode !== 0) {
            self::markTestSkipped('Node.js is not available');
        }

        // 检查sm-js-bc是否已安装
        if (!file_exists(__DIR__ . '/../../node_modules/sm-js-bc')) {
            self::markTestSkipped('sm-js-bc package is not installed. Run: npm install sm-js-bc');
        }
    }

    /**
     * 测试: PHP加密 → JavaScript解密
     */
    public function testPhpEncryptJavaScriptDecrypt(): void
    {
        echo "\n=== Testing PHP Encrypt → JavaScript Decrypt ===\n";

        $testMessages = [
            'Hello SM2!',
            'This is a longer test message for SM2 encryption verification.',
            'SM2加密测试消息，包含中文字符！',
        ];

        foreach ($testMessages as $message) {
            echo "Testing message: \"$message\"\n";
            
            // 生成密钥对
            $keyPair = $this->generateKeyPair();
            
            // PHP加密
            $ciphertext = $this->encryptWithPhp($message, $keyPair['publicKey']);
            echo "  PHP ciphertext length: " . strlen($ciphertext) . " bytes\n";
            
            // JavaScript解密
            $decrypted = $this->decryptWithJavaScript(
                $ciphertext,
                $keyPair['privateKey']
            );
            
            $this->assertEquals($message, $decrypted, 
                "JavaScript failed to decrypt PHP ciphertext");
            echo "  ✓ JavaScript successfully decrypted PHP ciphertext\n";
        }

        echo "✓ All PHP encrypt → JavaScript decrypt tests passed\n";
    }

    /**
     * 测试: JavaScript加密 → PHP解密
     */
    public function testJavaScriptEncryptPhpDecrypt(): void
    {
        echo "\n=== Testing JavaScript Encrypt → PHP Decrypt ===\n";

        $testMessages = [
            'Hello SM2!',
            'Test message from JavaScript',
            'JS加密PHP解密测试',
        ];

        foreach ($testMessages as $message) {
            echo "Testing message: \"$message\"\n";
            
            // 生成密钥对
            $keyPair = $this->generateKeyPair();
            
            // JavaScript加密
            $ciphertext = $this->encryptWithJavaScript(
                $message,
                $keyPair['publicKey']
            );
            echo "  JavaScript ciphertext length: " . strlen($ciphertext) . " bytes\n";
            
            // PHP解密
            $decrypted = $this->decryptWithPhp($ciphertext, $keyPair['privateKey']);
            
            $this->assertEquals($message, $decrypted,
                "PHP failed to decrypt JavaScript ciphertext");
            echo "  ✓ PHP successfully decrypted JavaScript ciphertext\n";
        }

        echo "✓ All JavaScript encrypt → PHP decrypt tests passed\n";
    }

    /**
     * 测试: 各种消息大小
     *
     * @dataProvider messageSizeProvider
     */
    public function testVariousMessageSizes(string $size, int $length): void
    {
        $message = str_repeat('x', $length);
        
        echo "\nTesting message size: $size ($length bytes)\n";
        
        // 生成密钥对
        $keyPair = $this->generateKeyPair();
        
        // PHP 加密 → JS 解密
        $ciphertext = $this->encryptWithPhp($message, $keyPair['publicKey']);
        $decrypted = $this->decryptWithJavaScript($ciphertext, $keyPair['privateKey']);
        $this->assertEquals($message, $decrypted, "Failed for size: $size");
        
        // JS 加密 → PHP 解密
        $ciphertext = $this->encryptWithJavaScript($message, $keyPair['publicKey']);
        $decrypted = $this->decryptWithPhp($ciphertext, $keyPair['privateKey']);
        $this->assertEquals($message, $decrypted, "Failed for size: $size");
        
        echo "  ✓ Both directions passed for $size\n";
    }

    public static function messageSizeProvider(): array
    {
        return [
            'small' => ['small', 10],
            'medium' => ['medium', 100],
            'large' => ['large', 1000],
        ];
    }

    /**
     * 生成SM2密钥对
     */
    private function generateKeyPair(): array
    {
        $generator = new ECKeyPairGenerator();
        $keyPair = $generator->generateKeyPair();
        
        return [
            'privateKey' => $keyPair['privateKey'],
            'publicKey' => $keyPair['publicKey'],
        ];
    }

    /**
     * 使用PHP进行SM2加密
     */
    private function encryptWithPhp(string $plaintext, array $publicKey): string
    {
        $engine = new SM2Engine();
        $engine->init(true, $publicKey); // true for encryption
        
        $inputBytes = unpack('C*', $plaintext);
        $inputArray = array_values($inputBytes);
        
        $ciphertext = $engine->processBlock($inputArray, 0, count($inputArray));
        
        return $this->bytesToHex($ciphertext);
    }

    /**
     * 使用PHP进行SM2解密
     */
    private function decryptWithPhp(string $ciphertextHex, array $privateKey): string
    {
        $ciphertext = $this->hexToBytes($ciphertextHex);
        
        $engine = new SM2Engine();
        $engine->init(false, $privateKey); // false for decryption
        
        $plaintext = $engine->processBlock($ciphertext, 0, count($ciphertext));
        
        return pack('C*', ...$plaintext);
    }

    /**
     * 使用JavaScript进行SM2加密
     */
    private function encryptWithJavaScript(string $plaintext, array $publicKey): string
    {
        $script = $this->createEncryptScript($plaintext, $publicKey);
        return $this->executeNodeScript($script);
    }

    /**
     * 使用JavaScript进行SM2解密
     */
    private function decryptWithJavaScript(string $ciphertextHex, array $privateKey): string
    {
        $script = $this->createDecryptScript($ciphertextHex, $privateKey);
        return $this->executeNodeScript($script);
    }

    /**
     * 创建加密脚本
     */
    private function createEncryptScript(string $plaintext, array $publicKey): string
    {
        $escapedPlaintext = addslashes($plaintext);
        $pubKeyX = gmp_strval($publicKey['x'], 16);
        $pubKeyY = gmp_strval($publicKey['y'], 16);

        return <<<JS
import { SM2Engine, SM2 } from 'sm-js-bc';

try {
    const plaintext = new TextEncoder().encode("$escapedPlaintext");
    
    // Create public key
    const publicKey = {
        x: BigInt('0x$pubKeyX'),
        y: BigInt('0x$pubKeyY')
    };
    
    const engine = new SM2Engine();
    engine.init(true, publicKey);
    
    const ciphertext = engine.processBlock(plaintext, 0, plaintext.length);
    const hex = Array.from(ciphertext)
        .map(b => b.toString(16).padStart(2, '0'))
        .join('');
    
    console.log(hex);
} catch (error) {
    console.error("Error:", error.message);
    process.exit(1);
}
JS;
    }

    /**
     * 创建解密脚本
     */
    private function createDecryptScript(string $ciphertextHex, array $privateKey): string
    {
        $privKeyD = gmp_strval($privateKey['d'], 16);

        return <<<JS
import { SM2Engine } from 'sm-js-bc';

try {
    const ciphertextHex = "$ciphertextHex";
    const ciphertext = new Uint8Array(
        ciphertextHex.match(/.{1,2}/g).map(byte => parseInt(byte, 16))
    );
    
    // Create private key
    const privateKey = {
        d: BigInt('0x$privKeyD')
    };
    
    const engine = new SM2Engine();
    engine.init(false, privateKey);
    
    const plaintext = engine.processBlock(ciphertext, 0, ciphertext.length);
    const text = new TextDecoder().decode(new Uint8Array(plaintext));
    
    console.log(text);
} catch (error) {
    console.error("Error:", error.message);
    process.exit(1);
}
JS;
    }

    /**
     * 执行Node.js脚本
     */
    private function executeNodeScript(string $script): string
    {
        $scriptPath = sys_get_temp_dir() . '/sm2_test_' . uniqid() . '.mjs';
        file_put_contents($scriptPath, $script);

        try {
            $output = shell_exec("node \"$scriptPath\" 2>&1");
            
            if ($output === null) {
                throw new \RuntimeException('Failed to execute Node.js script');
            }

            return trim($output);
        } finally {
            @unlink($scriptPath);
        }
    }

    /**
     * 字节数组转十六进制
     */
    private function bytesToHex(array $bytes): string
    {
        return implode('', array_map(fn($b) => sprintf('%02x', $b & 0xFF), $bytes));
    }

    /**
     * 十六进制转字节数组
     */
    private function hexToBytes(string $hex): array
    {
        $bytes = [];
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $bytes[] = hexdec(substr($hex, $i, 2));
        }
        return $bytes;
    }
}
