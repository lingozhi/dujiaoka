# 支付宝回调参数验证详解

## 🔍 你的疑问

**merchant_key（支付宝公钥）能正确处理回调参数吗？**

**答案：✅ 可以，但前提是配置正确！**

---

## 📊 回调签名验证流程

### 1. 支付宝发送回调

支付宝会发送包含这些参数的 POST 请求：

```
POST /pay/alipay/notify_url
Content-Type: application/x-www-form-urlencoded

out_trade_no=DRELIHTQ4G479HYT
trade_no=2025102722001462541453203265
trade_status=TRADE_SUCCESS
total_amount=0.01
app_id=2021006104605238
sign_type=RSA2
sign=TfxVaZLP...（很长的签名）
...（其他参数）
```

### 2. 系统接收并验证（第158行）

```php
$pay = Pay::alipay($config);
$result = $pay->verify();  // 自动验证签名
```

**yansongda/pay 包会做什么**：

1. **获取所有回调参数**
   ```php
   $data = $_POST;  // 或 $request->all()
   ```

2. **提取签名**
   ```php
   $sign = $data['sign'];
   unset($data['sign']);
   unset($data['sign_type']);
   ```

3. **构建待签名字符串**
   ```php
   ksort($data);  // 按key排序
   $string = http_build_query($data);  // 构建查询字符串
   ```

4. **使用支付宝公钥验证签名**
   ```php
   openssl_verify($string, base64_decode($sign), $ali_public_key, OPENSSL_ALGO_SHA256);
   ```

5. **返回验证结果**
   - ✅ 验证成功：返回订单数据对象
   - ❌ 验证失败：抛出异常

---

## ⚠️ 关键配置

### merchant_key 必须是支付宝公钥！

```php
$config = [
    'app_id' => $payGateway->merchant_id,           // App ID
    'ali_public_key' => $payGateway->merchant_key,  // ← 这里必须是支付宝公钥
    'private_key' => $payGateway->merchant_pem,     // 应用私钥
];
```

**常见错误配置**：

| 字段 | ❌ 错误配置 | ✅ 正确配置 |
|-----|-----------|-----------|
| merchant_key | 应用公钥 | **支付宝公钥** |
| merchant_key | 应用私钥 | **支付宝公钥** |
| merchant_pem | 应用公钥 | **应用私钥** |

---

## 🧪 如何验证配置是否正确？

### 方法 1：查看 Railway 日志

部署后创建测试订单，支付成功后查看日志：

**✅ 配置正确的日志**：
```
[INFO] 支付宝回调接收 {method: POST, data: {...}}
[INFO] 支付宝回调配置 {app_id: 2021006104605238, has_ali_public_key: true, has_private_key: true}
[INFO] 支付宝回调签名验证成功 {orderSN: XXX, trade_status: TRADE_SUCCESS, ...}
[INFO] 支付宝订单处理成功 {...}
```

**❌ 配置错误的日志**：
```
[INFO] 支付宝回调接收 {method: POST, data: {...}}
[INFO] 支付宝回调配置 {app_id: 2021006104605238, ...}
[ERROR] 支付宝回调异常: 验签失败
```

### 方法 2：检查订单状态

- ✅ 订单状态变为"已完成" → 回调处理成功
- ❌ 订单状态还是"待支付" → 回调失败

---

## 📝 配置检查清单

### ✅ 支付宝开放平台

1. **App ID**
   - 位置：控制台 → 应用 → 开发信息
   - 格式：16位数字（如 2021006104605238）
   - 填入后台：merchant_id 字段

2. **应用公钥**
   - 你自己生成的公钥
   - 需要上传到支付宝后台
   - **不要**填入系统后台

3. **支付宝公钥**
   - 上传应用公钥后，支付宝返回的公钥
   - 位置：接口加签方式 → 查看支付宝公钥
   - 填入后台：merchant_key 字段 ⭐

4. **应用私钥**
   - 你自己生成的私钥
   - **不要**上传到支付宝
   - 填入后台：merchant_pem 字段

---

## 🔐 三把钥匙的作用

```
支付请求（你 → 支付宝）:
  使用：应用私钥签名
  验证：支付宝用应用公钥验证

支付回调（支付宝 → 你）:
  使用：支付宝私钥签名
  验证：你用支付宝公钥验证 ← merchant_key 的作用
```

---

## 💡 verify() 方法详解

**yansongda/pay 的 verify() 方法**：

```php
public function verify($content = null, bool $refund = false): Collection
{
    // 1. 获取回调数据
    $data = Request::createFromGlobals()->request->all();

    // 2. 验证必要参数
    if (empty($data['sign']) || empty($data['out_trade_no'])) {
        throw new InvalidSignException('回调参数缺失');
    }

    // 3. 验证签名
    if (!$this->verifySign($data)) {
        throw new InvalidSignException('签名验证失败');
    }

    // 4. 返回数据
    return new Collection($data);
}
```

