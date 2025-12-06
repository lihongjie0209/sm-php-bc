# SM-PHP-BC

> SM2/SM3/SM4 PHP 实现 - 基于 Bouncy Castle 架构

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net/)
[![Composer](https://img.shields.io/badge/Composer-2.0+-green.svg)](https://getcomposer.org/)

一比一复刻 [Bouncy Castle Java](https://github.com/bcgit/bc-java) 的 SM2、SM3 和 SM4 算法的 PHP 实现。

## ✨ 特性

- 🔐 **SM2** - 椭圆曲线公钥密码算法（数字签名、公钥加密、密钥交换）
- 🔒 **SM3** - 密码杂凑算法（256位消息摘要）
- 🔑 **SM4** - 分组密码算法（128位对称加密）

- 🎯 **零外部依赖** - 纯 PHP 实现（仅需 GMP 扩展）
- 🔒 **完全兼容** - 与 Bouncy Castle Java 和 sm-js-bc 完全互操作
- 📦 **PSR-4 自动加载** - 遵循 PHP 编码标准
- 🧪 **全面测试** - 完整的 PHPUnit 测试覆盖
- 📚 **完整文档** - 详细的 API 文档和使用指南
- ✅ **生产就绪** - 所有核心功能完整实现

## 📦 安装

```bash
composer require sm-php-bc/sm-php-bc
```

### 系统要求

- PHP >= 8.1
- ext-gmp (大整数运算)
- ext-mbstring (字符串处理)

## 🚀 快速开始

> 💡 **提示**: 以下是基础用法示例。想要完整的可运行代码？直接跳转到 [📚 完整示例](#-完整示例) 章节，所有示例都可以直接运行！

以下代码片段展示了各算法的基本用法：

### SM3 哈希

```php
<?php
use SmBc\Crypto\Digests\SM3Digest;

$digest = new SM3Digest();
$data = 'Hello, SM3!';
$bytes = unpack('C*', $data);
$digest->update($bytes, 0, count($bytes));

$hash = array_fill(0, $digest->getDigestSize(), 0);
$digest->doFinal($hash, 0);

echo 'SM3 Hash: ' . bin2hex(pack('C*', ...$hash)) . PHP_EOL;
```

📖 **完整示例**: [examples/sm3_demo.php](./examples/sm3_demo.php)

### SM2 密钥对生成

```php
<?php
use SmBc\Crypto\EC\CustomNamedCurves;
use SmBc\Crypto\Generators\ECKeyPairGenerator;

// 获取 SM2 曲线参数
$domainParams = CustomNamedCurves::getByName('sm2p256v1');

// 生成密钥对
$generator = new ECKeyPairGenerator();
$generator->init($domainParams);
$keyPair = $generator->generateKeyPair();

$privateKey = $keyPair->getPrivate();
$publicKey = $keyPair->getPublic();

echo "Private Key: " . gmp_strval($privateKey->getD(), 16) . PHP_EOL;
echo "Public Key X: " . gmp_strval($publicKey->getQ()->getAffineXCoord()->toBigInteger(), 16) . PHP_EOL;
echo "Public Key Y: " . gmp_strval($publicKey->getQ()->getAffineYCoord()->toBigInteger(), 16) . PHP_EOL;
```

📖 **完整示例**: [examples/sm2_demo.php](./examples/sm2_demo.php)

### SM2 数字签名

```php
<?php
use SmBc\Crypto\Signers\SM2Signer;
use SmBc\Crypto\Params\ParametersWithRandom;

// 生成密钥对（省略）
$keyPair = $generator->generateKeyPair();

// 初始化签名器
$signer = new SM2Signer();
$signer->init(true, new ParametersWithRandom($keyPair->getPrivate()));

// 签名
$message = 'Hello, SM2!';
$messageBytes = unpack('C*', $message);
$signature = $signer->generateSignature($messageBytes);

// 验证签名
$verifier = new SM2Signer();
$verifier->init(false, $keyPair->getPublic());
$isValid = $verifier->verifySignature($messageBytes, $signature);

echo "Signature valid: " . ($isValid ? 'true' : 'false') . PHP_EOL;
```

📖 **完整示例**: [examples/sm2_demo.php](./examples/sm2_demo.php)

### SM2 公钥加密

```php
<?php
use SmBc\Crypto\Engines\SM2Engine;

// 生成密钥对（省略）
$keyPair = $generator->generateKeyPair();

// 加密
$engine = new SM2Engine();
$engine->init(true, new ParametersWithRandom($keyPair->getPublic()));
$plaintext = unpack('C*', 'Secret message');
$ciphertext = $engine->processBlock($plaintext, 0, count($plaintext));

// 解密
$engine->init(false, $keyPair->getPrivate());
$decrypted = $engine->processBlock($ciphertext, 0, count($ciphertext));

echo "Decrypted: " . pack('C*', ...$decrypted) . PHP_EOL;
```

📖 **完整示例**: [examples/sm2_demo.php](./examples/sm2_demo.php)

### SM4 对称加密

```php
<?php
use SmBc\Crypto\Engines\SM4Engine;
use SmBc\Crypto\Modes\CBCBlockCipher;
use SmBc\Crypto\Paddings\PKCS7Padding;
use SmBc\Crypto\Paddings\PaddedBufferedBlockCipher;
use SmBc\Crypto\Params\KeyParameter;
use SmBc\Crypto\Params\ParametersWithIV;

// 生成密钥和 IV
$key = random_bytes(16);
$iv = random_bytes(16);

// 创建加密器
$engine = new SM4Engine();
$blockCipher = new CBCBlockCipher($engine);
$padding = new PKCS7Padding();
$cipher = new PaddedBufferedBlockCipher($blockCipher, $padding);

// 加密
$keyParam = new KeyParameter(unpack('C*', $key));
$params = new ParametersWithIV($keyParam, unpack('C*', $iv));
$cipher->init(true, $params);

$plaintext = 'Hello, SM4!';
$input = unpack('C*', $plaintext);
$ciphertext = $cipher->doFinal($input);

// 解密
$cipher->init(false, $params);
$decrypted = $cipher->doFinal($ciphertext);

echo "Decrypted: " . pack('C*', ...$decrypted) . PHP_EOL;
```

> ⚠️ **安全提示**: 生产环境请使用 CBC、CTR 或 GCM 模式，避免使用 ECB 模式。

📖 **完整示例**: 
- [examples/sm4_demo.php](./examples/sm4_demo.php) - 多种工作模式
- [examples/advanced_demo.php](./examples/advanced_demo.php) - 高级用法

### SM2 密钥交换

```php
<?php
use SmBc\Crypto\Agreement\SM2KeyExchange;
use SmBc\Crypto\Params\SM2KeyExchangePrivateParameters;
use SmBc\Crypto\Params\SM2KeyExchangePublicParameters;

// Alice 生成密钥对（静态 + 临时）
$aliceStaticPair = $generator->generateKeyPair();
$aliceEphemeralPair = $generator->generateKeyPair();

// Bob 生成密钥对（静态 + 临时）
$bobStaticPair = $generator->generateKeyPair();
$bobEphemeralPair = $generator->generateKeyPair();

// Alice 计算共享密钥（发起方）
$aliceExchange = new SM2KeyExchange();
$alicePrivParams = new SM2KeyExchangePrivateParameters(
    true,  // initiator
    $aliceStaticPair->getPrivate(),
    $aliceEphemeralPair->getPrivate()
);
$aliceExchange->init($alicePrivParams);

$bobPubParams = new SM2KeyExchangePublicParameters(
    $bobStaticPair->getPublic(),
    $bobEphemeralPair->getPublic()
);
$aliceSharedKey = $aliceExchange->calculateKey(128, $bobPubParams);

// Bob 计算共享密钥（响应方）
$bobExchange = new SM2KeyExchange();
$bobPrivParams = new SM2KeyExchangePrivateParameters(
    false,  // responder
    $bobStaticPair->getPrivate(),
    $bobEphemeralPair->getPrivate()
);
$bobExchange->init($bobPrivParams);

$alicePubParams = new SM2KeyExchangePublicParameters(
    $aliceStaticPair->getPublic(),
    $aliceEphemeralPair->getPublic()
);
$bobSharedKey = $bobExchange->calculateKey(128, $alicePubParams);

// 验证双方密钥一致
$keysMatch = pack('C*', ...$aliceSharedKey) === pack('C*', ...$bobSharedKey);
echo "Keys match: " . ($keysMatch ? 'true' : 'false') . PHP_EOL;
```

> 💡 **提示**: SM2 密钥交换涉及多个参数类和步骤，建议查看完整示例了解详细用法。

📖 **完整示例**: [examples/key_exchange_demo.php](./examples/key_exchange_demo.php)

---

## 📚 完整示例

所有算法都提供了完整的可运行示例，位于 [`examples`](./examples) 目录：

| 示例文件 | 说明 | 演示内容 |
|---------|------|---------|
| [sm3_demo.php](./examples/sm3_demo.php) | SM3 哈希计算 | 基本哈希、分段更新、不同输入处理 |
| [sm2_demo.php](./examples/sm2_demo.php) | SM2 完整功能 | 密钥生成、签名验证、加密解密 |
| [sm4_demo.php](./examples/sm4_demo.php) | SM4 多种模式 | ECB/CBC/CTR/CFB/OFB/GCM 模式 |
| [key_exchange_demo.php](./examples/key_exchange_demo.php) | SM2 密钥交换 | ECDH 协议、密钥协商 |
| [advanced_demo.php](./examples/advanced_demo.php) | 高级特性 | KDF、多种填充方案、复杂场景 |

### 🚀 运行示例

```bash
# 进入项目目录
cd sm-php-bc

# 安装依赖
composer install

# 运行单个示例
php examples/sm3_demo.php           # SM3 哈希
php examples/sm2_demo.php           # SM2 完整功能
php examples/sm4_demo.php           # SM4 多种模式
php examples/key_exchange_demo.php  # SM2 密钥交换
php examples/advanced_demo.php      # 高级特性

# 运行所有示例
php examples/demo_all_features.php
```

详细说明请查看 [examples/README.md](./examples/README.md)。

## 📖 文档

详细文档请查看 [docs](./docs) 目录：

- **[API 文档](./docs/API.md)** - 详细的 API 参考
- **[使用指南](./docs/USAGE.md)** - 各算法的使用说明
- **[实现说明](./docs/IMPLEMENTATION.md)** - 技术实现细节
- **[与 JS 版本对比](./docs/JS_PHP_COMPARISON.md)** - 跨语言兼容性

## 🧪 测试

```bash
# 运行所有测试
composer test

# 或直接使用 PHPUnit
vendor/bin/phpunit

# 运行特定测试
vendor/bin/phpunit --filter SM2EngineTest
vendor/bin/phpunit --filter SM3DigestTest
vendor/bin/phpunit --filter SM4EngineTest

# 生成代码覆盖率报告
vendor/bin/phpunit --coverage-html coverage
```

## 🔧 开发

```bash
# 克隆仓库
git clone <repository-url>
cd sm-php-bc

# 安装依赖
composer install

# 运行代码检查
composer check

# 修复代码格式
composer fix
```

## 📋 支持的算法

### SM2（椭圆曲线密码）

- ✅ 密钥对生成
- ✅ 数字签名（SM2DSA）
- ✅ 签名验证
- ✅ 公钥加密
- ✅ 私钥解密
- ✅ 密钥交换（SM2DH）
- ✅ 自定义用户 ID 支持

### SM3（哈希算法）

- ✅ 消息摘要计算
- ✅ 流式数据更新
- ✅ 重置和重用
- ✅ 克隆摘要状态

### SM4（对称加密）

#### 工作模式

- ✅ ECB - 电子密码本模式
- ✅ CBC - 密码分组链接模式
- ✅ CTR - 计数器模式
- ✅ CFB - 密码反馈模式
- ✅ OFB - 输出反馈模式
- ✅ GCM - 伽罗瓦/计数器模式（带认证）

#### 填充方案

- ✅ PKCS7Padding - PKCS#7 填充
- ✅ ISO7816-4Padding - ISO/IEC 7816-4 填充
- ✅ ISO10126-2Padding - ISO 10126-2 填充
- ✅ ZeroBytePadding - 零字节填充
- ✅ NoPadding - 无填充（仅 CTR/OFB/CFB）

#### 高级特性

- ✅ 认证加密（GCM 模式）
- ✅ 密钥派生函数（KDF）
- ✅ 缓冲加密处理
- ✅ 流式数据处理

## 🔐 安全建议

1. **密钥管理**: 
   - 使用安全的随机数生成器生成密钥
   - 妥善保管私钥，不要硬编码在代码中
   - 定期更换密钥

2. **工作模式选择**:
   - 避免使用 ECB 模式（不安全）
   - 优先使用 GCM 模式（提供认证）
   - CBC/CTR 模式需配合 HMAC 使用

3. **IV 使用**:
   - 每次加密使用不同的 IV
   - IV 可以公开，但必须不可预测
   - GCM 模式的 nonce 不能重复使用

4. **填充选择**:
   - PKCS7 是最常用的填充方案
   - 流模式（CTR/OFB/CFB）不需要填充
   - 注意填充 oracle 攻击

## 🤝 贡献

欢迎贡献代码！请遵循以下步骤：

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/amazing-feature`)
3. 提交更改 (`git commit -m 'Add amazing feature'`)
4. 推送到分支 (`git push origin feature/amazing-feature`)
5. 创建 Pull Request

## 📄 许可证

本项目采用 MIT 许可证 - 详见 [LICENSE](LICENSE) 文件。

## 🙏 致谢

- [Bouncy Castle](https://www.bouncycastle.org/) - 原始 Java 实现
- [sm-js-bc](../sm-js-bc) - TypeScript 参考实现
- [GM/T 系列标准](http://www.gmbz.org.cn/) - 国密算法标准文档

## 📮 联系方式

如有问题或建议，请通过以下方式联系：

- 提交 Issue: [GitHub Issues](https://github.com/your-org/sm-php-bc/issues)
- 发送邮件: your-email@example.com

---

**注意**: 本实现仅供学习和研究使用。如需在生产环境中使用国密算法，请确保符合相关法律法规要求，并进行充分的安全评估和测试。
