#!/usr/bin/env php
<?php
/**
 * SM4 ECB 模式简单示例
 * 
 * 演示：
 * 1. 生成随机密钥
 * 2. ECB 模式加密/解密（PKCS7 填充）
 * 3. 处理不同长度的数据
 * 
 * 注意：ECB 模式不安全，仅用于演示和兼容性测试
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\SM4;

echo "=== SM4 ECB 模式简单示例 ===\n\n";

// 1. 生成随机密钥
echo "--- 1. 生成密钥 ---\n";
$key = SM4::generateKey();
echo "密钥长度: " . strlen($key) . " 字节 (128位)\n";
echo "密钥 (hex): " . bin2hex($key) . "\n";
echo "\n";

// 2. 加密和解密
echo "--- 2. 加密/解密 ---\n";
$plaintext = 'Hello, SM4! 这是一个测试消息。';
echo "明文: {$plaintext}\n";
echo "明文长度: " . strlen($plaintext) . " 字节\n";

// 加密（ECB + PKCS7 填充）
$ciphertext = SM4::encrypt($plaintext, $key);
echo "密文长度: " . strlen($ciphertext) . " 字节\n";
echo "密文 (hex): " . bin2hex($ciphertext) . "\n";

// 解密
$decrypted = SM4::decrypt($ciphertext, $key);
echo "解密结果: {$decrypted}\n";
echo "解密成功: " . ($decrypted === $plaintext ? '✅' : '❌') . "\n";
echo "\n";

// 3. 不同长度的数据
echo "--- 3. 不同长度数据 ---\n";
$testCases = [
    ['name' => '空数据', 'data' => ''],
    ['name' => '1 字节', 'data' => 'A'],
    ['name' => '15 字节', 'data' => str_repeat('A', 15)],
    ['name' => '16 字节 (1块)', 'data' => str_repeat('A', 16)],
    ['name' => '17 字节', 'data' => str_repeat('A', 17)],
    ['name' => '32 字节 (2块)', 'data' => str_repeat('A', 32)],
    ['name' => '100 字节', 'data' => str_repeat('A', 100)],
];

foreach ($testCases as $testCase) {
    $data = $testCase['data'];
    $encrypted = SM4::encrypt($data, $key);
    $decryptedData = SM4::decrypt($encrypted, $key);
    $success = $data === $decryptedData;
    
    $dataLen = strlen($data);
    $encLen = strlen($encrypted);
    $mark = $success ? '✅' : '❌';
    echo "{$testCase['name']}: {$dataLen} → {$encLen} 字节 {$mark}\n";
}
echo "\n";

// 4. 单块加密（16字节，ECB模式）
echo "--- 4. 单块加密（16字节，ECB模式）---\n";
$block = str_repeat('B', 16); // 16 字节的 'B'
echo "块数据 (hex): " . bin2hex($block) . "\n";

// 使用ECB加密单个块
$encryptedBlock = SM4::encryptECB($block, $key);
echo "加密后 (hex): " . bin2hex($encryptedBlock) . "\n";

$decryptedBlock = SM4::decryptECB($encryptedBlock, $key);
echo "解密后 (hex): " . bin2hex($decryptedBlock) . "\n";
echo "块加密成功: " . ($block === $decryptedBlock ? '✅' : '❌') . "\n";
echo "\n";

echo "✅ SM4 ECB 模式示例运行完成\n";
echo "\n";
echo "⚠️  安全提示：\n";
echo "   ECB 模式不提供语义安全性，相同明文块产生相同密文块\n";
echo "   仅用于演示和兼容性测试，生产环境请使用 CBC、CTR 或 GCM 模式\n";
