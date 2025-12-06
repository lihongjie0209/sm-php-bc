# 跨语言互操作测试指南

sm-php-bc 与 sm-js-bc 的跨语言兼容性测试文档

---

## 📖 快速导航

- [测试概述](#测试概述)
- [环境要求](#环境要求)
- [运行测试](#运行测试)
- [测试覆盖](#测试覆盖)
- [添加新测试](#添加新测试)

---

## 测试概述

跨语言互操作测试确保 **sm-php-bc** (PHP) 和 **sm-js-bc** (JavaScript) 两个实现之间完全兼容，可以互相加密/解密、签名/验证数据。

### 测试方法

```
PHP 实现 ←→ JavaScript 实现 (via Node.js)
    ↓               ↓
  加密/签名      解密/验证
  解密/验证      加密/签名
```

### 测试原理

1. PHP 测试调用 JavaScript 实现 (通过 Node.js)
2. 使用 npm 包 `sm-js-bc` (不依赖源码)
3. 验证两个实现产生相同的结果
4. 确保双向互操作性

---

## 环境要求

### 必需

- ✅ PHP 8.1+ (已安装)
- ✅ Composer (已安装)
- ✅ **Node.js 16+** (用于执行 JavaScript)
- ✅ **npm** (用于安装 sm-js-bc)

### 安装依赖

```bash
# 1. 安装 PHP 依赖
composer install

# 2. 安装 Node.js 依赖
npm install sm-js-bc

# 3. 验证环境
node --version
php --version
```

---

## 运行测试

### 运行所有跨语言测试

```bash
./vendor/bin/phpunit tests/CrossLanguage --testdox
```

### 运行特定测试

```bash
# SM3 哈希测试
./vendor/bin/phpunit tests/CrossLanguage/SM3DigestInteropTest.php

# SM2 签名测试
./vendor/bin/phpunit tests/CrossLanguage/SM2SignatureInteropTest.php

# SM2 加密测试
./vendor/bin/phpunit tests/CrossLanguage/SM2EncryptionInteropTest.php

# SM4 密码测试
./vendor/bin/phpunit tests/CrossLanguage/SM4CipherInteropTest.php
```

### 带详细输出

```bash
./vendor/bin/phpunit tests/CrossLanguage --testdox --verbose
```

---

## 测试覆盖

### ✅ 已实现

| 算法 | 测试场景 | 状态 |
|------|---------|------|
| **SM3** | 哈希计算互操作 | ✅ 完成 |
| **SM2** | 签名/验证互操作 | ✅ 完成 |
| **SM2** | 加密/解密互操作 | ✅ 完成 |
| **SM4-CBC** | CBC 模式互操作 | ✅ 完成 |

### ⏳ 进行中

| 算法 | 测试场景 | 状态 |
|------|---------|------|
| **SM4-ECB** | ECB 模式互操作 | 🔄 待实现 |
| **SM4-CTR** | CTR 模式互操作 | 🔄 待实现 |
| **SM4-GCM** | GCM 模式互操作 | 🔄 待实现 |

### 对齐度

```
当前: 70%
目标: 95%+
```

---

## 测试示例

### SM3 哈希

```php
// PHP 计算哈希
$digest = new SM3Digest();
$digest->updateArray($message, 0, count($message));
$phpHash = $digest->doFinal($result, 0);

// JavaScript 计算哈希 (via Node.js)
$jsHash = executeNodeScript("
    import { SM3Digest } from 'sm-js-bc';
    const digest = new SM3Digest();
    // ... 计算哈希
");

// 验证结果一致
assertEquals($phpHash, $jsHash);
```

### SM2 签名验证

```php
// PHP 签名
$signer = new SM2Signer();
$signature = $signer->generateSignature($message, $privateKey);

// JavaScript 验证 (via Node.js)
$verified = executeNodeScript("
    import { SM2Signer } from 'sm-js-bc';
    const signer = new SM2Signer();
    return signer.verifySignature(message, signature, publicKey);
");

// 验证通过
assertTrue($verified);
```

---

## 添加新测试

### 1. 创建测试类

```php
namespace Rtgm\SmPhpBc\Tests\CrossLanguage;

class MyNewInteropTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // 检查依赖
        if (!file_exists(__DIR__ . '/../../node_modules/sm-js-bc')) {
            self::markTestSkipped('sm-js-bc not installed');
        }
    }
    
    public function testMyFeature(): void
    {
        // 测试实现
    }
}
```

### 2. 实现测试方法

```php
public function testPhpToJsInterop(): void
{
    // 1. PHP 处理
    $result = $this->processWithPhp($data);
    
    // 2. JavaScript 验证
    $jsResult = $this->processWithJavaScript($data);
    
    // 3. 断言
    $this->assertEquals($result, $jsResult);
}
```

### 3. 创建 Node.js 脚本

```php
private function createTestScript(string $data): string
{
    return <<<JS
import { MyAlgorithm } from 'sm-js-bc';

try {
    // 处理逻辑
    const result = MyAlgorithm.process("$data");
    console.log(JSON.stringify({ result }));
} catch (error) {
    console.log(JSON.stringify({ error: error.message }));
    process.exit(1);
}
JS;
}
```

### 4. 执行脚本

```php
private function executeNodeScript(string $script): string
{
    $scriptPath = sys_get_temp_dir() . '/test_' . uniqid() . '.mjs';
    file_put_contents($scriptPath, $script);
    
    try {
        $output = shell_exec("node \"$scriptPath\" 2>&1");
        $result = json_decode(trim($output), true);
        return $result['result'] ?? throw new \Exception($result['error']);
    } finally {
        @unlink($scriptPath);
    }
}
```

---

## 故障排查

### Node.js 不可用

```
Error: Node.js is not available
```

**解决方案**: 安装 Node.js 16+

```bash
# 检查
node --version

# 如未安装，访问 https://nodejs.org/
```

### sm-js-bc 未安装

```
Error: sm-js-bc package is not installed
```

**解决方案**: 安装 npm 包

```bash
npm install sm-js-bc
```

### 测试超时

```
Error: Script execution timeout
```

**解决方案**: 增加超时时间

```php
set_time_limit(60); // 60 秒
```

### 脚本执行失败

```
Error: Failed to execute Node.js script
```

**调试步骤**:

1. 检查脚本语法
2. 手动运行脚本
3. 查看错误输出

```bash
# 手动测试
node /tmp/test_script.mjs
```

---

## CI/CD 集成

### GitHub Actions

```yaml
name: Cross-Language Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install PHP dependencies
        run: composer install
      
      - name: Install Node.js dependencies
        run: npm install sm-js-bc
      
      - name: Run Cross-Language Tests
        run: ./vendor/bin/phpunit tests/CrossLanguage --testdox
```

---

## 性能注意事项

### 进程开销

每次 Node.js 调用都有进程启动开销（~50-100ms）

**优化建议**:

1. 批量测试数据
2. 减少测试迭代
3. 使用参数化测试

### 测试速度对比

```
PHP 单次操作:  1-5 ms
Node.js 调用:  50-100 ms (包含启动)
```

---

## 相关文档

- 📄 [测试对齐分析](./INTEROP_TEST_ALIGNMENT.md)
- 📄 [工作会话记录](./SESSION_2025-12-06_INTEROP.md)
- 📄 [完成工作总结](./WORK_COMPLETED_2025-12-06.md)
- 📄 [项目开发指南](./INSTRUCTION.md)

---

## 贡献指南

### 添加新的互操作测试

1. 在 `tests/CrossLanguage/` 创建测试文件
2. 继承 `TestCase` 或使用 `BaseInteropTest`
3. 实现双向测试（PHP→JS 和 JS→PHP）
4. 添加参数化测试覆盖各种场景
5. 更新本文档

### 代码规范

- 使用 PSR-12 编码标准
- 添加 PHPDoc 注释
- 包含测试场景说明
- 优雅处理错误

---

## 联系方式

- 📦 **项目**: sm-php-bc
- 🔗 **相关项目**: sm-js-bc (JavaScript 实现)
- 📧 **反馈**: 通过 GitHub Issues

---

*最后更新: 2025-12-06*
