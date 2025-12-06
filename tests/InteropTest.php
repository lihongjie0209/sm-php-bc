<?php

declare(strict_types=1);

namespace SmBc\Tests;

use PHPUnit\Framework\TestCase;
use SmBc\SM4;
use SmBc\SM2;
use SmBc\Crypto\Digests\SM3Digest;
use SmBc\Crypto\Engines\SM2Engine;

/**
 * 跨语言互操作性测试 - PHP 与 JavaScript (sm-js-bc NPM包) 互操作
 * 
 * 测试 PHP 实现与 NPM 包 sm-js-bc 的兼容性
 * 确保不同语言实现之间可以正确加密/解密、签名/验证
 */
class InteropTest extends TestCase
{
    /**
     * 执行 Node.js 脚本并返回结果
     */
    private function executeNode(string $script): string
    {
        // 在项目根目录创建临时文件，这样 Node.js 可以找到 node_modules
        $projectRoot = dirname(__DIR__);
        $tempFile = $projectRoot . DIRECTORY_SEPARATOR . 'temp_node_' . uniqid() . '.js';
        file_put_contents($tempFile, $script);

        try {
            // 执行 Node.js
            $command = "node " . escapeshellarg($tempFile) . " 2>&1";
            $output = shell_exec($command);
            
            if ($output === null) {
                throw new \RuntimeException("Failed to execute Node.js script");
            }
            
            return trim($output);
        } finally {
            // 删除临时文件
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * SM4 ECB 模式互操作测试 - 使用国密标准测试向量
     * 测试向量来自 GB/T 32907-2016
     */
    public function testSM4_ECB_StandardVector(): void
    {
        // 已知测试向量（来自国密标准）
        $key = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $plaintext = hex2bin('0123456789ABCDEFFEDCBA9876543210');
        $expectedCiphertext = hex2bin('681EDF34D206965E86B3E94F536E4246');
        
        $ciphertext = SM4::encrypt($plaintext, $key);
        
        $this->assertEquals(
            $expectedCiphertext,
            $ciphertext,
            'SM4 ECB encryption should match standard test vector'
        );
        
        $decrypted = SM4::decrypt($ciphertext, $key);
        $this->assertEquals($plaintext, $decrypted);
    }
    
    /**
     * SM3 摘要互操作测试 - PHP 与 JS 互操作
     */
    public function testSM3_HashInterop(): void
    {
        $message = 'abc';

        // PHP 计算哈希
        $digest = new SM3Digest();
        
        // 测试向量1: "abc"
        $input1 = 'abc';
        $expected1 = '66c7f0f462eeedd9d1f2d46bdc10e4e24167c4875cf2f7a2297da02b8f4ba8e0';
        
        // 逐字节更新
        for ($i = 0; $i < strlen($input1); $i++) {
            $digest->update(ord($input1[$i]));
        }
        $output = str_repeat("\0", 32);
        $digest->doFinal($output, 0);
        
        $this->assertEquals(
            $expected1,
            bin2hex($output),
            'SM3 digest of "abc" should match standard'
        );
        
        // 测试向量2: "abcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcdabcd"
        $digest->reset();
        $input2 = str_repeat('abcd', 16);
        $expected2 = 'debe9ff92275b8a138604889c18e5a4d6fdb70e5387e5765293dcba39c0c5732';
        
        // 逐字节更新
        for ($i = 0; $i < strlen($input2); $i++) {
            $digest->update(ord($input2[$i]));
        }
        $output = str_repeat("\0", 32);
        $digest->doFinal($output, 0);
        
        $this->assertEquals(
            $expected2,
            bin2hex($output),
            'SM3 digest of repeated "abcd" should match standard'
        );
    }
    
    /**
     * SM2 签名互操作测试
     * 使用固定的测试向量
     */
    public function testSM2_Sign_Interop(): void
    {
        // 固定的测试密钥对（来自标准测试向量）
        $privateKeyHex = '128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263';
        $publicKeyHex = '040AE4C7798AA0F119471BEE11825BE46202BB79E2A5844495E97C04FF4DF2548A7C0240F88F1CD4E16352A73C17B7F16F07353E53A176D684A9FE0C6BB798E857';
        
        $message = 'message digest';
        $userId = '1234567812345678';
        
        // 签名（注意：由于使用随机k值，每次签名结果会不同）
        $signature = SM2::signWithHex($message, $privateKeyHex, $userId);
        
        // 验证签名
        $valid = SM2::verifyWithHex($message, $signature, $publicKeyHex, $userId);
        
        $this->assertTrue($valid, 'Signature verification should succeed');
        
        // 修改消息后验证应失败
        $valid = SM2::verifyWithHex($message . 'x', $signature, $publicKeyHex, $userId);
        $this->assertFalse($valid, 'Signature verification should fail with modified message');
    }
    
    /**
     * SM2 加密互操作测试
     */
    public function testSM2_Encrypt_Interop(): void
    {
        // 生成密钥对
        $keyPair = SM2::generateKeyPair();
        
        $plaintext = 'Hello, SM2!';
        
        // C1C3C2模式加密
        $ciphertext = SM2::encryptWithHex($plaintext, $keyPair->getPublicKeyHex(), SM2Engine::MODE_C1C3C2);
        
        // 解密
        $decrypted = SM2::decryptWithHex($ciphertext, $keyPair->getPrivateKeyHex(), SM2Engine::MODE_C1C3C2);
        
        $this->assertEquals($plaintext, $decrypted, 'SM2 encryption/decryption roundtrip');
        
        // C1C2C3模式
        $ciphertext2 = SM2::encryptWithHex($plaintext, $keyPair->getPublicKeyHex(), SM2Engine::MODE_C1C2C3);
        $decrypted2 = SM2::decryptWithHex($ciphertext2, $keyPair->getPrivateKeyHex(), SM2Engine::MODE_C1C2C3);
        
        $this->assertEquals($plaintext, $decrypted2, 'SM2 encryption/decryption roundtrip (C1C2C3)');
    }
    
    /**
     * 导出测试向量供JS验证
     * 运行: php tests/InteropTest.php --export
     */
    public function testExportTestVectors(): void
    {
        $vectors = [];
        
        // SM4测试向量
        $sm4Key = SM4::generateKey();
        $sm4Plain = 'Hello, World!';
        $sm4Cipher = SM4::encrypt($sm4Plain, $sm4Key);
        
        $vectors['sm4'] = [
            'key' => bin2hex($sm4Key),
            'plaintext' => $sm4Plain,
            'ciphertext' => bin2hex($sm4Cipher),
        ];
        
        // SM2测试向量
        $sm2KeyPair = SM2::generateKeyPair();
        $sm2Message = 'Test message';
        $sm2Signature = SM2::signWithHex($sm2Message, $sm2KeyPair->getPrivateKeyHex(), '1234567812345678');
        
        $vectors['sm2'] = [
            'privateKey' => $sm2KeyPair->getPrivateKeyHex(),
            'publicKey' => $sm2KeyPair->getPublicKeyHex(),
            'message' => $sm2Message,
            'userId' => '1234567812345678',
            'signature' => bin2hex($sm2Signature),
        ];
        
        // SM3测试向量
        $digest = new SM3Digest();
        $sm3Input = 'abc';
        for ($i = 0; $i < strlen($sm3Input); $i++) {
            $digest->update(ord($sm3Input[$i]));
        }
        $sm3Output = str_repeat("\0", 32);
        $digest->doFinal($sm3Output, 0);
        
        $vectors['sm3'] = [
            'input' => $sm3Input,
            'output' => bin2hex($sm3Output),
        ];
        
        $json = json_encode($vectors, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        // 仅在明确要求时输出
        if (in_array('--export', $_SERVER['argv'] ?? [])) {
            file_put_contents(__DIR__ . '/test_vectors.json', $json);
            echo "Test vectors exported to tests/test_vectors.json\n";
        }
        
        $this->assertTrue(true);
    }

    /**
     * 测试 SM4-ECB 互操作 - PHP 加密，JS 解密
     */
    public function testSM4EcbPhpEncryptJsDecrypt(): void
    {
        $key = '0123456789abcdeffedcba9876543210';
        $plaintext = 'Hello SM4!';

        // PHP 加密
        $keyBin = hex2bin($key);
        $ciphertext = SM4::encrypt($plaintext, $keyBin);
        $ciphertextHex = bin2hex($ciphertext);

        // JS 解密
        $nodeScript = <<<JS
const { SM4 } = require('sm-js-bc');
const key = Buffer.from('$key', 'hex');
const ciphertext = Buffer.from('$ciphertextHex', 'hex');
const plaintext = SM4.decrypt(ciphertext, key);
console.log(plaintext.toString('utf8'));
JS;

        $result = $this->executeNode($nodeScript);
        $this->assertEquals($plaintext, $result);
    }

    /**
     * 测试 SM4-ECB 互操作 - JS 加密，PHP 解密
     */
    public function testSM4EcbJsEncryptPhpDecrypt(): void
    {
        $key = '0123456789abcdeffedcba9876543210';
        $plaintext = 'Hello from JS!';

        // JS 加密
        $nodeScript = <<<JS
const { SM4 } = require('sm-js-bc');
const key = Buffer.from('$key', 'hex');
const plaintext = '$plaintext';
const ciphertext = SM4.encrypt(Buffer.from(plaintext, 'utf8'), key);
console.log(ciphertext.toString('hex'));
JS;

        $ciphertextHex = $this->executeNode($nodeScript);

        // PHP 解密
        $keyBin = hex2bin($key);
        $ciphertext = hex2bin($ciphertextHex);
        $decrypted = SM4::decrypt($ciphertext, $keyBin);

        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * 测试 SM3 哈希互操作 - PHP 与 JS 对比
     */
    public function testSM3HashWithJsInterop(): void
    {
        $message = 'abc';

        // PHP 计算哈希
        $digest = new SM3Digest();
        for ($i = 0; $i < strlen($message); $i++) {
            $digest->update(ord($message[$i]));
        }
        $output = str_repeat("\0", 32);
        $digest->doFinal($output, 0);
        $phpHash = bin2hex($output);

        // JS 计算哈希
        $nodeScript = <<<JS
const { SM3 } = require('sm-js-bc');
const message = '$message';
const hash = SM3.hash(Buffer.from(message, 'utf8'));
console.log(hash.toString('hex'));
JS;

        $jsHash = $this->executeNode($nodeScript);

        $this->assertEquals($phpHash, $jsHash);
        // SM3("abc") 的标准结果
        $this->assertEquals('66c7f0f462eeedd9d1f2d46bdc10e4e24167c4875cf2f7a2297da02b8f4ba8e0', $phpHash);
    }

    /**
     * 测试 SM2 签名/验证互操作 - PHP 签名，JS 验证
     */
    public function testSM2SignPhpVerifyJs(): void
    {
        $privateKey = '128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263';
        $publicKey = '040AE4C7798AA0F119471BEE11825BE46202BB79E2A5844495E97C04FF4DF2548A7C0240F88F1CD4E16352A73C17B7F16F07353E53A176D684A9FE0C6BB798E857';
        $message = 'message digest';
        $userId = '1234567812345678';

        // PHP 签名
        $signature = SM2::signWithHex($message, $privateKey, $userId);
        $signatureHex = bin2hex($signature);

        // JS 验证
        $nodeScript = <<<JS
const { SM2 } = require('sm-js-bc');
const publicKey = '$publicKey';
const message = '$message';
const userId = '$userId';
const signature = Buffer.from('$signatureHex', 'hex');

const valid = SM2.verifyWithHex(Buffer.from(message, 'utf8'), signature, publicKey, userId);
console.log(valid);
JS;

        $result = $this->executeNode($nodeScript);
        $this->assertEquals('true', $result);
    }

    /**
     * 测试 SM2 签名/验证互操作 - JS 签名，PHP 验证
     */
    public function testSM2SignJsVerifyPhp(): void
    {
        $privateKey = '128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263';
        $publicKey = '040AE4C7798AA0F119471BEE11825BE46202BB79E2A5844495E97C04FF4DF2548A7C0240F88F1CD4E16352A73C17B7F16F07353E53A176D684A9FE0C6BB798E857';
        $message = 'JS signed message';
        $userId = '1234567812345678';

        // JS 签名
        $nodeScript = <<<JS
const { SM2 } = require('sm-js-bc');
const privateKey = '$privateKey';
const message = '$message';
const userId = '$userId';

const signature = SM2.signWithHex(Buffer.from(message, 'utf8'), privateKey, userId);
console.log(signature.toString('hex'));
JS;

        $signatureHex = $this->executeNode($nodeScript);
        $signature = hex2bin($signatureHex);

        // PHP 验证
        $valid = SM2::verifyWithHex($message, $signature, $publicKey, $userId);

        $this->assertTrue($valid);
    }

    /**
     * 测试 SM2 加密/解密互操作 - PHP 加密，JS 解密
     */
    public function testSM2EncryptPhpDecryptJs(): void
    {
        $privateKey = '128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263';
        $publicKey = '040AE4C7798AA0F119471BEE11825BE46202BB79E2A5844495E97C04FF4DF2548A7C0240F88F1CD4E16352A73C17B7F16F07353E53A176D684A9FE0C6BB798E857';
        $plaintext = 'encryption test';

        // PHP 加密 (C1C3C2 mode)
        $ciphertext = SM2::encryptWithHex($plaintext, $publicKey, SM2Engine::MODE_C1C3C2);
        $ciphertextHex = bin2hex($ciphertext);

        // JS 解密
        $nodeScript = <<<JS
const { SM2, SM2Engine } = require('sm-js-bc');
const privateKey = '$privateKey';
const ciphertext = Buffer.from('$ciphertextHex', 'hex');

const plaintext = SM2.decryptWithHex(ciphertext, privateKey, SM2Engine.MODE_C1C3C2);
console.log(plaintext.toString('utf8'));
JS;

        $result = $this->executeNode($nodeScript);
        $this->assertEquals($plaintext, $result);
    }

    /**
     * 测试 SM2 加密/解密互操作 - JS 加密，PHP 解密
     */
    public function testSM2EncryptJsDecryptPhp(): void
    {
        $privateKey = '128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263';
        $publicKey = '040AE4C7798AA0F119471BEE11825BE46202BB79E2A5844495E97C04FF4DF2548A7C0240F88F1CD4E16352A73C17B7F16F07353E53A176D684A9FE0C6BB798E857';
        $plaintext = 'JS encryption test';

        // JS 加密
        $nodeScript = <<<JS
const { SM2, SM2Engine } = require('sm-js-bc');
const publicKey = '$publicKey';
const plaintext = '$plaintext';

const ciphertext = SM2.encryptWithHex(Buffer.from(plaintext, 'utf8'), publicKey, SM2Engine.MODE_C1C3C2);
console.log(ciphertext.toString('hex'));
JS;

        $ciphertextHex = $this->executeNode($nodeScript);
        $ciphertext = hex2bin($ciphertextHex);

        // PHP 解密
        $decrypted = SM2::decryptWithHex($ciphertext, $privateKey, SM2Engine::MODE_C1C3C2);

        $this->assertEquals($plaintext, $decrypted);
    }
}
