# 任务：对齐 sm-js-bc v0.4.0 功能

**创建日期**: 2025-12-08
**参考版本**: https://github.com/lihongjie0209/sm-js-bc/tree/v0.4.0

## 📋 任务概述

将 sm-php-bc 的功能与 sm-js-bc v0.4.0 版本对齐，主要包括 API 一致性改进和新增测试套件。

## 🎯 主要目标

1. ✅ 实现 API 一致性改进，与 Bouncy Castle Java 保持一致
2. ✅ 添加 API 兼容性测试套件
3. ✅ 更新文档说明新增功能
4. ✅ 确保所有测试通过

## 📊 当前状态分析

### PHP 版本现状 (v0.1.1)
- ✅ SM2/SM3/SM4 核心功能完整
- ✅ 跨语言互操作测试已实现
- ✅ 基础 API 已实现
- ⚠️ 部分 API 一致性特性缺失
- ❌ 缺少 HMAC-SM3
- ❌ 缺少 PKI 支持 (X.509, PKCS#8, PKCS#10, ASN.1)
- ❌ 缺少 ZUC 流密码算法

### JS 版本 v0.4.0 新增内容（完整列表）

根据 git log 和源码分析，v0.4.0 相对于 v0.3.0 新增以下内容：

#### 1. API 一致性改进（对齐 Bouncy Castle Java）
- ✅ `SM3Digest.reset()` 无参数版本 - PHP已实现
- ✅ `SM3Digest.reset(Memoable)` 方法重载 - PHP已实现
- ❌ `SM2Engine.Mode` 静态属性（Java风格枚举访问）- **需要添加**
- ❌ `SM2Signer.createBasePointMultiplier()` 受保护方法 - **需要检查/添加**
- ❌ `SM2Signer.calculateE()` 受保护方法 - **需要检查/添加**
- ⚠️ `SM2Signer.hashToInteger()` 标记为已弃用 - **需要检查**
- ❌ API 兼容性测试套件 - **需要创建**

#### 2. HMAC-SM3 实现（新增功能）
- ❌ `HMac` 类 - **需要实现**
- ❌ `Mac` 接口 - **需要实现**
- ❌ HMAC-SM3 测试套件 - **需要创建**
- ❌ HMAC-SM3 示例代码 - **需要创建**

#### 3. PKI 支持（新增大功能模块）

##### 3.1 ASN.1 编码/解码
- ❌ `ASN1BitString` - **需要实现**
- ❌ `ASN1Integer` - **需要实现**
- ❌ `ASN1ObjectIdentifier` - **需要实现**
- ❌ `ASN1OctetString` - **需要实现**
- ❌ `ASN1Sequence` - **需要实现**
- ❌ `DEREncoder` / `DERDecoder` - **需要实现**
- ❌ `GMObjectIdentifiers` - **需要实现**

##### 3.2 PEM 编码
- ❌ `PemReader` - **需要实现**
- ❌ `PemWriter` - **需要实现**
- ❌ `PemObject` - **需要实现**

##### 3.3 PKCS#8 密钥编码
- ❌ `PrivateKeyInfo` - **需要实现**
- ❌ `SubjectPublicKeyInfo` - **需要实现**
- ❌ `SM2PrivateKeyEncoder` - **需要实现**
- ❌ `SM2PublicKeyEncoder` - **需要实现**

##### 3.4 PKCS#10 CSR（证书签名请求）
- ❌ `PKCS10CertificationRequest` - **需要实现**
- ❌ `PKCS10CertificationRequestBuilder` - **需要实现**

##### 3.5 X.509 证书支持
- ❌ `X509Certificate` - **需要实现**
- ❌ `X509CertificateBuilder` - **需要实现**
- ❌ `X509Name` - **需要实现**
- ❌ `X509Extensions` - **需要实现**
- ❌ `TBSCertificate` - **需要实现**
- ❌ `Validity` - **需要实现**
- ❌ `SubjectAlternativeName` - **需要实现**
- ❌ `CertificateList` (CRL) - **需要实现**
- ❌ `CertPathValidator` - **需要实现**

#### 4. ZUC 流密码算法（新增功能）
- ❌ `ZUCEngine` (ZUC-128) - **需要实现**
- ❌ `Zuc256Engine` (ZUC-256) - **需要实现**
- ❌ `Zuc128Mac` - **需要实现**
- ❌ `Zuc256Mac` - **需要实现**
- ❌ ZUC 测试套件 - **需要创建**
- ❌ ZUC 示例代码 - **需要创建**

## 🔧 详细实现计划

> **重要决策**: 考虑到 v0.4.0 新增了大量功能（HMAC、PKI、ZUC），本次对齐将分为两个版本：
> - **v0.2.0**: API 一致性改进（本次实现）
> - **v0.3.0-v0.5.0**: HMAC、PKI、ZUC 等新功能（后续规划）

### 阶段 1: 代码分析和准备 ✅
- [x] 克隆并分析 sm-js-bc v0.4.0 源码
- [x] 阅读 CHANGELOG.md 和 API 文档
- [x] 分析所有 git commit 历史
- [x] 识别完整的新增功能列表
- [x] 检查 PHP 现有实现状态
- [x] 创建此任务文档
- [x] 确定实现优先级和版本规划

### 阶段 2: API 一致性改进（v0.2.0 范围）

#### 任务 2.1: SM2Engine Mode 支持
- [ ] 添加 SM2Engine::Mode 常量数组
- [ ] 保持向后兼容性
- [ ] 更新文档说明用法

#### 任务 2.2: SM2Signer 方法改进
- [ ] 检查并实现 createBasePointMultiplier() 受保护方法
- [ ] 检查并实现 calculateE() 受保护方法
- [ ] 标记 hashToInteger() 为已弃用
- [ ] 确保方法可被子类覆盖

### 阶段 3: API 兼容性测试套件
- [ ] **任务 3.1**: 创建 tests/Unit/APICompatibilityTest.php
  - [ ] SM3Digest::reset() 方法重载测试
  - [ ] SM2Engine::Mode 静态常量访问测试
  - [ ] SM2Signer 受保护方法可访问性测试
  - [ ] 类型兼容性测试
  - [ ] API 方法命名一致性测试

### 阶段 4: 文档更新（v0.2.0）
- [ ] **任务 4.1**: 更新 CHANGELOG.md
  - [ ] 添加 v0.2.0 版本说明
  - [ ] 列出 API 一致性改进
  - [ ] 标记已弃用的方法
  
- [ ] **任务 4.2**: 更新 README.md
  - [ ] 添加 API 兼容性说明
  - [ ] 更新版本徽章
  
- [ ] **任务 4.3**: 创建 docs/API_IMPROVEMENTS.md
  - [ ] 翻译 JS 版本的改进文档
  - [ ] 添加 PHP 特定的使用示例
  
- [ ] **任务 4.4**: 更新 COMPLETED_FEATURES.md
  - [ ] 更新 API 一致性状态

### 阶段 5: 测试和验证
- [ ] **任务 5.1**: 运行所有单元测试
  - [ ] 确保现有测试通过
  - [ ] 确保新测试通过
  
- [ ] **任务 5.2**: 运行跨语言互操作测试
  - [ ] 验证与 sm-js-bc v0.4.0 的兼容性
  
- [ ] **任务 5.3**: 代码审查和安全检查
  - [ ] 运行 code_review 工具
  - [ ] 运行 codeql_checker
  - [ ] 检查代码风格

### 阶段 6: 后续功能规划（v0.3.0+）

#### v0.3.0: HMAC-SM3 实现（计划中）
- [ ] 实现 Mac 接口
- [ ] 实现 HMac 类
- [ ] 添加测试和示例

#### v0.4.0: PKI 基础支持（计划中）
- [ ] 实现 ASN.1 编码/解码
- [ ] 实现 PEM 读写
- [ ] 实现 PKCS#8 密钥编码
- [ ] 添加测试和示例

#### v0.5.0: X.509 证书支持（计划中）
- [ ] 实现 X.509 证书类
- [ ] 实现证书构建器
- [ ] 实现证书验证
- [ ] 实现 CRL 支持
- [ ] 添加测试和示例

#### v0.6.0: ZUC 流密码（计划中）
- [ ] 实现 ZUC-128/256 引擎
- [ ] 实现 ZUC MAC
- [ ] 添加测试和示例

## 📈 预期成果

### 代码变更
- SM2Engine.php: 添加 Mode 静态属性支持
- SM2Signer.php: 添加/优化受保护方法
- tests/Unit/APICompatibilityTest.php: 新建测试文件

### 文档变更
- CHANGELOG.md: 新增 v0.2.0 版本记录
- README.md: 更新特性说明
- docs/API_IMPROVEMENTS.md: 新建文档
- COMPLETED_FEATURES.md: 更新完成状态

### 测试结果
- 所有单元测试通过 (包括新增的 API 兼容性测试)
- 跨语言互操作测试通过
- 代码覆盖率保持或提高

## 📚 参考资料

- sm-js-bc v0.4.0: https://github.com/lihongjie0209/sm-js-bc/tree/v0.4.0
- CHANGELOG: /tmp/sm-js-bc/CHANGELOG.md
- API_CONSISTENCY_AUDIT: /tmp/sm-js-bc/docs/API_CONSISTENCY_AUDIT.md
- API_IMPROVEMENTS: /tmp/sm-js-bc/docs/API_IMPROVEMENTS.md
- API Compatibility Tests: /tmp/sm-js-bc/test/unit/crypto/APICompatibility.test.ts

## 🔄 进度跟踪

- **开始日期**: 2025-12-08
- **当前阶段**: 阶段 1 (代码分析和准备) ✅ → 阶段 2 (API 一致性改进) 🚧
- **本次版本**: v0.2.0 (API 一致性改进)
- **总体进度**: 15% (阶段 1 完成)
- **预计完成**: 2025-12-08 (v0.2.0)

## 📝 工作日志

### 2025-12-08 10:03 - 10:15
- ✅ 创建任务文档
- ✅ 克隆并分析 sm-js-bc v0.4.0 完整源码
- ✅ 分析所有 git commit 历史 (v0.3.0 到 v0.4.0)
- ✅ 识别完整的功能变更列表：
  - API 一致性改进
  - HMAC-SM3 (新增)
  - PKI 支持：ASN.1, PEM, PKCS#8, PKCS#10, X.509, CRL (大量新增)
  - ZUC 流密码算法 (新增)
- ✅ 制定分阶段实现计划
- ✅ 确定 v0.2.0 范围：专注于 API 一致性改进
- ✅ 开始实现 SM2Engine::Mode 支持

---

**注意**: 本文档需要在任务开始和完成时更新，以便后续开发参考。
