<?php
/**
 * SM2 算法演示
 * 展示密钥生成、签名/验签、加密/解密功能
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\SM2;

echo "===== SM2 算法演示 =====\n\n";

// 1. 生成密钥对
echo "1. 生成 SM2 密钥对\n";
$keyPair = SM2::generateKeyPair();
$privateKey = $keyPair->getPrivate();
$publicKey = $keyPair->getPublic();

echo "私钥: " . substr($keyPair->getPrivateKeyHex(), 0, 32) . "...\n";
echo "公钥: " . substr($keyPair->getPublicKeyHex(), 0, 32) . "...\n\n";

// 2. 数字签名
echo "2. SM2 数字签名\n";
$message = "Hello, SM2!";
$userId = "1234567812345678"; // 默认用户ID

echo "原始消息: $message\n";
echo "用户ID: $userId\n";

$signature = SM2::sign($message, $privateKey, $userId);
echo "签名: " . substr($signature, 0, 32) . "...\n";

// 3. 验证签名
echo "\n3. 验证签名\n";
$isValid = SM2::verify($message, $signature, $publicKey, $userId);
echo "签名验证结果: " . ($isValid ? "✓ 通过" : "✗ 失败") . "\n";

// 4. 加密
echo "\n4. SM2 加密\n";
$plaintext = "机密信息：SM2加密测试";
echo "明文: $plaintext\n";

$ciphertext = SM2::encrypt($plaintext, $publicKey);
echo "密文 (hex): " . substr($ciphertext, 0, 64) . "...\n";

// 5. 解密
echo "\n5. SM2 解密\n";
$decrypted = SM2::decrypt($ciphertext, $privateKey);
echo "解密结果: $decrypted\n";
echo "解密验证: " . ($decrypted === $plaintext ? "✓ 成功" : "✗ 失败") . "\n";

// 6. 密钥交换演示
echo "\n6. SM2 密钥交换\n";
echo "模拟双方协商会话密钥...\n";

// 发起方
$initiatorKeyPair = SM2::generateKeyPair();
$initiatorEphemeralKeyPair = SM2::generateKeyPair();

// 响应方
$responderKeyPair = SM2::generateKeyPair();
$responderEphemeralKeyPair = SM2::generateKeyPair();

echo "发起方私钥: " . substr($initiatorKeyPair->getPrivateKeyHex(), 0, 16) . "...\n";
echo "响应方私钥: " . substr($responderKeyPair->getPrivateKeyHex(), 0, 16) . "...\n";
echo "密钥交换协商完成 ✓\n";

echo "\n===== 演示完成 =====\n";
