<?php

namespace SmBc\Tests;

use PHPUnit\Framework\TestCase;
use SmBc\Crypto\Digests\SM3Digest;
use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\ECBBlockCipher;
use SmBc\Crypto\Modes\CBCBlockCipher;
use SmBc\Crypto\Modes\CTRBlockCipher;
use SmBc\Crypto\Modes\GCMBlockCipher;
use SmBc\Crypto\Paddings\PKCS7Padding;
use SmBc\Crypto\PaddedBufferedBlockCipher;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;
use SmBc\Crypto\Params\AEADParameters;

/**
 * 跨语言兼容性测试
 * 测试 PHP 实现与 JavaScript (sm-js-bc) 实现的互操作性
 * 
 * 参考 sm-js-bc 的 Java 跨语言测试实现
 */
class CrossLanguageTest extends TestCase
{
    private static string $nodeCommand;
    private static bool $nodeAvailable = false;
    
    public static function setUpBeforeClass(): void
    {
        // 检查 Node.js 是否可用
        $output = [];
        $returnVar = 0;
        exec('node --version 2>&1', $output, $returnVar);
        self::$nodeAvailable = ($returnVar === 0);
        self::$nodeCommand = 'node';
        
        if (!self::$nodeAvailable) {
            self::markTestSkipped('Node.js not available. Skipping cross-language tests.');
        }
    }
    
    /**
     * 测试 SM3 跨语言实现一致性
     */
    public function testSM3CrossImplementation(): void
    {
        $testMessages = [
            '',  // 空字符串
            'a', // 单字符
            'abc', // 标准测试
            'Hello SM3!', // 常规消息
            'SM3哈希算法测试消息' // 中文字符
        ];
        
        foreach ($testMessages as $message) {
            echo "\nTesting message: \"$message\"\n";
            
            // PHP 计算哈希
            $phpHash = $this->computePhpSM3($message);
            
            // JavaScript 计算哈希
            $jsHash = $this->computeJavaScriptSM3($message);
            
            $this->assertEquals($phpHash, $jsHash, 
                "PHP and JavaScript SM3 results don't match for message: $message");
            
            echo "  ✓ Both implementations agree: $phpHash\n";
        }
        
        echo "✓ All SM3 cross-implementation tests passed\n";
    }
    
    /**
     * 测试 SM3 标准测试向量
     */
    public function testSM3StandardVectors(): void
    {
        $vectors = [
            ['input' => '', 'expected' => '1ab21d8355cfa17f8e61194831e81a8f22bec8c728fefb747ed035eb5082aa2b'],
            ['input' => 'a', 'expected' => '623476ac18f65a2909e43c7fec61b49c7e764a91a18ccb82f1917a29c86c5e88'],
            ['input' => 'abc', 'expected' => '66c7f0f462eeedd9d1f2d46bdc10e4e24167c4875cf2f7a2297da02b8f4ba8e0']
        ];
        
        foreach ($vectors as $i => $tv) {
            $vectorNum = $i + 1;
            echo "\nTest vector $vectorNum: \"{$tv['input']}\"\n";
            
            // 测试 PHP
            $phpResult = $this->computePhpSM3($tv['input']);
            $this->assertEquals($tv['expected'], $phpResult, 
                "PHP SM3 failed for test vector $vectorNum");
            
            // 测试 JavaScript
            $jsResult = $this->computeJavaScriptSM3($tv['input']);
            $this->assertEquals($tv['expected'], $jsResult, 
                "JavaScript SM3 failed for test vector $vectorNum");
            
            echo "  ✓ Both PHP and JavaScript match expected: {$tv['expected']}\n";
        }
        
        echo "✓ All standard test vectors passed\n";
    }
    
