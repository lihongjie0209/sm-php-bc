# ZUC 流密码实现计划

**日期**: 2025-12-08
**状态**: 准备开始实现

## 📋 实现范围

### 1. ZUCEngine (核心算法)
**文件**: `src/Crypto/Engines/ZUCEngine.php`
**行数**: ~400 行
**复杂度**: ⭐⭐⭐⭐⭐

**包含内容**:
- 两个 S-盒（S0, S1）各 256 字节
- LFSR（线性反馈移位寄存器）16个31位单元
- 非线性函数 F
- 比特重组 BR
- 模 2^31-1 加法
- 初始化过程（32 轮）
- 密钥流生成

**关键方法**:
```php
- init(bool $forEncryption, CipherParameters $params): void
- getAlgorithmName(): string
- returnByte(int $input): int
- processBytes(string $input, int $inOff, int $length, string &$output, int $outOff): int
- reset(): void
- LFSRWithInitMode(int $u): void
- LFSRWithWorkMode(): void
- bitReorganization(): void
- nonlinearFunction(int $x0, int $x1, int $x2): int
- L1(int $x): int
- L2(int $x): int
- makeKeyStream(): void
```

### 2. Zuc128Mac (128位MAC)
**文件**: `src/Crypto/Macs/Zuc128Mac.php`
**行数**: ~200 行
**复杂度**: ⭐⭐⭐

**包含内容**:
- 基于 ZUCEngine
- 32位累加器
- T 函数（用于 MAC 计算）
- 支持增量更新

**关键方法**:
```php
- init(CipherParameters $params): void
- getMacSize(): int
- update(int $input): void
- updateArray(string $input, int $inOff, int $length): void
- doFinal(string &$output, int $outOff): int
- reset(): void
- getAlgorithmName(): string
```

### 3. Zuc256Mac (256位MAC)
**文件**: `src/Crypto/Macs/Zuc256Mac.php`
**行数**: ~210 行
**复杂度**: ⭐⭐⭐

**包含内容**:
- ZUC-256 变体
- 支持 32/64/128 位 MAC 输出
- 增强的安全性

## 🔧 技术挑战

### 1. 位操作
ZUC 算法需要大量精确的位操作：
- 31位模运算
- 循环左移（ROL）
- 位重组
- S-盒查找

### 2. 无符号整数
PHP 中整数运算的符号处理需要特别注意。

### 3. 性能优化
需要在正确性和性能之间平衡。

## 📝 实现步骤

### 第一阶段: ZUCEngine 核心
1. ✅ 创建 StreamCipher 接口（已完成）
2. ⏳ 实现 ZUCEngine 类
   - S-盒定义
   - LFSR 实现
   - 非线性函数
   - 初始化逻辑
   - 密钥流生成

### 第二阶段: 测试 ZUCEngine
1. 创建基础测试用例
2. 使用标准测试向量验证
3. 与 sm-js-bc 交叉验证

### 第三阶段: MAC 实现
1. 实现 Zuc128Mac
2. 实现 Zuc256Mac
3. 完整测试

### 第四阶段: 文档和示例
1. 添加使用示例
2. 更新文档
3. 添加性能说明

## ⏱️ 时间估计

- ZUCEngine: 2-3 小时
- Zuc128Mac: 1 小时
- Zuc256Mac: 1 小时
- 测试和调试: 1-2 小时
- 文档: 30 分钟

**总计**: 5-7 小时

## 🎯 实现策略

### 选项 A: 完整实现（推荐用于生产）
完全实现所有功能，包括所有优化和边界情况处理。

### 选项 B: 最小实现（快速原型）
实现核心功能，跳过某些优化，重点确保正确性。

### 选项 C: 渐进实现（当前）
分步骤实现，每个阶段都可独立测试和提交。

## 📚 参考资源

**标准文档**:
- GM/T 0001-2012 - ZUC 算法规范
- 3GPP TS 35.221 - ZUC 在 LTE 中的应用

**参考实现**:
- sm-js-bc/src/crypto/engines/ZUCEngine.ts
- org.bouncycastle.crypto.engines.ZucEngine

## ⚠️ 注意事项

1. **测试向量**: 确保使用官方测试向量验证
2. **位精度**: 31位 LFSR 需要特别注意
3. **性能**: 纯 PHP 实现性能可能不如原生扩展
4. **用途**: 主要用于 3GPP LTE/5G 加密场景

## 🚀 开始实现

准备从 ZUCEngine 核心开始实现...
