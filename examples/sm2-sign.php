#!/usr/bin/env php
<?php
/**
 * SM2 数字签名示例
 * 演示如何使用 SM2 进行签名和验签
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\SM2;

echo "=== SM2 数字签名示例 ===\n\n";

// 生成密钥对
echo "步骤 1: 生成密钥对\n";
$keyPair = SM2::generateKeyPair();
echo "私钥: " . substr(gmp_strval($keyPair['privateKey'], 16), 0, 32) . "...\n";
echo "公钥 X: " . substr(gmp_strval($keyPair['publicKey']['x'], 16), 0, 32) . "...\n";

// 签名
echo "\n步骤 2: 对消息进行签名\n";
$message = 'Hello, SM2!';
echo "原始消息: {$message}\n";

$signature = SM2::sign($message, $keyPair['privateKey']);
echo "签名结果: " . bin2hex($signature) . "\n";
echo "签名长度: " . strlen($signature) . " 字节\n";

// 验签
echo "\n步骤 3: 验证签名\n";
$isValid = SM2::verify($message, $signature, $keyPair['publicKey']);
echo "签名验证结果: " . ($isValid ? '✅ 有效' : '❌ 无效') . "\n";

// 篡改消息后验签
echo "\n步骤 4: 篡改消息后验签\n";
$tamperedMessage = 'Hello, SM3!'; // 故意改错
$isValidTampered = SM2::verify($tamperedMessage, $signature, $keyPair['publicKey']);
echo "篡改消息: {$tamperedMessage}\n";
echo "签名验证结果: " . ($isValidTampered ? '✅ 有效' : '❌ 无效（预期）') . "\n";

// 不同密钥对验签
echo "\n步骤 5: 使用不同的公钥验签\n";
$anotherKeyPair = SM2::generateKeyPair();
$isValidWrongKey = SM2::verify($message, $signature, $anotherKeyPair['publicKey']);
echo "使用错误的公钥: " . ($isValidWrongKey ? '✅ 有效' : '❌ 无效（预期）') . "\n";

// 签名不同的消息
echo "\n步骤 6: 签名多条消息\n";
$messages = ['Message 1', 'Message 2', 'Message 3'];
foreach ($messages as $index => $msg) {
    $sig = SM2::sign($msg, $keyPair['privateKey']);
    $valid = SM2::verify($msg, $sig, $keyPair['publicKey']);
    $num = $index + 1;
    $checkMark = $valid ? '✅' : '❌';
    echo "消息 {$num}: \"{$msg}\" -> 签名长度: " . strlen($sig) . "字节, 验证: {$checkMark}\n";
}

echo "\n✅ SM2 数字签名示例运行完成\n";