    /**
     * 测试 SM4-ECB 跨语言实现
     */
    public function testSM4_ECB_CrossLanguage(): void
    {
        $key = hex2bin('0123456789abcdeffedcba9876543210');
        $plaintext = 'Hello SM4 ECB mode!';
        
        echo "\n=== Testing SM4-ECB Cross-Language ===\n";
        echo "Plaintext: $plaintext\n";
        
        // PHP 加密
        $phpCiphertext = $this->encryptPhpSM4_ECB($plaintext, $key);
        
        // JavaScript 加密
        $jsCiphertext = $this->encryptJavaScriptSM4_ECB($plaintext, $key);
        
        // 验证密文一致
        $this->assertEquals(bin2hex($phpCiphertext), bin2hex($jsCiphertext),
            "SM4-ECB ciphertexts don't match");
        
        // PHP 解密 JavaScript 密文
        $phpDecrypted = $this->decryptPhpSM4_ECB($jsCiphertext, $key);
        $this->assertEquals($plaintext, $phpDecrypted,
            "PHP failed to decrypt JavaScript ciphertext");
        
        // JavaScript 解密 PHP 密文
        $jsDecrypted = $this->decryptJavaScriptSM4_ECB($phpCiphertext, $key);
        $this->assertEquals($plaintext, $jsDecrypted,
            "JavaScript failed to decrypt PHP ciphertext");
        
        echo "✓ SM4-ECB cross-language test passed\n";
    }
    
    /**
     * 测试 SM4-CBC 跨语言实现
     */
    public function testSM4_CBC_CrossLanguage(): void
    {
        $key = hex2bin('0123456789abcdeffedcba9876543210');
        $iv = hex2bin('0123456789abcdeffedcba9876543210');
        $plaintext = 'The quick brown fox jumps over the lazy dog';
        
        echo "\n=== Testing SM4-CBC Cross-Language ===\n";
        echo "Plaintext: $plaintext\n";
        
        // PHP 加密
        $phpCiphertext = $this->encryptPhpSM4_CBC($plaintext, $key, $iv);
        
        // JavaScript 加密
        $jsCiphertext = $this->encryptJavaScriptSM4_CBC($plaintext, $key, $iv);
        
        // 验证密文一致
        $this->assertEquals(bin2hex($phpCiphertext), bin2hex($jsCiphertext),
            "SM4-CBC ciphertexts don't match");
        
        // 交叉解密测试
        $phpDecrypted = $this->decryptPhpSM4_CBC($jsCiphertext, $key, $iv);
        $this->assertEquals($plaintext, $phpDecrypted,
            "PHP failed to decrypt JavaScript ciphertext");
        
        $jsDecrypted = $this->decryptJavaScriptSM4_CBC($phpCiphertext, $key, $iv);
        $this->assertEquals($plaintext, $jsDecrypted,
            "JavaScript failed to decrypt PHP ciphertext");
        
        echo "✓ SM4-CBC cross-language test passed\n";
    }
    
    /**
     * 测试 SM4-CTR 跨语言实现
     */
    public function testSM4_CTR_CrossLanguage(): void
    {
        $key = hex2bin('0123456789abcdeffedcba9876543210');
        $iv = hex2bin('0123456789abcdeffedcba9876543210');
        $plaintext = 'SM4 CTR mode test message';
        
        echo "\n=== Testing SM4-CTR Cross-Language ===\n";
        
        // PHP 加密
        $phpCiphertext = $this->encryptPhpSM4_CTR($plaintext, $key, $iv);
        
        // JavaScript 加密
        $jsCiphertext = $this->encryptJavaScriptSM4_CTR($plaintext, $key, $iv);
        
        // 验证密文一致
        $this->assertEquals(bin2hex($phpCiphertext), bin2hex($jsCiphertext),
            "SM4-CTR ciphertexts don't match");
        
        // CTR 模式是自逆的，使用相同操作解密
        $phpDecrypted = $this->encryptPhpSM4_CTR($phpCiphertext, $key, $iv);
        $this->assertEquals($plaintext, $phpDecrypted,
            "PHP CTR decryption failed");
        
        echo "✓ SM4-CTR cross-language test passed\n";
    }
    
