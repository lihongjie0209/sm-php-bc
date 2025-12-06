#!/usr/bin/env php
<?php
/**
 * SM2 密钥交换示例
 * 演示如何使用 SM2 进行密钥协商（ECDH）
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\SM2;
use SmBc\Crypto\Agreement\SM2KeyExchange;
use SmBc\Crypto\Params\SM2KeyExchangePrivateParameters;
use SmBc\Crypto\Params\SM2KeyExchangePublicParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;

echo "=== SM2 密钥交换示例 ===\n\n";

echo "场景: Alice 和 Bob 通过 SM2 密钥交换协议协商共享密钥\n\n";

// 获取SM2域参数
$domainParams = SM2::getParameters();
$curve = $domainParams->getCurve();

// 步骤 1: Alice 生成静态密钥对
echo "步骤 1: Alice 生成静态密钥对\n";
$aliceStaticKeyPair = SM2::generateKeyPair();
$aliceStaticPriv = new ECPrivateKeyParameters($aliceStaticKeyPair['privateKey'], $domainParams);
$aliceStaticPubPoint = $curve->createPoint($aliceStaticKeyPair['publicKey']['x'], $aliceStaticKeyPair['publicKey']['y']);
$aliceStaticPub = new ECPublicKeyParameters($aliceStaticPubPoint, $domainParams);
echo "Alice 静态私钥: " . substr(gmp_strval($aliceStaticKeyPair['privateKey'], 16), 0, 32) . "...\n";
echo "Alice 静态公钥 X: " . substr(gmp_strval($aliceStaticKeyPair['publicKey']['x'], 16), 0, 32) . "...\n";

// 步骤 2: Alice 生成临时密钥对
echo "\n步骤 2: Alice 生成临时密钥对\n";
$aliceEphemeralKeyPair = SM2::generateKeyPair();
$aliceEphemeralPriv = new ECPrivateKeyParameters($aliceEphemeralKeyPair['privateKey'], $domainParams);
$aliceEphemeralPubPoint = $curve->createPoint($aliceEphemeralKeyPair['publicKey']['x'], $aliceEphemeralKeyPair['publicKey']['y']);
$aliceEphemeralPub = new ECPublicKeyParameters($aliceEphemeralPubPoint, $domainParams);
echo "Alice 临时公钥 X: " . substr(gmp_strval($aliceEphemeralKeyPair['publicKey']['x'], 16), 0, 32) . "...\n";

// 步骤 3: Bob 生成静态密钥对
echo "\n步骤 3: Bob 生成静态密钥对\n";
$bobStaticKeyPair = SM2::generateKeyPair();
$bobStaticPriv = new ECPrivateKeyParameters($bobStaticKeyPair['privateKey'], $domainParams);
$bobStaticPubPoint = $curve->createPoint($bobStaticKeyPair['publicKey']['x'], $bobStaticKeyPair['publicKey']['y']);
$bobStaticPub = new ECPublicKeyParameters($bobStaticPubPoint, $domainParams);
echo "Bob 静态私钥: " . substr(gmp_strval($bobStaticKeyPair['privateKey'], 16), 0, 32) . "...\n";
echo "Bob 静态公钥 X: " . substr(gmp_strval($bobStaticKeyPair['publicKey']['x'], 16), 0, 32) . "...\n";

// 步骤 4: Bob 生成临时密钥对
echo "\n步骤 4: Bob 生成临时密钥对\n";
$bobEphemeralKeyPair = SM2::generateKeyPair();
$bobEphemeralPriv = new ECPrivateKeyParameters($bobEphemeralKeyPair['privateKey'], $domainParams);
$bobEphemeralPubPoint = $curve->createPoint($bobEphemeralKeyPair['publicKey']['x'], $bobEphemeralKeyPair['publicKey']['y']);
$bobEphemeralPub = new ECPublicKeyParameters($bobEphemeralPubPoint, $domainParams);
echo "Bob 临时公钥 X: " . substr(gmp_strval($bobEphemeralKeyPair['publicKey']['x'], 16), 0, 32) . "...\n";

// 步骤 5: Alice 初始化密钥交换（作为发起方）
echo "\n步骤 5: Alice 初始化密钥交换（发起方）\n";
$aliceExchange = new SM2KeyExchange();
$alicePrivParams = new SM2KeyExchangePrivateParameters(
    true,  // initiator = true (发起方)
    $aliceStaticPriv,
    $aliceEphemeralPriv
);
$aliceExchange->init($alicePrivParams);

// 步骤 6: Alice 计算共享密钥
echo "\n步骤 6: Alice 计算共享密钥\n";
echo "Alice 使用: Bob的静态公钥 + Bob的临时公钥\n";
$bobPubParams = new SM2KeyExchangePublicParameters($bobStaticPub, $bobEphemeralPub);
$aliceSharedKey = $aliceExchange->calculateKey(128, $bobPubParams); // 128位 = 16字节
echo "Alice 共享密钥: " . bin2hex($aliceSharedKey) . "\n";

// 步骤 7: Bob 初始化密钥交换（作为响应方）
echo "\n步骤 7: Bob 初始化密钥交换（响应方）\n";
$bobExchange = new SM2KeyExchange();
$bobPrivParams = new SM2KeyExchangePrivateParameters(
    false,  // initiator = false (响应方)
    $bobStaticPriv,
    $bobEphemeralPriv
);
$bobExchange->init($bobPrivParams);

// 步骤 8: Bob 计算共享密钥
echo "\n步骤 8: Bob 计算共享密钥\n";
echo "Bob 使用: Alice的静态公钥 + Alice的临时公钥\n";
$alicePubParams = new SM2KeyExchangePublicParameters($aliceStaticPub, $aliceEphemeralPub);
$bobSharedKey = $bobExchange->calculateKey(128, $alicePubParams); // 128位 = 16字节
echo "Bob 共享密钥: " . bin2hex($bobSharedKey) . "\n";

// 步骤 9: 验证密钥一致性
echo "\n步骤 9: 验证密钥一致性\n";
$keysMatch = $aliceSharedKey === $bobSharedKey;
echo "密钥匹配: " . ($keysMatch ? '✅ 成功' : '❌ 失败') . "\n";
echo "密钥长度: " . strlen($aliceSharedKey) . " 字节\n";

// 生成不同长度的共享密钥
echo "\n--- 生成不同长度的共享密钥 ---\n";
$keyLengths = [128, 192, 256]; // 位

foreach ($keyLengths as $keyBits) {
    // 重新生成临时密钥对
    $alice2Ephemeral = SM2::generateKeyPair();
    $alice2EphemeralPriv = new ECPrivateKeyParameters($alice2Ephemeral['privateKey'], $domainParams);
    $alice2EphemeralPubPoint = $curve->createPoint($alice2Ephemeral['publicKey']['x'], $alice2Ephemeral['publicKey']['y']);
    $alice2EphemeralPub = new ECPublicKeyParameters($alice2EphemeralPubPoint, $domainParams);
    
    $bob2Ephemeral = SM2::generateKeyPair();
    $bob2EphemeralPriv = new ECPrivateKeyParameters($bob2Ephemeral['privateKey'], $domainParams);
    $bob2EphemeralPubPoint = $curve->createPoint($bob2Ephemeral['publicKey']['x'], $bob2Ephemeral['publicKey']['y']);
    $bob2EphemeralPub = new ECPublicKeyParameters($bob2EphemeralPubPoint, $domainParams);
    
    // Alice 计算密钥
    $aliceEx2 = new SM2KeyExchange();
    $alicePrivParams2 = new SM2KeyExchangePrivateParameters(true, $aliceStaticPriv, $alice2EphemeralPriv);
    $aliceEx2->init($alicePrivParams2);
    $bobPubParams2 = new SM2KeyExchangePublicParameters($bobStaticPub, $bob2EphemeralPub);
    $aliceKey = $aliceEx2->calculateKey($keyBits, $bobPubParams2);
    
    // Bob 计算密钥
    $bobEx2 = new SM2KeyExchange();
    $bobPrivParams2 = new SM2KeyExchangePrivateParameters(false, $bobStaticPriv, $bob2EphemeralPriv);
    $bobEx2->init($bobPrivParams2);
    $alicePubParams2 = new SM2KeyExchangePublicParameters($aliceStaticPub, $alice2EphemeralPub);
    $bobKey = $bobEx2->calculateKey($keyBits, $alicePubParams2);
    
    $keyBytes = $keyBits / 8;
    echo "\n{$keyBits}位密钥 ({$keyBytes}字节):\n";
    echo "Alice: " . substr(bin2hex($aliceKey), 0, 40) . "...\n";
    echo "Bob:   " . substr(bin2hex($bobKey), 0, 40) . "...\n";
    echo "匹配: " . ($aliceKey === $bobKey ? '✅' : '❌') . "\n";
}

echo "\n✅ SM2 密钥交换示例运行完成\n";
