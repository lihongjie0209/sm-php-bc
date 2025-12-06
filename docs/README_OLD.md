# SM-PHP-BC

> SM2/SM3/SM4 PHP implementation based on Bouncy Castle Java

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net/)

一比一复刻 [Bouncy Castle Java](https://github.com/bcgit/bc-java) 的 SM2、SM3 和 SM4 算法的 PHP 实现，移植自 [sm-js-bc](../sm-js-bc)。

## ✨ 特性

- 🔐 **SM2** - 椭圆曲线公钥密码算法（数字签名、公钥加密、密钥交换）
- 🔒 **SM3** - 密码杂凑算法（256位消息摘要）
- 🔑 **SM4** - 分组密码算法（128位对称加密）

- 🎯 **零外部依赖** - 纯 PHP 实现（仅需 ext-gmp）
- 🔒 **完全兼容** - 与 Bouncy Castle Java 完全互操作
- 📦 **PSR-12 规范** - 遵循 PHP 编码标准
- 🧪 **全面测试** - 126+ 单元测试，400+ 断言
- 📚 **完整文档** - 详细的 API 文档和使用指南
- ✅ **生产就绪** - 所有测试通过，可用于生产环境

## 📦 安装

```bash
composer require sm-php-bc/sm-php-bc
```

或克隆仓库：

```bash
git clone <repository-url>
cd sm-php-bc
composer install
```

## 🚀 快速开始

> 💡 **提示**: 以下是基础用法示例。想要完整的可运行代码？直接跳转到 [📚 完整示例](#-完整示例) 章节，所有示例都可以直接运行！

以下代码片段展示了各算法的基本用法：

### SM3 哈希

```php
use SmBc\Crypto\Digests\SM3Digest;

$digest = new SM3Digest();
$data = 'Hello, SM3!';
$digest->updateBytes($data, 0, strlen($data));

$hash = str_repeat("\x00", $digest->getDigestSize());
$digest->doFinal($hash, 0);

echo 'SM3 Hash: ' . bin2hex($hash);
```

📖 **完整示例**: [examples/sm3-hash.php](./examples/sm3-hash.php)

### SM2 密钥对生成

```php
use SmBc\Crypto\SM2;

$keyPair = SM2::generateKeyPair();
echo 'Private key: ' . gmp_strval($keyPair['privateKey'], 16) . "\n";
echo 'Public key X: ' . gmp_strval($keyPair['publicKey']['x'], 16) . "\n";
echo 'Public key Y: ' . gmp_strval($keyPair['publicKey']['y'], 16) . "\n";
```

📖 **完整示例**: [examples/sm2-keypair.php](./examples/sm2-keypair.php)

### SM2 数字签名

```php
use SmBc\Crypto\SM2;

$keyPair = SM2::generateKeyPair();

// 签名
$message = 'Hello, SM2!';
$signature = SM2::sign($message, $keyPair['privateKey']);

// 验签
$isValid = SM2::verify($message, $signature, $keyPair['publicKey']);
echo 'Signature valid: ' . ($isValid ? 'true' : 'false');
```

📖 **完整示例**: [examples/sm2-sign.php](./examples/sm2-sign.php)

### SM2 公钥加密

```php
use SmBc\Crypto\SM2;

$keyPair = SM2::generateKeyPair();

// 加密
$plaintext = 'Secret message';
$ciphertext = SM2::encrypt($plaintext, $keyPair['publicKey']);

// 解密
$decrypted = SM2::decrypt($ciphertext, $keyPair['privateKey']);
echo 'Decrypted: ' . $decrypted;
```

📖 **完整示例**: [examples/sm2-encrypt.php](./examples/sm2-encrypt.php)

### SM4 对称加密

```php
use SmBc\Crypto\SM4;

// 生成密钥并加密
$key = SM4::generateKey();
$plaintext = 'Hello, SM4!';
$ciphertext = SM4::encrypt($plaintext, $key);

// 解密
$decrypted = SM4::decrypt($ciphertext, $key);
echo 'Decrypted: ' . $decrypted;
```

> ⚠️ **安全提示**: 上述示例使用 ECB 模式，仅用于演示。生产环境请使用 CBC、CTR 或 GCM 模式。

📖 **完整示例**: 
- [examples/sm4-ecb-simple.php](./examples/sm4-ecb-simple.php) - 基础加密示例
- [examples/sm4-modes.php](./examples/sm4-modes.php) - 多种工作模式（ECB/CBC/CTR/GCM）

### SM2 密钥交换

```php
use SmBc\Crypto\SM2;
use SmBc\Crypto\Agreement\SM2KeyExchange;
use SmBc\Crypto\Params\SM2KeyExchangePrivateParameters;
use SmBc\Crypto\Params\SM2KeyExchangePublicParameters;
use SmBc\Crypto\Params\ECPrivateKeyParameters;
use SmBc\Crypto\Params\ECPublicKeyParameters;

// 获取SM2域参数
$domainParams = SM2::getParameters();
$curve = $domainParams->getCurve();

// Alice 生成密钥对（静态 + 临时）
$aliceStatic = SM2::generateKeyPair();
$aliceEphemeral = SM2::generateKeyPair();

$aliceStaticPriv = new ECPrivateKeyParameters($aliceStatic['privateKey'], $domainParams);
$aliceStaticPubPoint = $curve->createPoint($aliceStatic['publicKey']['x'], $aliceStatic['publicKey']['y']);
$aliceStaticPub = new ECPublicKeyParameters($aliceStaticPubPoint, $domainParams);

$aliceEphemeralPriv = new ECPrivateKeyParameters($aliceEphemeral['privateKey'], $domainParams);
$aliceEphemeralPubPoint = $curve->createPoint($aliceEphemeral['publicKey']['x'], $aliceEphemeral['publicKey']['y']);
$aliceEphemeralPub = new ECPublicKeyParameters($aliceEphemeralPubPoint, $domainParams);

// Bob 生成密钥对（静态 + 临时）
$bobStatic = SM2::generateKeyPair();
$bobEphemeral = SM2::generateKeyPair();

$bobStaticPriv = new ECPrivateKeyParameters($bobStatic['privateKey'], $domainParams);
$bobStaticPubPoint = $curve->createPoint($bobStatic['publicKey']['x'], $bobStatic['publicKey']['y']);
$bobStaticPub = new ECPublicKeyParameters($bobStaticPubPoint, $domainParams);

$bobEphemeralPriv = new ECPrivateKeyParameters($bobEphemeral['privateKey'], $domainParams);
$bobEphemeralPubPoint = $curve->createPoint($bobEphemeral['publicKey']['x'], $bobEphemeral['publicKey']['y']);
$bobEphemeralPub = new ECPublicKeyParameters($bobEphemeralPubPoint, $domainParams);

// Alice 初始化密钥交换（发起方）
$aliceExchange = new SM2KeyExchange();
$alicePrivParams = new SM2KeyExchangePrivateParameters(
    true,  // initiator
    $aliceStaticPriv,
    $aliceEphemeralPriv
);
$aliceExchange->init($alicePrivParams);

// Alice 计算共享密钥
$bobPubParams = new SM2KeyExchangePublicParameters($bobStaticPub, $bobEphemeralPub);
$aliceSharedKey = $aliceExchange->calculateKey(128, $bobPubParams);

// Bob 初始化密钥交换（响应方）
$bobExchange = new SM2KeyExchange();
$bobPrivParams = new SM2KeyExchangePrivateParameters(
    false,  // responder
    $bobStaticPriv,
    $bobEphemeralPriv
);
$bobExchange->init($bobPrivParams);

// Bob 计算共享密钥
$alicePubParams = new SM2KeyExchangePublicParameters($aliceStaticPub, $aliceEphemeralPub);
$bobSharedKey = $bobExchange->calculateKey(128, $alicePubParams);

// 验证双方密钥一致
echo 'Keys match: ' . ($aliceSharedKey === $bobSharedKey ? 'true' : 'false');
```

> 💡 **提示**: SM2 密钥交换涉及多个参数类和步骤，建议查看完整示例了解详细用法。

📖 **完整示例**: [examples/sm2-keyexchange.php](./examples/sm2-keyexchange.php)

---

## 📚 完整示例

所有算法都提供了完整的可运行示例，位于 [`examples`](./examples) 目录：

| 示例文件 | 说明 | 演示内容 |
|---------|------|---------|
| [sm3-hash.php](./examples/sm3-hash.php) | SM3 哈希计算 | 基本哈希、分段更新、空数据处理 |
| [sm2-keypair.php](./examples/sm2-keypair.php) | SM2 密钥对生成 | 生成密钥对、查看公私钥 |
| [sm2-sign.php](./examples/sm2-sign.php) | SM2 数字签名 | 签名、验签、错误验证 |
| [sm2-encrypt.php](./examples/sm2-encrypt.php) | SM2 公钥加密 | 加密、解密、不同长度消息 |
| [sm2-keyexchange.php](./examples/sm2-keyexchange.php) | SM2 密钥交换 | ECDH 协议、密钥协商 |
| [sm4-ecb-simple.php](./examples/sm4-ecb-simple.php) | SM4 基础加密 | ECB 模式、PKCS7 填充 |
| [sm4-modes.php](./examples/sm4-modes.php) | SM4 多种模式 | ECB/CBC/CTR/GCM 对比 |

### 🚀 运行示例

```bash
# 运行单个示例
php examples/sm3-hash.php         # SM3 哈希
php examples/sm2-keypair.php      # SM2 密钥对生成
php examples/sm2-sign.php         # SM2 数字签名
php examples/sm2-encrypt.php      # SM2 公钥加密
php examples/sm2-keyexchange.php  # SM2 密钥交换
php examples/sm4-ecb-simple.php   # SM4 基础加密
php examples/sm4-modes.php        # SM4 多种模式

# 运行所有示例 (Bash/Zsh)
for file in examples/*.php; do echo "=== Running $file ===" && php "$file" && echo; done

# 运行所有示例 (PowerShell)
Get-ChildItem examples\*.php | ForEach-Object { Write-Host "=== Running $($_.Name) ===" -ForegroundColor Green; php $_.FullName; Write-Host }
```

详细说明请查看 [examples/README.md](./examples/README.md)。

## 📖 文档

详细文档请查看 [docs](./docs) 目录：

- **[文档导航](./docs/README.md)** - 所有文档的入口
- **[实现计划](./docs/IMPLEMENTATION_PLAN.md)** - 技术架构和实现计划
- **[完整总结](./FINAL_COMPLETE_SUMMARY.md)** - 项目完整总结

## 🧪 测试

本项目包含完整的单元测试套件，确保代码质量和正确性。

### 测试覆盖

- ✅ 126+ 单元测试
- ✅ 400+ 断言
- ✅ 100% 通过率
- ✅ 覆盖所有核心功能

### 运行测试

```bash
# 运行所有测试
vendor/bin/phpunit

# 运行特定测试类
vendor/bin/phpunit tests/Crypto/SM3DigestTest.php
vendor/bin/phpunit tests/Crypto/SM2EngineTest.php
vendor/bin/phpunit tests/Crypto/SM4EngineTest.php

# 带详细输出
vendor/bin/phpunit --verbose

# 生成测试覆盖率报告
vendor/bin/phpunit --coverage-html coverage
```

## 🏗️ 项目结构

```
sm-php-bc/
├── src/                    # 源代码
│   ├── Crypto/            # 密码学算法
│   │   ├── Digests/       # 摘要算法（SM3）
│   │   ├── Engines/       # 加密引擎（SM2、SM4）
│   │   ├── Signers/       # 签名算法（SM2）
│   │   ├── Agreement/     # 密钥交换
│   │   ├── Modes/         # 加密模式（ECB、CBC、CTR、GCM）
│   │   ├── Paddings/      # 填充方案
│   │   └── Params/        # 参数类
│   ├── Math/              # 数学运算
│   │   ├── EC/            # 椭圆曲线
│   │   └── Field/         # 有限域
│   └── Util/              # 工具类
├── tests/                 # 测试
├── examples/              # 示例代码
├── docs/                  # 文档
└── vendor/                # 依赖
```

## 🔧 开发

### 环境要求

- PHP >= 8.1
- ext-gmp 扩展
- Composer

### 开发流程

```bash
# 克隆项目
git clone <repository-url>
cd sm-php-bc

# 安装依赖
composer install

# 运行测试
vendor/bin/phpunit

# 代码风格检查（如果配置了）
composer run-script lint

# 运行示例
php examples/sm3-hash.php
```

## 🤝 贡献

欢迎贡献！请遵循以下步骤：

1. Fork 本项目
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'feat: Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

请确保：
- ✅ 所有测试通过
- ✅ 遵循 PSR-12 编码规范
- ✅ 更新相关文档

## 📜 许可证

[MIT License](./LICENSE)

## 🔗 相关链接

- [Bouncy Castle Java](https://github.com/bcgit/bc-java) - 参考实现
- [sm-js-bc](../sm-js-bc) - TypeScript 版本
- [GM/T 0003-2012](http://www.gmbz.org.cn/) - SM2 标准
- [GM/T 0004-2012](http://www.gmbz.org.cn/) - SM3 标准
- [GM/T 0002-2012](http://www.gmbz.org.cn/) - SM4 标准

## 🙏 致谢

- Bouncy Castle 项目提供了优秀的参考实现
- 所有为国密算法标准化做出贡献的专家学者

## ❓ 常见问题

### 为什么要实现这个库？

为了在 PHP 生态中提供一个与 Bouncy Castle Java 完全兼容的 SM2/SM3/SM4 实现，确保跨语言互操作性。

### 与其他 PHP SM2/SM3 库的区别？

- ✅ 基于 Bouncy Castle Java 一比一复刻，保证兼容性
- ✅ 纯 PHP 实现，无需 OpenSSL 扩展
- ✅ 完整的类型声明和文档
- ✅ 全面的单元测试覆盖

### 性能如何？

PHP 8.1+ 的 JIT 编译器显著提升了性能。对于加密算法这类计算密集型任务，性能已经非常接近原生扩展。

### 可以在生产环境使用吗？

可以！本项目已经：
- ✅ 通过 126+ 单元测试
- ✅ 遵循 PSR-12 编码规范
- ✅ 提供完整的错误处理
- ✅ 包含详细的文档和示例

---

**如有问题或建议，欢迎提出 [Issue](../../issues) 或 [Pull Request](../../pulls)！**
