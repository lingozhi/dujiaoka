# Railway 邮件配置快速指南

## ✨ 重要更新

**邮件配置现已支持环境变量！**

只需在 Railway 配置环境变量，无需在管理后台设置，配置将自动生效且永久保存。即使容器重启，邮件功能也能正常工作！

## 🚀 快速配置（推荐）

### 方法1：通过 Railway 网页控制台

1. 访问 https://railway.app/dashboard
2. 选择您的项目
3. 点击服务 → "Variables" 标签
4. 点击 "New Variable" 按钮
5. 逐个添加以下8个环境变量：

| 变量名 | 值 |
|--------|-----|
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | `smtp.feishu.cn` |
| `MAIL_PORT` | `465` |
| `MAIL_USERNAME` | `no-reply@opwan.ai` |
| `MAIL_PASSWORD` | `Y5H2MrTLzJfFUH0a` |
| `MAIL_ENCRYPTION` | `ssl` |
| `MAIL_FROM_ADDRESS` | `no-reply@opwan.ai` |
| `MAIL_FROM_NAME` | `独角数卡` |

6. 点击保存，Railway 会自动重新部署

### 方法2：使用 Railway CLI

如果您已安装 Railway CLI，可以运行以下命令：

```bash
# 确保在项目目录中
cd D:\code\2025\dujiaoka

# 执行配置脚本
railway variables set MAIL_MAILER=smtp
railway variables set MAIL_HOST=smtp.feishu.cn
railway variables set MAIL_PORT=465
railway variables set MAIL_USERNAME=no-reply@opwan.ai
railway variables set MAIL_PASSWORD=Y5H2MrTLzJfFUH0a
railway variables set MAIL_ENCRYPTION=ssl
railway variables set MAIL_FROM_ADDRESS=no-reply@opwan.ai
railway variables set "MAIL_FROM_NAME=独角数卡"
```

或者直接运行脚本：
```bash
bash railway-email-config.sh
```

## ⏱️ 等待部署

配置完成后：
- ⏳ Railway 会自动触发重新部署
- ⏳ 通常需要 2-5 分钟
- ✅ 部署完成后邮件功能即可使用

## 🎯 配置原理

**新版本改进：**

- ✅ 邮件配置直接从 Railway 环境变量加载
- ✅ 容器重启后配置自动保留（环境变量持久化）
- ✅ 无需在管理后台重复设置
- ✅ 管理后台配置（如已设置）优先级更高

**工作机制：**

1. 系统首先检查管理后台的邮件配置（缓存）
2. 如果缓存为空或不存在，自动使用 Railway 环境变量
3. 这确保了即使缓存丢失（容器重启），邮件功能依然可用

## 🧪 测试邮件功能

### 在 Railway 容器中测试

```bash
# 连接到 Railway 容器
railway run bash

# 发送测试邮件
php artisan email:test your_email@example.com

# 退出容器
exit
```

### 通过管理后台测试

1. 登录管理后台：https://dujiaoka-production-c6cf.up.railway.app/admin
2. 进入 系统设置 → 邮件配置
3. 点击 "发送测试邮件"
4. 检查邮箱（包括垃圾邮件文件夹）

## 📧 邮件发送场景

配置完成后，系统会在以下情况自动发送邮件：

- ✉️ 订单支付成功通知
- ✉️ 卡密发货邮件
- ✉️ 找回密码链接
- ✉️ 系统通知邮件

## ❗ 故障排查

### 问题1：配置后收不到邮件

**解决步骤：**

1. 检查 Railway 日志：
```bash
railway logs --filter "mail"
```

2. 确认环境变量已设置：
```bash
railway variables
```

3. 确认服务已重新部署（查看 Railway Dashboard）

### 问题2：报错 "Connection refused"

- 确认端口是 `465`
- 确认加密方式是 `ssl`（不是 `tls`）

### 问题3：报错 "Authentication failed"

- 确认用户名：`no-reply@opwan.ai`
- 确认密码：`Y5H2MrTLzJfFUH0a`（注意大小写）

## 🔐 安全提醒

- ⚠️ 专用密码已配置在 Railway 环境变量中
- ⚠️ 不要将密码提交到 Git 仓库
- ⚠️ 本地 .env 文件已配置但不会提交
- ⚠️ 定期更换专用密码以提高安全性

## 📞 需要帮助？

如遇到问题，请检查：
1. Railway 部署日志
2. 环境变量是否正确设置
3. 邮箱服务商是否有限制
4. 参考 `EMAIL_SETUP.md` 文档

---

**配置完成后记得重启服务！** 🔄
