# 支付回调地址配置指南

## 📋 支付回调地址说明

在独角数卡系统中，支付回调地址由系统自动生成，格式为：

```
{APP_URL}/pay/{支付方式}/notify_url
```

例如：
- 支付宝：`https://yourdomain.com/pay/alipay/notify_url`
- 微信：`https://yourdomain.com/pay/wepay/notify_url`
- Stripe：`https://yourdomain.com/pay/stripe/notify_url`

## ⚠️ 重要：必须配置 APP_URL

**如果不配置 APP_URL，支付回调将无法正常工作！**

支付回调地址使用 Laravel 的 `url()` 函数生成，依赖于 `APP_URL` 环境变量。

---

## 🚀 Railway 配置步骤

### 方法 1：在 Railway 控制台配置（推荐）

1. **访问 Railway Dashboard**
   https://railway.app/dashboard

2. **选择你的项目**
   点击你的独角数卡项目

3. **进入环境变量设置**
   点击服务 → "Variables" 标签

4. **添加 APP_URL 环境变量**
   - 点击 "New Variable"
   - 变量名：`APP_URL`
   - 变量值：`https://your-app-name.up.railway.app`（替换为你的实际域名）

5. **如果使用自定义域名**
   - 变量值：`https://dujiaoka.yourdomain.com`（替换为你的自定义域名）

6. **保存并等待重新部署**
   Railway 会自动触发重新部署（约 2-3 分钟）

---

### 方法 2：使用 Railway CLI 配置

```bash
railway variables set APP_URL=https://your-app-name.up.railway.app
```

---

## 🔍 如何获取你的 Railway 域名

### 查看 Railway 自动分配的域名：

1. 访问 Railway Dashboard
2. 选择你的项目
3. 点击 "Settings" 标签
4. 在 "Domains" 部分可以看到自动分配的域名
5. 格式通常是：`your-app-name.up.railway.app`

### 如果绑定了自定义域名：

使用你的自定义域名作为 APP_URL：
```
APP_URL=https://shop.yourdomain.com
```

---

## 📊 支付回调路由列表

系统支持以下支付方式的回调：

| 支付方式 | 回调路由 | 完整示例 |
|---------|---------|---------|
| **支付宝** | `/pay/alipay/notify_url` | `https://yourdomain.com/pay/alipay/notify_url` |
| **微信支付** | `/pay/wepay/notify_url` | `https://yourdomain.com/pay/wepay/notify_url` |
| **码支付** | `/pay/mapay/notify_url` | `https://yourdomain.com/pay/mapay/notify_url` |
| **Paysapi** | `/pay/paysapi/notify_url` | `https://yourdomain.com/pay/paysapi/notify_url` |
| **PayJS** | `/pay/payjs/notify_url` | `https://yourdomain.com/pay/payjs/notify_url` |
| **易支付** | `/pay/yipay/notify_url` | `https://yourdomain.com/pay/yipay/notify_url` |
| **PayPal** | `/pay/paypal/notify_url` | `https://yourdomain.com/pay/paypal/notify_url` |
| **V免签** | `/pay/vpay/notify_url` | `https://yourdomain.com/pay/vpay/notify_url` |
| **Stripe** | `/pay/stripe/return_url` | `https://yourdomain.com/pay/stripe/return_url` |
| **Coinbase** | `/pay/coinbase/notify_url` | `https://yourdomain.com/pay/coinbase/notify_url` |
| **EPUSDT** | `/pay/epusdt/notify_url` | `https://yourdomain.com/pay/epusdt/notify_url` |
| **TokenPay** | `/pay/tokenpay/notify_url` | `https://yourdomain.com/pay/tokenpay/notify_url` |

---

## 🔧 代码实现说明

回调地址在各个支付控制器中自动生成，例如（`app/Http/Controllers/Pay/AlipayController.php:28`）：

```php
$config = [
    // ... 其他配置
    'notify_url' => url($this->payGateway->pay_handleroute . '/notify_url'),
    'return_url' => url('detail-order-sn', ['orderSN' => $this->order->order_sn]),
];
```

`url()` 函数会使用 `APP_URL` 环境变量生成完整的URL。

---

## ✅ 验证配置是否正确

### 方法 1：检查日志

部署完成后，在 Railway 控制台查看日志：

```bash
railway logs
```

查找是否有包含正确域名的日志输出。

### 方法 2：测试订单

1. 创建一个测试订单
2. 选择支付方式
3. 检查支付网关收到的回调URL是否正确

### 方法 3：使用命令行检查

```bash
railway run php artisan tinker
```

然后执行：
```php
echo url('/pay/alipay/notify_url');
```

应该输出正确的完整URL。

---

## 🔒 HTTPS 注意事项

**重要：** Railway 默认提供 HTTPS，所以 APP_URL 必须使用 `https://`：

✅ **正确：**
```
APP_URL=https://your-app.up.railway.app
```

❌ **错误：**
```
APP_URL=http://your-app.up.railway.app
```

大多数支付网关（如支付宝、微信支付、Stripe）**要求回调地址必须是 HTTPS**！

---

## 🆘 常见问题

### Q1: 支付成功但订单状态未更新？

**原因：** 回调地址配置错误，支付网关无法通知系统。

**解决：**
1. 检查 Railway 环境变量中 `APP_URL` 是否正确
2. 确认使用的是 `https://` 而不是 `http://`
3. 在支付网关后台检查回调日志

### Q2: 如何在支付网关后台配置回调地址？

对于需要手动配置回调地址的支付网关：

1. 登录支付网关后台（如 Stripe Dashboard）
2. 找到 Webhook 或回调地址设置
3. 填入：`https://yourdomain.com/pay/{支付方式}/notify_url`
4. 保存配置

**示例：**
- Stripe Webhook: `https://yourdomain.com/pay/stripe/return_url`
- PayPal IPN: `https://yourdomain.com/pay/paypal/notify_url`

### Q3: Railway 域名经常变化怎么办？

**建议使用自定义域名：**

1. 在 Railway 绑定你的自定义域名
2. 设置 `APP_URL` 为自定义域名
3. 这样即使 Railway 内部域名变化，回调地址也保持不变

### Q4: 本地开发时如何测试回调？

**使用 ngrok 或类似工具：**

```bash
ngrok http 80
```

然后将生成的公网地址设置为 `APP_URL`。

---

## 📝 完整配置示例

### Railway 环境变量设置：

```bash
APP_NAME=独角数卡
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dujiaoka-production.up.railway.app

# 邮件配置
MAIL_HOST=smtp.resend.com
MAIL_PORT=2587
# ... 其他配置
```

### Stripe 回调配置示例：

在 Stripe Dashboard 的 Webhook 设置中：
```
Endpoint URL: https://dujiaoka-production.up.railway.app/pay/stripe/return_url
Events to send: checkout.session.completed
```

---

## 🎯 总结

**关键要点：**

1. ✅ 必须在 Railway 设置 `APP_URL` 环境变量
2. ✅ APP_URL 必须使用 `https://`
3. ✅ 使用你的实际域名（Railway 分配的或自定义的）
4. ✅ 设置后 Railway 会自动重新部署
5. ✅ 回调地址由系统自动生成，无需手动配置

**现在就去 Railway 控制台添加 APP_URL 环境变量吧！** 🚀
