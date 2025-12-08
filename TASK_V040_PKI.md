# 任务：实现 v0.4.0 - PKI 基础支持

**创建日期**: 2025-12-08
**参考版本**: sm-js-bc v0.4.0

## 📋 任务概述

实现 PKI（公钥基础设施）的基础支持，包括 ASN.1 编码/解码、PEM 格式支持和 PKCS#8 密钥编码。

## 🎯 实现目标

### 阶段 1: ASN.1 基础类
1. ✅ ASN1Tags - ASN.1 标签常量
2. ✅ ASN1Encodable - ASN.1 编码接口
3. ✅ ASN1Integer - 整数类型
4. ✅ ASN1OctetString - 字节串类型
5. ✅ ASN1BitString - 比特串类型
6. ✅ ASN1ObjectIdentifier - 对象标识符
7. ✅ ASN1Sequence - 序列类型

### 阶段 2: DER 编解码器
1. ✅ DEREncoder - DER 编码器
2. ✅ DERDecoder - DER 解码器

### 阶段 3: 算法标识符
1. ✅ GMObjectIdentifiers - 国密算法 OID
2. ✅ AlgorithmIdentifier - 算法标识符

### 阶段 4: PEM 支持
1. ✅ PemObject - PEM 对象
2. ✅ PemReader - PEM 读取器
3. ✅ PemWriter - PEM 写入器

### 阶段 5: PKCS#8 密钥编码
1. ✅ PrivateKeyInfo - 私钥信息
2. ✅ SubjectPublicKeyInfo - 公钥信息
3. ✅ SM2PrivateKeyEncoder - SM2 私钥编码器
4. ✅ SM2PublicKeyEncoder - SM2 公钥编码器

## 🔧 实现策略

由于这是一个大型任务，我将采用以下策略：

1. **最小可用实现** - 先实现核心功能，确保可以编码/解码 SM2 密钥
2. **渐进式开发** - 分阶段实现，每个阶段都可以独立测试
3. **参考 sm-js-bc** - 保持 API 一致性
4. **简化设计** - PHP 版本可以简化某些不必要的复杂性

## 📚 参考实现

### sm-js-bc
- `src/asn1/` - ASN.1 类（11个文件）
- `src/pkcs/` - PKCS 类（6个文件）
- `src/util/io/pem/` - PEM 类（3个文件）

### Bouncy Castle Java
- `org.bouncycastle.asn1.*` - ASN.1 包
- `org.bouncycastle.pkcs.*` - PKCS 包
- `org.bouncycastle.util.io.pem.*` - PEM 包

## 🔄 进度跟踪

- **开始日期**: 2025-12-08
- **当前阶段**: 规划中
- **总体进度**: 0%

## 📝 工作日志

### 2025-12-08
- 创建任务文档
- 分析 sm-js-bc 的 PKI 实现
- 制定实现策略

## ⚠️ 注意事项和范围说明

PKI 支持是一个非常复杂的功能模块。完整实现包含：
- **20+ 个新类**
- **3000+ 行代码**
- **大量的测试用例**

这是 sm-js-bc 中最大的功能模块之一。完整实现需要：
- ASN.1 基础类（11个文件）
- PKCS 支持（6个文件）
- PEM 支持（3个文件）
- X.509 证书（9个文件）

### 建议的实现策略

**选项 1: 分步实现（推荐）**
- v0.4.0: 基础 ASN.1 + PEM + 简单的密钥编码
- v0.5.0: PKCS#8 完整支持
- v0.6.0: X.509 证书支持

**选项 2: 最小可用实现（快速）**
仅实现最核心的功能：
1. 基础 ASN.1 类（INTEGER, OCTET STRING, SEQUENCE）
2. 简单的 DER 编码/解码
3. SM2 密钥的 PEM 导入/导出

这样可以让用户快速使用 PEM 格式保存和加载 SM2 密钥，而不需要完整的 PKI 栈。

### 当前状态

由于 PKI 是一个大型功能，建议先确认：
1. 是否需要完整的 X.509 证书支持？
2. 还是只需要基础的密钥编码/解码？
3. 时间和资源的考虑

如继续实现，我将从最小可用实现开始，确保每个阶段都可以独立使用和测试。
