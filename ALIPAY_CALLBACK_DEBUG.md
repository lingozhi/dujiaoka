# 支付宝回调问题排查指南

## 🔍 问题现象

支付宝支付成功后，订单状态未更新为"已完成"。

---

## 📊 回调工作流程

```
用户支付成功
  ↓
支付宝服务器发送回调
  ↓
POST https://yourdomain.com/pay/alipay/notify_url
  ↓
AlipayController::notifyUrl()
  ↓
1. 验证订单存在
2. 验证支付网关配置
3. 验证签名
4. 检查支付状态
5. 完成订单
  ↓
返回 success 给支付宝
```

---

## 🛠️ 已添加的日志记录

### 1. 回调接收日志
```
[INFO] 支付宝回调接收
- method: POST/GET
- data: 所有回调参数
- ip: 支付宝服务器IP
- headers: 请求头
```

### 2. 订单验证日志
```
[ERROR] 支付宝回调缺少订单号
[ERROR] 支付宝回调订单不存在
[INFO] 支付宝回调订单已完成（重复通知）
```

### 3. 支付网关验证日志
```
[ERROR] 支付宝回调支付网关不存在
[ERROR] 支付宝回调路由不匹配
[INFO] 支付宝回调配置（app_id, 密钥状态）
```

### 4. 签名验证日志
```
[INFO] 支付宝回调签名验证成功
[ERROR] 支付宝回调异常（签名验证失败）
```

### 5. 订单处理日志
```
[INFO] 支付宝订单处理成功
[ERROR] 支付宝订单处理失败
[WARNING] 支付宝回调状态非成功
```

---

## 🔧 排查步骤

### 步骤 1：查看 Railway 日志

1. 访问 Railway Dashboard
2. 选择服务 → **Logs** 标签
3. 搜索关键词：`支付宝回调`

#### 可能的日志情况

**情况 A：没有任何日志**
```
原因：支付宝没有发送回调
解决：检查支付宝后台回调地址配置
```

**情况 B：有"回调接收"日志，但签名验证失败**
```
[INFO] 支付宝回调接收
[ERROR] 支付宝回调异常: 签名验证失败
```
```
原因：支付宝公钥或应用私钥配置错误
解决：检查后台支付网关配置
```

**情况 C：签名验证成功，但订单处理失败**
```
[INFO] 支付宝回调签名验证成功
[ERROR] 支付宝订单处理失败: 金额不一致
```
```
原因：订单金额与支付金额不匹配
解决：检查订单创建时的金额设置
```

**情况 D：路由不匹配**
```
[ERROR] 支付宝回调路由不匹配
expected: /pay/alipay
actual: /pay/xxx
```
```
原因：后台配置的 pay_handleroute 不正确
解决：修改支付网关配置中的 pay_handleroute 为 /pay/alipay
```

---

## 🎯 支付宝后台配置

### 1. 登录支付宝开放平台

访问：https://open.alipay.com/

### 2. 进入应用管理

- 选择你的应用
- 进入 **开发信息**

### 3. 配置回调地址

#### 异步通知地址（必须配置）

```
https://dujiaoka-production-c6cf.up.railway.app/pay/alipay/notify_url
```

⚠️ **注意**：
- 必须是 HTTPS 协议
- 必须是完整的域名（不能用 localhost 或 IP）
- 必须公网可访问

#### 同步跳转地址（可选）

```
https://dujiaoka-production-c6cf.up.railway.app/detail-order-sn/{订单号}
```

### 4. 配置应用网关（如果有）

如果支付宝后台有"应用网关"选项，也填入相同的回调地址。

---

## 🔑 密钥配置

### 1. 应用公钥和私钥

**生成密钥**（如果还没有）：

```bash
# 使用支付宝密钥生成工具
https://opendocs.alipay.com/common/02kipl
```

**配置路径**：
- 后台 → 店铺管理 → 支付方式管理 → 支付宝

