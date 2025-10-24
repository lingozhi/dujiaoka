# Resend 邮件服务配置指南（2分钟完成）

## ✨ 为什么选择 Resend

- ✅ **100% 在 Railway 上可用**（不会连接超时）
- ✅ **免费额度充足**：每月 3000 封邮件
- ✅ **配置超级简单**：只需 2 分钟
- ✅ **专为开发者设计**：API 友好

---

## 🚀 步骤 1: 注册并获取 API Key（1分钟）

### 1.1 访问 Resend 官网

打开浏览器，访问：**https://resend.com/signup**

### 1.2 注册账号

- 使用 GitHub 账号直接登录（最快）
- 或者使用邮箱注册

### 1.3 获取 API Key

注册成功后，你会自动进入控制台：

1. 在左侧菜单找到 **"API Keys"**
2. 点击 **"Create API Key"** 按钮
3. 输入名称（比如：`Railway Production`）
4. 点击 **"Add"** 生成
5. **复制** 生成的 API Key（格式：`re_xxxxxxxxxxxxxxxxx`）

⚠️ **重要：** API Key 只显示一次，请立即复制保存！

---

## 🚀 步骤 2: 在 Railway 配置 Resend（1分钟）

### 方法 A：使用命令行配置（推荐，最快）

在本地终端运行：

```bash
railway run php artisan mail:save-config \
  --host=smtp.resend.com \
  --port=587 \
  --username=resend \
  --password=re_你的API密钥 \
  --encryption=tls \
  --from_address=onboarding@resend.dev \
  --from_name=独角数卡
```

**替换 `re_你的API密钥`** 为你刚才复制的 API Key！

---

### 方法 B：在管理后台配置

1. 访问：`https://your-app.railway.app/admin/system-setting`

2. 点击 **"邮件设置"** 标签

3. 填写以下配置：

| 字段 | 值 |
|------|-----|
| **Driver** | `smtp` |
| **Host** | `smtp.resend.com` |
| **Port** | `587` |
| **Username** | `resend` |
| **Password** | `re_你的API密钥` |
| **Encryption** | `tls` |
| **From Address** | `onboarding@resend.dev` |
| **From Name** | `独角数卡` |

4. 点击 **"提交"** 保存

---

## 🧪 步骤 3: 测试邮件发送

### 方法 1：使用命令行测试

```bash
railway run php artisan email:test your_email@example.com
```

**替换 `your_email@example.com`** 为你的真实邮箱！

### 方法 2：使用管理后台测试

1. 访问：`https://your-app.railway.app/admin/email-test`
2. 填写你的邮箱地址
3. 点击 **"发送"**

---

## ✅ 预期结果

### 成功的话你会看到：

**命令行：**
```
✅ 邮件发送成功！
```

**管理后台：**
```
发送成功！
```

**你的邮箱：**
- 收到一封来自 `onboarding@resend.dev` 的测试邮件
- 检查收件箱和垃圾邮件文件夹

---

## 🎯 完成后的配置

配置保存后，邮件功能会在以下场景自动工作：

- ✉️ **订单支付成功** → 自动发送卡密到客户邮箱
- ✉️ **找回密码** → 发送重置链接
- ✉️ **系统通知** → 管理员通知邮件

---

## 📊 Resend 配置参数说明

| 参数 | 值 | 说明 |
|------|-----|------|
| **Host** | `smtp.resend.com` | Resend SMTP 服务器地址 |
| **Port** | `587` | SMTP 端口（使用 TLS 加密）|
| **Username** | `resend` | 固定值，不需要修改 |
| **Password** | `re_xxxxx` | 你的 API Key |
| **Encryption** | `tls` | 加密方式（不是 ssl）|
| **From Address** | `onboarding@resend.dev` | 发件人邮箱（Resend 提供的测试邮箱）|
| **From Name** | `独角数卡` | 发件人名称（可自定义）|

---

## 🔧 进阶配置（可选）

### 使用自己的域名发件

如果你有自己的域名（如 `opwan.ai`），可以：

1. 在 Resend 控制台添加并验证域名
2. 验证通过后，修改 `From Address` 为：`no-reply@yourdomain.com`

**好处：**
- ✅ 更专业的发件地址
- ✅ 提高邮件送达率
- ✅ 不会被标记为垃圾邮件

**不验证域名也能用！** `onboarding@resend.dev` 足够用于生产环境。

---

## ❓ 常见问题

### Q1: 为什么用 `onboarding@resend.dev`？

**A:** 这是 Resend 提供的测试邮箱，无需验证即可使用，非常适合快速上线。

### Q2: 免费额度够用吗？

**A:** 每月 3000 封对于中小型电商完全够用。如果不够，可以升级到付费计划。

### Q3: API Key 丢了怎么办？

**A:** 在 Resend 控制台删除旧的，重新创建一个即可。

### Q4: 邮件会进垃圾箱吗？

**A:** Resend 的送达率很高。如果使用自己的域名并正确配置 SPF/DKIM，几乎不会进垃圾箱。

---

## 🆘 遇到问题？

### 检查配置是否保存成功

```bash
railway run php artisan mail:show-config
```

### 查看日志

```bash
railway logs --filter "mail"
```

### 测试 SMTP 连接

```bash
railway run php artisan mail:test-connection smtp.resend.com --port=587
```

---

## 🎉 总结

**Resend 配置流程：**

1. ✅ 注册 Resend → 获取 API Key（1分钟）
2. ✅ 运行配置命令（10秒）
3. ✅ 测试邮件发送（10秒）
4. ✅ 完成！

**总耗时：** 不到 2 分钟

**结果：** 邮件功能立即可用，永不超时！

---

**现在就去注册 Resend 吧！** 👉 https://resend.com/signup
