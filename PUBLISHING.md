# 发布指南

本文档提供了将`zhifu/tron-api`包发布到Composer的步骤指南。

## 准备工作

1. 确保你有一个[GitHub](https://github.com)账户
2. 确保你有一个[Packagist](https://packagist.org)账户并与GitHub账户关联

## 步骤1：创建GitHub仓库

1. 在GitHub上创建一个新的仓库，命名为`tron-api`
2. 将本地项目推送到GitHub仓库：

```bash
# 初始化Git仓库
git init

# 添加所有文件
git add .

# 提交更改
git commit -m "初始化提交"

# 添加远程仓库（替换USERNAME为你的GitHub用户名）
git remote add origin https://github.com/USERNAME/tron-api.git

# 推送到GitHub
git push -u origin main  # 如果是master分支则使用 git push -u origin master
```

## 步骤2：给项目打标签

为了发布特定版本，我们需要给项目打上版本标签：

```bash
# 创建标签
git tag -a v1.0.0 -m "第一个正式版本"

# 推送标签到GitHub
git push origin v1.0.0
```

## 步骤3：在Packagist上发布包

1. 登录[Packagist](https://packagist.org)
2. 点击"Submit Package"按钮
3. 输入GitHub仓库URL（例如：https://github.com/USERNAME/tron-api）
4. 点击"Check"按钮验证
5. 如果一切正常，点击"Submit"按钮

## 步骤4：配置Webhook自动更新

为了让Packagist在你更新GitHub仓库时自动更新，配置一个Webhook：

1. 在Packagist的包页面，找到并复制Webhook URL
2. 前往GitHub仓库设置 -> Webhooks -> Add webhook
3. 粘贴Webhook URL到"Payload URL"字段
4. 内容类型选择"application/json"
5. 点击"Add webhook"保存

## 更新包

每次更新包时，需要执行以下步骤：

1. 更新代码
2. 提交更改到Git
3. 创建新的版本标签
4. 推送更改和标签到GitHub

```bash
git add .
git commit -m "更新说明"
git tag -a v1.0.1 -m "版本说明"
git push origin main
git push origin v1.0.1
```

## 包的使用

用户可以通过以下命令安装你的包：

```bash
composer require zhifu/tron-api
```

## 注意事项

- 确保`composer.json`文件中的包名与GitHub仓库名一致
- 包名必须是唯一的，以避免与其他包冲突
- 确保你的包符合PSR标准
- 提供完整的文档和示例代码
- 定期更新和维护你的包 