| 字段 | 说明 | 示例 |
|-----|------|------|
| merchant_id | App ID | 2021001234567890 |
| merchant_key | 支付宝公钥 | MIIBIjANBgkqhkiG9w0... |
| merchant_pem | 应用私钥 | MIIEvQIBADANBgkqhk... |
| pay_handleroute | 处理路由 | /pay/alipay |

⚠️ **常见错误**：
- ❌ merchant_key 填的是应用公钥（错误）
- ✅ merchant_key 应该填支付宝公钥
- ❌ merchant_pem 填的是应用公钥（错误）
- ✅ merchant_pem 应该填应用私钥

---

## 🧪 测试回调

### 方法 1：使用支付宝沙箱环境

1. 访问：https://openhome.alipay.com/platform/appDaily.htm
2. 使用沙箱账号测试支付
3. 查看 Railway 日志

### 方法 2：创建真实小额订单

1. 创建 0.01 元的测试订单
2. 使用真实支付宝账号支付
3. 查看订单状态是否更新
4. 查看 Railway 日志

### 方法 3：模拟回调请求

使用 Postman 或 curl 模拟支付宝回调：

```bash
curl -X POST 'https://dujiaoka-production-c6cf.up.railway.app/pay/alipay/notify_url' \
  -d 'out_trade_no=YOUR_ORDER_SN' \
  -d 'trade_status=TRADE_SUCCESS' \
  -d 'total_amount=0.01' \
  -d 'trade_no=2024012722001234567890' \
  -d 'sign=MOCK_SIGN' \
  -d 'sign_type=RSA2'
```

⚠️ **注意**：这只能测试订单查询逻辑，签名验证会失败。

---

## 📋 配置清单

### ✅ Railway 环境变量
```
APP_URL=https://dujiaoka-production-c6cf.up.railway.app
```

### ✅ 支付宝后台配置
- 异步通知地址：`{APP_URL}/pay/alipay/notify_url`
- 应用公钥：已上传到支付宝后台
- 支付宝公钥：已获取并保存

### ✅ 系统后台配置
- App ID（merchant_id）：正确填写
- 支付宝公钥（merchant_key）：正确填写
- 应用私钥（merchant_pem）：正确填写
- 处理路由（pay_handleroute）：`/pay/alipay`
- 是否启用：已启用

---

## 🚨 常见问题

### Q1: 日志显示"签名验证失败"

**原因**：
1. 支付宝公钥配置错误
2. 应用私钥配置错误
3. 密钥格式问题（有多余的空格或换行）

**解决**：
1. 重新获取支付宝公钥
2. 确认应用私钥正确
3. 删除密钥中的空格和换行符（只保留密钥内容）

### Q2: 日志显示"订单金额不一致"

**原因**：
- 订单实际金额与支付金额不匹配
- 可能是折扣、优惠券导致的差异

**解决**：
检查 `OrderProcessService::completedOrder()` 中的金额验证逻辑。

### Q3: 完全没有回调日志

**原因**：
1. 支付宝后台没有配置回调地址
2. 回调地址配置错误
3. Railway 防火墙阻止了请求（不太可能）

**解决**：
1. 检查支付宝后台配置
2. 确认回调地址能公网访问
3. 测试域名是否可以 ping 通

### Q4: 支付成功了但订单状态是"待处理"

**原因**：
- 订单类型是"手动处理"
- 系统正常处理为待处理状态

**解决**：
检查商品设置，确认是"自动发货"还是"手动处理"。

---

## 📝 下一步

1. **创建测试订单** - 使用 0.01 元测试
2. **完成支付** - 使用真实支付宝账号
3. **查看 Railway 日志** - 搜索"支付宝回调"
4. **根据日志排查** - 参考上面的日志情况说明
5. **告诉我日志内容** - 如果还有问题，把日志发给我

---

## 🔗 相关文档

- [支付宝开放平台](https://open.alipay.com/)
- [支付宝密钥工具](https://opendocs.alipay.com/common/02kipl)
- [支付宝回调说明](https://opendocs.alipay.com/open/270/105902)
- [Railway Dashboard](https://railway.app/dashboard)

---

**修改已完成，等待部署后测试！** 🚀
