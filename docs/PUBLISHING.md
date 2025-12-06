# 发布到 Packagist 指南

## 前提条件

1. **GitHub 账户**: 确保你有 GitHub 账户
2. **Packagist 账户**: 在 [Packagist.org](https://packagist.org/) 注册账户
3. **GitHub Token**: 用于自动更新
4. **Git 配置**: 配置好 git 用户信息

## 发布步骤

### 1. 检查项目准备情况

```bash
# 确保所有测试通过
composer test

# 检查 composer.json 语法
composer validate

# 更新依赖
composer update
```

### 2. 创建 GitHub 仓库

```bash
# 初始化 Git（如果还没有）
git init

# 添加所有文件
git add .

# 提交
git commit -m "feat: 首次发布 v0.1.0"

# 使用 gh CLI 创建仓库并推送
gh repo create lihongjie0209/sm-php-bc --public --source=. --remote=origin --push

# 或者手动添加远程仓库
# git remote add origin https://github.com/lihongjie0209/sm-php-bc.git
# git branch -M main
# git push -u origin main
```

### 3. 创建发布标签

```bash
# 创建并推送标签
git tag -a v0.1.0 -m "Release version 0.1.0"
git push origin v0.1.0

# 使用 gh CLI 创建 release
gh release create v0.1.0 \
  --title "v0.1.0 - 首次发布" \
  --notes "首次发布，支持完整的 SM2/SM3/SM4 算法实现"
```

### 4. 提交到 Packagist

1. 访问 [Packagist.org](https://packagist.org/)
2. 登录你的账户
3. 点击 "Submit"
4. 输入 GitHub 仓库 URL: `https://github.com/lihongjie0209/sm-php-bc`
5. 点击 "Check"，然后 "Submit"

### 5. 设置自动更新（推荐）

#### 方法 1: GitHub Service Hook

1. 在 Packagist 项目页面，找到 API Token
2. 在 GitHub 仓库设置中：
   - Settings → Webhooks → Add webhook
   - Payload URL: `https://packagist.org/api/github?username=YOUR_USERNAME`
   - Content type: `application/json`
   - Secret: 你的 Packagist API Token
   - 选择 "Just the push event"

#### 方法 2: 使用 GitHub Action

项目已包含 `.github/workflows/packagist-sync.yml`，会自动同步到 Packagist。

需要在 GitHub 仓库设置中添加 Secret:
- `PACKAGIST_TOKEN`: 你的 Packagist API Token

### 6. 验证安装

```bash
# 在其他项目中测试安装
composer require lihongjie0209/sm-php-bc

# 或指定版本
composer require lihongjie0209/sm-php-bc:^0.1.0
```

## 后续版本发布

### 1. 更新版本号

编辑 `composer.json`:
```json
{
    "version": "0.2.0"
}
```

### 2. 更新 CHANGELOG.md

记录新版本的变更。

### 3. 提交更改

```bash
git add .
git commit -m "chore: bump version to 0.2.0"
git push
```

### 4. 创建新标签

```bash
git tag -a v0.2.0 -m "Release version 0.2.0"
git push origin v0.2.0

gh release create v0.2.0 \
  --title "v0.2.0" \
  --notes-file CHANGELOG.md
```

### 5. Packagist 自动更新

如果配置了 webhook，Packagist 会自动检测新标签并更新。

## 故障排除

### Composer 验证失败

```bash
# 检查 composer.json 格式
composer validate --strict

# 修复依赖问题
composer update --dry-run
```

### GitHub 推送失败

```bash
# 检查远程仓库
git remote -v

# 重新设置远程仓库
git remote set-url origin https://github.com/lihongjie0209/sm-php-bc.git
```

### Packagist 未更新

1. 检查 webhook 是否正确配置
2. 在 Packagist 项目页面手动点击 "Update"
3. 查看 webhook 日志排查错误

## 最佳实践

1. **遵循语义化版本**: 
   - 主版本号：不兼容的 API 修改
   - 次版本号：向下兼容的功能性新增
   - 修订号：向下兼容的问题修正

2. **维护 CHANGELOG**: 每次发布都要更新

3. **编写测试**: 确保所有功能都有测试覆盖

4. **文档更新**: 保持 README 和示例代码最新

5. **代码审查**: 发布前进行代码审查

## 相关链接

- [Packagist 文档](https://packagist.org/about)
- [Composer 文档](https://getcomposer.org/doc/)
- [语义化版本](https://semver.org/lang/zh-CN/)
- [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/)
