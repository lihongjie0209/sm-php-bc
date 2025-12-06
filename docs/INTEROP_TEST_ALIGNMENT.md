# 跨语言互操作测试对齐分析

## 概述

本文档对比 sm-js-bc 的 Java 跨语言测试和 sm-php-bc 的 PHP 跨语言测试，确保两者测试用例完全对齐。

## 测试文件对比

### sm-js-bc (Java 测试)
位置: `test/graalvm-integration/java/src/test/java/com/sm/bc/graalvm/`

文件:
1. `SM3DigestInteropTest.java` - SM3 哈希算法测试
2. `SM2SignatureInteropTest.java` - SM2 签名测试
3. `SM2EncryptionInteropTest.java` - SM2 加密测试
4. `SM4CipherInteropTest.java` - SM4 密码测试（所有模式）
5. `SimplifiedCrossLanguageTest.java` - 简化的跨语言测试（使用 Node.js）

### sm-php-bc (PHP 测试)
位置: `tests/CrossLanguage/`

文件:
1. `SM3DigestInteropTest.php` - SM3 哈希算法测试 ✅
2. `SM2SignatureInteropTest.php` - SM2 签名测试 ✅
3. `SM4CipherInteropTest.php` - SM4 密码测试 ⚠️ (需要扩展)
4. `BaseInteropTest.php` - 基础测试类

## 详细测试用例对比

### 1. SM3 哈希测试

#### JS版本测试用例:
- ✅ 空字符串
- ✅ 单字符 "a"
- ✅ 标准测试 "abc"
- ✅ 常规消息
- ✅ 中文字符
- ✅ 标准测试向量验证
- ✅ 性能对比测试

#### PHP版本测试用例:
- ✅ 空字符串
- ✅ 单字符 "a"
- ✅ 标准测试 "abc"
- ✅ 随机数据测试

**状态**: ✅ 基本对齐，PHP版本已包含主要测试场景

---

### 2. SM2 签名测试

#### JS版本测试用例:
- Java sign → JavaScript verify
- JavaScript sign → Java verify
- 密钥格式兼容性
- 边界情况和错误处理

#### PHP版本测试用例:
- PHP sign → JavaScript verify
- JavaScript sign → PHP verify
- 随机数据测试

**状态**: ✅ 基本对齐

---

### 3. SM2 加密测试

#### JS版本测试用例:
- Java encrypt → JavaScript decrypt
- JavaScript encrypt → Java decrypt
- 各种消息大小
- 无效密文错误处理

#### PHP版本测试用例:
- 需要添加

**状态**: ❌ 缺失，需要实现

---

### 4. SM4 密码测试

#### JS版本测试用例:

**ECB 模式:**
- ✅ 空输入（带填充）
- ✅ 单块（16字节）
- ✅ 多块（32字节）
- ✅ 各种明文大小

**CBC 模式:**
- ✅ 单块（带IV）
- ✅ 多块（带IV）
- ✅ 各种明文大小

**CTR 模式:**
- ✅ 流模式（无填充）
- ✅ 各种明文大小

**GCM 模式:**
- ✅ 16字节明文的AEAD
- ✅ 32字节明文的AEAD
- ✅ 空明文（带AAD）
- ✅ 各种配置
- ✅ MAC验证失败检测

**其他:**
- ✅ 随机数据一致性测试

#### PHP版本测试用例:
- ✅ CBC模式基础测试
- ❌ ECB模式（缺失）
- ❌ CTR模式（缺失）
- ❌ GCM模式（缺失）
- ❌ 各种明文大小参数化测试（缺失）

**状态**: ⚠️ 部分实现，需要扩展

---

## 需要补充的测试

### 高优先级

1. **SM2EncryptionInteropTest.php** - 完全缺失
   - Java encrypt → JavaScript decrypt
   - JavaScript encrypt → Java decrypt
   - 各种消息大小测试
   - 错误处理测试

2. **SM4CipherInteropTest.php 扩展**
   - ECB 模式完整测试
   - CTR 模式完整测试
   - GCM 模式完整测试
   - 参数化测试（各种大小）

### 中优先级

3. **SimplifiedCrossLanguageTest.php** (可选)
   - 使用 Node.js 的简化测试
   - 不需要 GraalVM 设置

### 低优先级

4. **ParameterizedInteropTest.php** (可选)
   - 大规模参数化测试
   - 属性化测试

---

## 实现建议

### 1. SM2 加密测试

```php
class SM2EncryptionInteropTest extends BaseInteropTest
{
    public function testPhpEncryptJsDecrypt(): void
    {
        // 测试 PHP 加密 → JS 解密
    }
    
    public function testJsEncryptPhpDecrypt(): void
    {
        // 测试 JS 加密 → PHP 解密
    }
    
    /**
     * @dataProvider messageSizeProvider
     */
    public function testVariousMessageSizes(string $message): void
    {
        // 测试各种消息大小
    }
}
```

### 2. SM4 扩展测试

```php
class SM4CipherInteropTest extends BaseInteropTest
{
    // ECB 模式
    public function testECBEmpty(): void { }
    public function testECBSingleBlock(): void { }
    public function testECBMultiBlock(): void { }
    
    // CTR 模式
    public function testCTRNoPadding(): void { }
    
    // GCM 模式
    public function testGCMWithAAD(): void { }
    public function testGCMMACVerification(): void { }
    
    /**
     * @dataProvider plaintextSizeProvider
     */
    public function testVariousSizes(array $plaintext, string $mode): void
    {
        // 参数化测试
    }
}
```

---

## 执行计划

### 阶段 1: 基础对齐 (1-2天)
- [x] SM3 测试对齐验证
- [x] SM2 签名测试对齐验证
- [ ] 添加 SM2 加密测试
- [ ] 扩展 SM4 测试（ECB, CTR）

### 阶段 2: 完整对齐 (2-3天)
- [ ] 添加 SM4 GCM 测试
- [ ] 添加参数化测试
- [ ] 添加错误处理测试
- [ ] 性能对比测试

### 阶段 3: CI/CD 集成 (1天)
- [ ] 更新 GitHub Actions workflow
- [ ] 添加跨语言测试到 CI 流水线
- [ ] 生成测试报告

---

## 当前状态总结

| 测试类别 | JS版本 | PHP版本 | 对齐状态 | 优先级 |
|---------|--------|---------|---------|--------|
| SM3 Digest | ✅ 完整 | ✅ 完整 | ✅ 已对齐 | - |
| SM2 Signature | ✅ 完整 | ✅ 完整 | ✅ 已对齐 | - |
| SM2 Encryption | ✅ 完整 | ❌ 缺失 | ❌ 未对齐 | 高 |
| SM4-ECB | ✅ 完整 | ❌ 缺失 | ❌ 未对齐 | 高 |
| SM4-CBC | ✅ 完整 | ✅ 基础 | ⚠️ 部分对齐 | 中 |
| SM4-CTR | ✅ 完整 | ❌ 缺失 | ❌ 未对齐 | 高 |
| SM4-GCM | ✅ 完整 | ❌ 缺失 | ❌ 未对齐 | 高 |

**总体对齐度**: 约 40%

**下一步行动**: 
1. 实现 SM2 加密互操作测试
2. 扩展 SM4 测试覆盖所有模式
3. 添加参数化测试支持

---

*最后更新: 2025-12-06*
