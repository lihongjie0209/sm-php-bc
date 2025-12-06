#!/usr/bin/env php
<?php
/**
 * SM2 公钥加密示例
 * 演示如何使用 SM2 进行加密和解密
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\SM2;

echo "=== SM2 公钥加密示例 ===\n\n";

// 生成密钥对
echo "步骤 1: 生成密钥对\n";
$keyPair = SM2::generateKeyPair();
echo "公钥 X: " . substr(gmp_strval($keyPair['publicKey']['x'], 16), 0, 32) . "...\n";
echo "私钥: " . substr(gmp_strval($keyPair['privateKey'], 16), 0, 32) . "...\n";

// 加密
echo "\n步骤 2: 使用公钥加密消息\n";
$plaintext = 'Secret message';
echo "明文: {$plaintext}\n";
echo "明文长度: " . strlen($plaintext) . " 字节\n";

$ciphertext = SM2::encrypt($plaintext, $keyPair['publicKey']);
echo "密文: " . bin2hex($ciphertext) . "\n";
echo "密文长度: " . strlen($ciphertext) . " 字节\n";

// 解密
echo "\n步骤 3: 使用私钥解密消息\n";
$decrypted = SM2::decrypt($ciphertext, $keyPair['privateKey']);
echo "解密结果: {$decrypted}\n";
echo "解密成功: " . ($decrypted === $plaintext ? '✅' : '❌') . "\n";

// 加密不同长度的消息
echo "\n步骤 4: 加密不同长度的消息\n";
$testMessages = [
    'A',                    // 1 字节
    'Hello',               // 5 字节
    'This is a longer message for testing SM2 encryption!', // 54 字节
    '中文消息测试',         // UTF-8 多字节字符
];

foreach ($testMessages as $index => $msg) {
    $cipher = SM2::encrypt($msg, $keyPair['publicKey']);
    $dec = SM2::decrypt($cipher, $keyPair['privateKey']);
    $success = $dec === $msg;
    $num = $index + 1;
    
    echo "\n测试 {$num}:\n";
    echo "原文: \"{$msg}\"\n";
    echo "明文长度: " . strlen($msg) . "字节, 密文长度: " . strlen($cipher) . "字节\n";
    echo "解密结果: " . ($success ? '✅ 成功' : '❌ 失败') . "\n";
}

// 使用错误的私钥解密
echo "\n步骤 5: 使用错误的私钥解密\n";
$anotherKeyPair = SM2::generateKeyPair();
try {
    $wrongDecrypted = SM2::decrypt($ciphertext, $anotherKeyPair['privateKey']);
    echo "使用错误私钥解密: {$wrongDecrypted}\n";
} catch (Exception $e) {
    echo "使用错误私钥解密: ❌ 失败（预期）\n";
    echo "错误信息: " . $e->getMessage() . "\n";
}

echo "\n✅ SM2 公钥加密示例运行完成\n";