**所以 verify() 会自动**：
- ✅ 从 HTTP 请求中获取所有参数
- ✅ 验证签名
- ✅ 返回订单数据

**你不需要手动**：
- ❌ 手动提取参数
- ❌ 手动构建签名字符串
- ❌ 手动调用 openssl_verify

---

## 🎯 代码中的处理逻辑

### 第158行：验证签名

```php
$result = $pay->verify();
```

**返回的 $result 包含**：
```php
$result->out_trade_no    // 订单号
$result->trade_no        // 支付宝交易号
$result->trade_status    // 交易状态
$result->total_amount    // 支付金额
$result->buyer_id        // 买家支付宝ID
// ... 其他参数
```

### 第167行：检查支付状态

```php
if ($result->trade_status == 'TRADE_SUCCESS' || $result->trade_status == 'TRADE_FINISHED')
```

**支付宝的交易状态**：
- `WAIT_BUYER_PAY` - 等待买家付款
- `TRADE_CLOSED` - 交易关闭
- `TRADE_SUCCESS` - 交易成功 ⭐
- `TRADE_FINISHED` - 交易完结 ⭐

### 第169行：完成订单

```php
$this->orderProcessService->completedOrder(
    $result->out_trade_no,   // 订单号
    $result->total_amount,    // 支付金额
    $result->trade_no         // 支付宝交易号
);
```

---

## 🐛 常见问题排查

### 问题 1：签名验证失败

**日志**：
```
[ERROR] 支付宝回调异常: 验签失败
```

**原因**：
1. merchant_key 填的不是支付宝公钥
2. 支付宝公钥复制错误（有多余空格、换行）
3. 密钥格式不对（应该是不带头尾的纯密钥）

**解决**：
1. 重新从支付宝后台获取支付宝公钥
2. 确保只复制密钥内容（去掉 `-----BEGIN...-----`）
3. 去掉所有空格和换行

### 问题 2：没有收到回调

**日志**：
```
（完全没有"支付宝回调接收"日志）
```

**原因**：
1. 支付宝后台没有配置回调地址
2. 回调地址配置错误
3. Railway 域名不可访问

**解决**：
1. 检查支付宝后台的应用网关配置
2. 确认回调地址：`https://dujiaoka-production-c6cf.up.railway.app/pay/alipay/notify_url`
3. 测试域名是否可以访问

### 问题 3：订单金额不一致

**日志**：
```
[ERROR] 支付宝订单处理失败: 订单金额不一致
```

**原因**：
- 订单实际金额 ≠ 支付金额
- 可能有折扣、优惠券

**解决**：
- 检查 OrderProcessService::completedOrder 中的金额验证逻辑

---

## ✅ 配置验证命令

创建一个测试脚本来验证配置：

```php
<?php
// test_alipay_config.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 获取支付宝配置
$payGateway = \App\Models\Pay::where('pay_check', 'alipay')->first();

if (!$payGateway) {
    echo "❌ 未找到支付宝支付配置\n";
    exit;
}

echo "✅ 支付宝配置检查\n\n";
echo "App ID (merchant_id): {$payGateway->merchant_id}\n";
echo "支付宝公钥长度 (merchant_key): " . strlen($payGateway->merchant_key) . " 字符\n";
echo "应用私钥长度 (merchant_pem): " . strlen($payGateway->merchant_pem) . " 字符\n";
echo "处理路由: {$payGateway->pay_handleroute}\n\n";

// 验证密钥格式
if (strlen($payGateway->merchant_key) < 200) {
    echo "⚠️ 警告：支付宝公钥太短，可能配置错误\n";
}

if (strlen($payGateway->merchant_pem) < 1000) {
    echo "⚠️ 警告：应用私钥太短，可能配置错误\n";
}

if ($payGateway->pay_handleroute !== '/pay/alipay') {
    echo "❌ 错误：处理路由应该是 /pay/alipay\n";
}

echo "\n✅ 配置检查完成\n";
```

**运行**：
```bash
php test_alipay_config.php
```

---

## 🎯 总结

### merchant_key 能正确处理回调参数吗？

**答案：✅ 能！**

**前提是**：
1. ✅ merchant_key 填的是**支付宝公钥**（不是应用公钥或私钥）
2. ✅ 支付宝后台配置了正确的回调地址
3. ✅ 密钥格式正确（不带头尾，无空格换行）

### 验证方法

1. 创建测试订单
2. 完成支付
3. 查看 Railway 日志
4. 检查订单状态

如果日志显示"支付宝回调签名验证成功"，说明配置正确！

---

**需要帮你检查配置吗？** 可以告诉我：
1. Railway 日志中的错误信息
2. merchant_key 的前20个字符
3. 订单状态是否更新

我会帮你分析具体问题！🔍