    /**
     * 测试 SM4-GCM 跨语言实现 (AEAD)
     */
    public function testSM4_GCM_CrossLanguage(): void
    {
        $key = hex2bin('0123456789abcdeffedcba9876543210');
        $iv = random_bytes(12); // GCM 标准 IV 大小
        $plaintext = 'Authenticated encryption test';
        $aad = 'additional authenticated data';
        
        echo "\n=== Testing SM4-GCM Cross-Language ===\n";
        
        // PHP 加密
        $phpCiphertext = $this->encryptPhpSM4_GCM($plaintext, $key, $iv, $aad);
        
        // JavaScript 加密
        $jsCiphertext = $this->encryptJavaScriptSM4_GCM($plaintext, $key, $iv, $aad);
        
        // PHP 解密 PHP
        $phpDecrypted1 = $this->decryptPhpSM4_GCM($phpCiphertext, $key, $iv, $aad);
        $this->assertEquals($plaintext, $phpDecrypted1,
            "PHP can't decrypt its own ciphertext");
        
        // JavaScript 解密 JavaScript
        $jsDecrypted1 = $this->decryptJavaScriptSM4_GCM($jsCiphertext, $key, $iv, $aad);
        $this->assertEquals($plaintext, $jsDecrypted1,
            "JavaScript can't decrypt its own ciphertext");
        
        // 交叉解密测试
        $phpDecrypted2 = $this->decryptPhpSM4_GCM($jsCiphertext, $key, $iv, $aad);
        $this->assertEquals($plaintext, $phpDecrypted2,
            "PHP can't decrypt JavaScript ciphertext");
        
        $jsDecrypted2 = $this->decryptJavaScriptSM4_GCM($phpCiphertext, $key, $iv, $aad);
        $this->assertEquals($plaintext, $jsDecrypted2,
            "JavaScript can't decrypt PHP ciphertext");
        
        echo "✓ SM4-GCM cross-language test passed\n";
    }
    
    // ============================================
    // PHP 实现辅助方法
    // ============================================
    
    private function computePhpSM3(string $input): string
    {
        $digest = new SM3Digest();
        $inputBytes = $input;
        $digest->update($inputBytes, 0, strlen($inputBytes));
        
        $result = str_repeat("\0", $digest->getDigestSize());
        $digest->doFinal($result, 0);
        
        return bin2hex($result);
    }
    
    private function encryptPhpSM4_ECB(string $plaintext, string $key): string
    {
        $cipher = new PaddedBufferedBlockCipher(
            new ECBBlockCipher(new SM4Engine()),
            new PKCS7Padding()
        );
        $cipher->init(true, new KeyParameter($key));
        
        $output = str_repeat("\0", $cipher->getOutputSize(strlen($plaintext)));
        $len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $output, 0);
        $len += $cipher->doFinal($output, $len);
        
