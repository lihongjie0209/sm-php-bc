<?php

declare(strict_types=1);

/**
 * SM2 PEM 编码示例
 * 
 * 演示如何使用 PEM 格式保存和加载 SM2 密钥
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SmBc\Crypto\Params\ECDomainParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;
use SmBc\Math\EC\ECCurveFp;
use SmBc\Math\BigInteger;
use SmBc\Util\Pem\SM2KeyPemEncoder;
use SmBc\Crypto\Signers\SM2Signer;
use SmBc\Crypto\Params\ParametersWithRandom;
use SmBc\Util\SecureRandom;

echo "====================================\n";
echo "SM2 PEM 编码示例\n";
echo "====================================\n\n";

// ============================================
// 1. 生成 SM2 密钥对
// ============================================
echo "1. 生成 SM2 密钥对\n";
echo "-----------------------------------\n";

// SM2 曲线参数
$p = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFF');
$a = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFC');
$b = new BigInteger('0x28E9FA9E9D9F5E344D5A9E4BCF6509A7F39789F515AB8F92DDBCBD414D940E93');
$n = new BigInteger('0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123');

$curve = new ECCurveFp($p, $a, $b, $n, BigInteger::ONE());

$gx = new BigInteger('0x32C4AE2C1F1981195F9904466A39C9948FE30BBFF2660BE1715A4589334C74C7');
$gy = new BigInteger('0xBC3736A2F4F6779C59BDCEE36B692153D0A9877CC62A474002DF32E52139F0A0');
$g = $curve->createPoint($gx, $gy);

$domainParams = new ECDomainParameters($curve, $g, $n, BigInteger::ONE());

// 使用固定私钥（实际应用中应使用随机生成）
$d = new BigInteger('0x128B2FA8BD433C6C068C8D803DFF79792A519A55171B1B650C23661D15897263');
$privateKey = new ECPrivateKeyParameters($d, $domainParams);

// 计算公钥
$q = $domainParams->getG()->multiply($d)->normalize();
$publicKey = new ECPublicKeyParameters($q, $domainParams);

echo "密钥对生成完成\n\n";

// ============================================
// 2. 将私钥编码为 PEM 格式
// ============================================
echo "2. 将私钥编码为 PEM 格式\n";
echo "-----------------------------------\n";

$privateKeyPem = SM2KeyPemEncoder::encodePrivateKey($privateKey);

echo $privateKeyPem;
echo "\n";

// ============================================
// 3. 将公钥编码为 PEM 格式
// ============================================
echo "3. 将公钥编码为 PEM 格式\n";
echo "-----------------------------------\n";

$publicKeyPem = SM2KeyPemEncoder::encodePublicKey($publicKey);

echo $publicKeyPem;
echo "\n";

// ============================================
// 4. 从 PEM 格式加载私钥
// ============================================
echo "4. 从 PEM 格式加载私钥\n";
echo "-----------------------------------\n";

$loadedPrivateKey = SM2KeyPemEncoder::decodePrivateKey($privateKeyPem);

echo "私钥加载成功\n";
echo "原始私钥 D: " . gmp_strval($privateKey->getD()->val, 16) . "\n";
echo "加载私钥 D: " . gmp_strval($loadedPrivateKey->getD()->val, 16) . "\n";
echo "验证: " . ($privateKey->getD()->equals($loadedPrivateKey->getD()) ? '一致 ✓' : '不一致 ✗') . "\n\n";

// ============================================
// 5. 从 PEM 格式加载公钥
// ============================================
echo "5. 从 PEM 格式加载公钥\n";
echo "-----------------------------------\n";

$loadedPublicKey = SM2KeyPemEncoder::decodePublicKey($publicKeyPem);

echo "公钥加载成功\n";
$originalQ = $publicKey->getQ()->normalize();
$loadedQ = $loadedPublicKey->getQ()->normalize();
echo "原始公钥 X: " . substr(gmp_strval($originalQ->getAffineXCoord()->toBigInteger()->val, 16), 0, 32) . "...\n";
echo "加载公钥 X: " . substr(gmp_strval($loadedQ->getAffineXCoord()->toBigInteger()->val, 16), 0, 32) . "...\n";
echo "验证: " . ($originalQ->getAffineXCoord()->toBigInteger()->equals($loadedQ->getAffineXCoord()->toBigInteger()) ? '一致 ✓' : '不一致 ✗') . "\n\n";

// ============================================
// 6. 实用场景：使用加载的密钥进行签名验证
// ============================================
echo "6. 使用加载的密钥进行签名验证\n";
echo "-----------------------------------\n";

$message = 'Test message for signing with PEM loaded keys';

// 使用加载的私钥签名
$signer = new SM2Signer();
$signer->init(true, new ParametersWithRandom($loadedPrivateKey, new SecureRandom()));
$signer->updateBytes($message, 0, strlen($message));
$signature = $signer->generateSignature();

echo "消息: $message\n";
echo "签名: " . bin2hex($signature) . "\n";

// 使用加载的公钥验证
$verifier = new SM2Signer();
$verifier->init(false, $loadedPublicKey);
$verifier->updateBytes($message, 0, strlen($message));
$isValid = $verifier->verifySignature($signature);

echo "验证结果: " . ($isValid ? '有效 ✓' : '无效 ✗') . "\n\n";

// ============================================
// 7. 保存密钥到文件
// ============================================
echo "7. 保存密钥到文件\n";
echo "-----------------------------------\n";

$privateKeyFile = '/tmp/sm2_private_key.pem';
$publicKeyFile = '/tmp/sm2_public_key.pem';

file_put_contents($privateKeyFile, $privateKeyPem);
file_put_contents($publicKeyFile, $publicKeyPem);

echo "私钥已保存到: $privateKeyFile\n";
echo "公钥已保存到: $publicKeyFile\n\n";

// ============================================
// 8. 从文件加载密钥
// ============================================
echo "8. 从文件加载密钥\n";
echo "-----------------------------------\n";

$loadedFromFilePrivate = SM2KeyPemEncoder::decodePrivateKey(file_get_contents($privateKeyFile));
$loadedFromFilePublic = SM2KeyPemEncoder::decodePublicKey(file_get_contents($publicKeyFile));

echo "从文件加载密钥成功\n";
echo "私钥验证: " . ($loadedFromFilePrivate->getD()->equals($privateKey->getD()) ? '一致 ✓' : '不一致 ✗') . "\n";

$filePublicQ = $loadedFromFilePublic->getQ()->normalize();
echo "公钥验证: " . ($filePublicQ->getAffineXCoord()->toBigInteger()->equals($originalQ->getAffineXCoord()->toBigInteger()) ? '一致 ✓' : '不一致 ✗') . "\n\n";

// 清理临时文件
unlink($privateKeyFile);
unlink($publicKeyFile);

echo "====================================\n";
echo "示例完成\n";
echo "====================================\n";
echo "\n";
echo "💡 提示：\n";
echo "- PEM 格式便于密钥的存储和传输\n";
echo "- 私钥应妥善保管，避免泄露\n";
echo "- 本示例使用简化的 JSON 编码格式\n";
echo "- 完整的 PKCS#8 支持将在后续版本提供\n";