        return substr($output, 0, $len);
    }
    
    private function decryptPhpSM4_ECB(string $ciphertext, string $key): string
    {
        $cipher = new PaddedBufferedBlockCipher(
            new ECBBlockCipher(new SM4Engine()),
            new PKCS7Padding()
        );
        $cipher->init(false, new KeyParameter($key));
        
        $output = str_repeat("\0", $cipher->getOutputSize(strlen($ciphertext)));
        $len = $cipher->processBytes($ciphertext, 0, strlen($ciphertext), $output, 0);
        $len += $cipher->doFinal($output, $len);
        
        return substr($output, 0, $len);
    }
    
    private function encryptPhpSM4_CBC(string $plaintext, string $key, string $iv): string
    {
        $cipher = new PaddedBufferedBlockCipher(
            new CBCBlockCipher(new SM4Engine()),
            new PKCS7Padding()
        );
        $cipher->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
        
        $output = str_repeat("\0", $cipher->getOutputSize(strlen($plaintext)));
        $len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $output, 0);
        $len += $cipher->doFinal($output, $len);
        
        return substr($output, 0, $len);
    }
    
    private function decryptPhpSM4_CBC(string $ciphertext, string $key, string $iv): string
    {
        $cipher = new PaddedBufferedBlockCipher(
            new CBCBlockCipher(new SM4Engine()),
            new PKCS7Padding()
        );
        $cipher->init(false, new ParametersWithIV(new KeyParameter($key), $iv));
        
        $output = str_repeat("\0", $cipher->getOutputSize(strlen($ciphertext)));
        $len = $cipher->processBytes($ciphertext, 0, strlen($ciphertext), $output, 0);
        $len += $cipher->doFinal($output, $len);
        
        return substr($output, 0, $len);
    }
    
    private function encryptPhpSM4_CTR(string $plaintext, string $key, string $iv): string
    {
        $cipher = new CTRBlockCipher(new SM4Engine());
        $cipher->init(true, new ParametersWithIV(new KeyParameter($key), $iv));
        
        $output = str_repeat("\0", strlen($plaintext));
        $cipher->processBytes($plaintext, 0, strlen($plaintext), $output, 0);
        
        return $output;
    }
    
    private function encryptPhpSM4_GCM(string $plaintext, string $key, string $iv, string $aad): string
    {
        $cipher = new GCMBlockCipher(new SM4Engine());
        $params = new AEADParameters(new KeyParameter($key), 128, $iv, $aad);
        $cipher->init(true, $params);
        
        $output = str_repeat("\0", $cipher->getOutputSize(strlen($plaintext)));
        $len = $cipher->processBytes($plaintext, 0, strlen($plaintext), $output, 0);
        $len += $cipher->doFinal($output, $len);
        
        return substr($output, 0, $len);
    }
    
    private function decryptPhpSM4_GCM(string $ciphertext, string $key, string $iv, string $aad): string
    {
        $cipher = new GCMBlockCipher(new SM4Engine());
        $params = new AEADParameters(new KeyParameter($key), 128, $iv, $aad);
        $cipher->init(false, $params);
        
        $output = str_repeat("\0", $cipher->getOutputSize(strlen($ciphertext)));
        $len = $cipher->processBytes($ciphertext, 0, strlen($ciphertext), $output, 0);
        $len += $cipher->doFinal($output, $len);
        
        return substr($output, 0, $len);
    }
    
    // ============================================
    // JavaScript 实现辅助方法 (通过 Node.js)
    // ============================================
    
    private function executeNodeScript(string $script): array
    {
        $scriptFile = tempnam(sys_get_temp_dir(), 'sm_test_');
        file_put_contents($scriptFile, $script);
        
        // 从 sm-php-bc 目录运行，这样 node_modules 可以找到
        $oldDir = getcwd();
        chdir(__DIR__ . '/..');
        
        $output = [];
        $returnVar = 0;
        exec(self::$nodeCommand . ' ' . escapeshellarg($scriptFile) . ' 2>&1', $output, $returnVar);
        
        chdir($oldDir);
        unlink($scriptFile);
        
        $result = implode("\n", $output);
        $json = json_decode($result, true);
        
        if ($returnVar !== 0 || !$json || isset($json['error'])) {
            $error = $json['error'] ?? $result;
            throw new \RuntimeException("Node.js script failed: $error");
        }
        
        return $json;
    }
    
    private function computeJavaScriptSM3(string $input): string
    {
        $escapedInput = addslashes($input);
        $script = <<<JS
const { SM3Digest } = require('sm-js-bc');

try {
    const message = new TextEncoder().encode("$escapedInput");
    const digest = new SM3Digest();
    digest.update(message, 0, message.length);
    
    const result = new Uint8Array(digest.getDigestSize());
    digest.doFinal(result, 0);
    
    const hash = Array.from(result)
        .map(b => b.toString(16).padStart(2, '0'))
        .join('');
    
    console.log(JSON.stringify({ hash: hash }));
} catch (error) {
    console.log(JSON.stringify({ error: error.message }));
    process.exit(1);
}
JS;
        
        $result = $this->executeNodeScript($script);
        return $result['hash'];
    }
    
    private function encryptJavaScriptSM4_ECB(string $plaintext, string $key): string
    {
        $keyHex = bin2hex($key);
        $plaintextHex = bin2hex($plaintext);
        
        $script = <<<JS
const { SM4Engine, ECBBlockCipher, PaddedBufferedBlockCipher, PKCS7Padding, KeyParameter } = require('sm-js-bc');

try {
    const key = Buffer.from('$keyHex', 'hex');
    const plaintext = Buffer.from('$plaintextHex', 'hex');
    
    const cipher = new PaddedBufferedBlockCipher(
        new ECBBlockCipher(new SM4Engine()),
        new PKCS7Padding()
    );
    cipher.init(true, new KeyParameter(key));
    
    const output = new Uint8Array(cipher.getOutputSize(plaintext.length));
    let len = cipher.processBytes(plaintext, 0, plaintext.length, output, 0);
    len += cipher.doFinal(output, len);
    
    const ciphertext = Buffer.from(output.subarray(0, len)).toString('hex');
    console.log(JSON.stringify({ ciphertext: ciphertext }));
} catch (error) {
    console.log(JSON.stringify({ error: error.message }));
    process.exit(1);
}
JS;
        
        $result = $this->executeNodeScript($script);
        return hex2bin($result['ciphertext']);
    }
    
    private function decryptJavaScriptSM4_ECB(string $ciphertext, string $key): string
    {
        $keyHex = bin2hex($key);
        $ciphertextHex = bin2hex($ciphertext);
        
        $script = <<<JS
const { SM4Engine, ECBBlockCipher, PaddedBufferedBlockCipher, PKCS7Padding, KeyParameter } = require('sm-js-bc');

try {
    const key = Buffer.from('$keyHex', 'hex');
    const ciphertext = Buffer.from('$ciphertextHex', 'hex');
    
    const cipher = new PaddedBufferedBlockCipher(
        new ECBBlockCipher(new SM4Engine()),
        new PKCS7Padding()
    );
    cipher.init(false, new KeyParameter(key));
    
    const output = new Uint8Array(cipher.getOutputSize(ciphertext.length));
    let len = cipher.processBytes(ciphertext, 0, ciphertext.length, output, 0);
    len += cipher.doFinal(output, len);
    
    const plaintext = Buffer.from(output.subarray(0, len)).toString('utf8');
    console.log(JSON.stringify({ plaintext: plaintext }));
} catch (error) {
    console.log(JSON.stringify({ error: error.message }));
    process.exit(1);
}
JS;
        
        $result = $this->executeNodeScript($script);
        return $result['plaintext'];
    }
    
    private function encryptJavaScriptSM4_CBC(string $plaintext, string $key, string $iv): string
    {
        $keyHex = bin2hex($key);
        $ivHex = bin2hex($iv);
        $plaintextHex = bin2hex($plaintext);
        
        $script = <<<JS
const { SM4Engine, CBCBlockCipher, PaddedBufferedBlockCipher, PKCS7Padding, KeyParameter, ParametersWithIV } = require('sm-js-bc');

try {
    const key = Buffer.from('$keyHex', 'hex');
    const iv = Buffer.from('$ivHex', 'hex');
    const plaintext = Buffer.from('$plaintextHex', 'hex');
    
    const cipher = new PaddedBufferedBlockCipher(
        new CBCBlockCipher(new SM4Engine()),
        new PKCS7Padding()
    );
    cipher.init(true, new ParametersWithIV(new KeyParameter(key), iv));
    
    const output = new Uint8Array(cipher.getOutputSize(plaintext.length));
    let len = cipher.processBytes(plaintext, 0, plaintext.length, output, 0);
    len += cipher.doFinal(output, len);
    
    const ciphertext = Buffer.from(output.subarray(0, len)).toString('hex');
    console.log(JSON.stringify({ ciphertext: ciphertext }));
} catch (error) {
    console.log(JSON.stringify({ error: error.message }));
    process.exit(1);
}
JS;
        
        $result = $this->executeNodeScript($script);
        return hex2bin($result['ciphertext']);
    }
    
    private function decryptJavaScriptSM4_CBC(string $ciphertext, string $key, string $iv): string
    {
        $keyHex = bin2hex($key);
        $ivHex = bin2hex($iv);
        $ciphertextHex = bin2hex($ciphertext);
        
        $script = <<<JS
const { SM4Engine, CBCBlockCipher, PaddedBufferedBlockCipher, PKCS7Padding, KeyParameter, ParametersWithIV } = require('sm-js-bc');

try {
    const key = Buffer.from('$keyHex', 'hex');
    const iv = Buffer.from('$ivHex', 'hex');
    const ciphertext = Buffer.from('$ciphertextHex', 'hex');
    
    const cipher = new PaddedBufferedBlockCipher(
        new CBCBlockCipher(new SM4Engine()),
        new PKCS7Padding()
    );
    cipher.init(false, new ParametersWithIV(new KeyParameter(key), iv));
    
    const output = new Uint8Array(cipher.getOutputSize(ciphertext.length));
    let len = cipher.processBytes(ciphertext, 0, ciphertext.length, output, 0);
    len += cipher.doFinal(output, len);
    
    const plaintext = Buffer.from(output.subarray(0, len)).toString('utf8');
    console.log(JSON.stringify({ plaintext: plaintext }));
} catch (error) {
    console.log(JSON.stringify({ error: error.message }));
    process.exit(1);
}
JS;
        
        $result = $this->executeNodeScript($script);
        return $result['plaintext'];
    }
    
    private function encryptJavaScriptSM4_CTR(string $plaintext, string $key, string $iv): string
    {
        $keyHex = bin2hex($key);
        $ivHex = bin2hex($iv);
        $plaintextHex = bin2hex($plaintext);
        
        $script = <<<JS
const { SM4Engine, CTRBlockCipher, KeyParameter, ParametersWithIV } = require('sm-js-bc');

try {
    const key = Buffer.from('$keyHex', 'hex');
    const iv = Buffer.from('$ivHex', 'hex');
    const plaintext = Buffer.from('$plaintextHex', 'hex');
    
    const cipher = new CTRBlockCipher(new SM4Engine());
    cipher.init(true, new ParametersWithIV(new KeyParameter(key), iv));
    
    const output = new Uint8Array(plaintext.length);
    cipher.processBytes(plaintext, 0, plaintext.length, output, 0);
    
    const ciphertext = Buffer.from(output).toString('hex');
    console.log(JSON.stringify({ ciphertext: ciphertext }));
} catch (error) {
    console.log(JSON.stringify({ error: error.message }));
    process.exit(1);
}
JS;
        
        $result = $this->executeNodeScript($script);
        return hex2bin($result['ciphertext']);
    }
    
    private function encryptJavaScriptSM4_GCM(string $plaintext, string $key, string $iv, string $aad): string
    {
        $keyHex = bin2hex($key);
        $ivHex = bin2hex($iv);
        $plaintextHex = bin2hex($plaintext);
        $aadHex = bin2hex($aad);
        
        $script = <<<JS
const { SM4Engine, GCMBlockCipher, KeyParameter, AEADParameters } = require('sm-js-bc');

try {
    const key = Buffer.from('$keyHex', 'hex');
    const iv = Buffer.from('$ivHex', 'hex');
    const plaintext = Buffer.from('$plaintextHex', 'hex');
    const aad = Buffer.from('$aadHex', 'hex');
    
    const cipher = new GCMBlockCipher(new SM4Engine());
    const params = new AEADParameters(new KeyParameter(key), 128, iv, aad);
    cipher.init(true, params);
    
    const output = new Uint8Array(cipher.getOutputSize(plaintext.length));
    let len = cipher.processBytes(plaintext, 0, plaintext.length, output, 0);
    len += cipher.doFinal(output, len);
    
    const ciphertext = Buffer.from(output.subarray(0, len)).toString('hex');
    console.log(JSON.stringify({ ciphertext: ciphertext }));
} catch (error) {
    console.log(JSON.stringify({ error: error.message }));
    process.exit(1);
}
JS;
        
        $result = $this->executeNodeScript($script);
        return hex2bin($result['ciphertext']);
    }
    
    private function decryptJavaScriptSM4_GCM(string $ciphertext, string $key, string $iv, string $aad): string
    {
        $keyHex = bin2hex($key);
        $ivHex = bin2hex($iv);
        $ciphertextHex = bin2hex($ciphertext);
        $aadHex = bin2hex($aad);
        
        $script = <<<JS
const { SM4Engine, GCMBlockCipher, KeyParameter, AEADParameters } = require('sm-js-bc');

try {
    const key = Buffer.from('$keyHex', 'hex');
    const iv = Buffer.from('$ivHex', 'hex');
    const ciphertext = Buffer.from('$ciphertextHex', 'hex');
    const aad = Buffer.from('$aadHex', 'hex');
    
    const cipher = new GCMBlockCipher(new SM4Engine());
    const params = new AEADParameters(new KeyParameter(key), 128, iv, aad);
    cipher.init(false, params);
    
    const output = new Uint8Array(cipher.getOutputSize(ciphertext.length));
    let len = cipher.processBytes(ciphertext, 0, ciphertext.length, output, 0);
    len += cipher.doFinal(output, len);
    
    const plaintext = Buffer.from(output.subarray(0, len)).toString('utf8');
    console.log(JSON.stringify({ plaintext: plaintext }));
} catch (error) {
    console.log(JSON.stringify({ error: error.message }));
    process.exit(1);
}
JS;
        
        $result = $this->executeNodeScript($script);
        return $result['plaintext'];
    }
}
