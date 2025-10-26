# 独角数卡支付系统配置完全指南

## 目录
1. [支付系统架构](#支付系统架构)
2. [支付流程完整说明](#支付流程完整说明)
3. [后台配置步骤](#后台配置步骤)
4. [重要配置项说明](#重要配置项说明)
5. [代码实现详解](#代码实现详解)
6. [常见问题](#常见问题)

---

## 支付系统架构

独角数卡使用**支付网关**模式，支持多种支付方式：

### 支持的支付方式

| 支付方式 | 控制器 | 路由前缀 |
|---------|--------|---------|
| 支付宝 | AlipayController | `/pay/alipay` |
| 微信支付 | WepayController | `/pay/wepay` |
| 码支付 | MapayController | `/pay/mapay` |
| Paysapi | PaysapiController | `/pay/paysapi` |
| PayJS | PayjsController | `/pay/payjs` |
| 易支付 | YipayController | `/pay/yipay` |
| PayPal | PaypalPayController | `/pay/paypal` |
| V免签 | VpayController | `/pay/vpay` |
| Stripe | StripeController | `/pay/stripe` |
| Coinbase | CoinbaseController | `/pay/coinbase` |
| EPUSDT | EpusdtController | `/pay/epusdt` |
| TokenPay | TokenPayController | `/pay/tokenpay` |

---

## 支付流程完整说明

### 1️⃣ 用户选择商品并下单

**文件**: `routes/common/web.php:20`

```php
Route::post('create-order', 'OrderController@createOrder');
```

**流程**:
- 用户在首页 `/buy/{id}` 选择商品
- 填写购买数量、邮箱、支付方式
- 提交表单到 `/create-order`

**代码**: `app/Http/Controllers/Home/OrderController.php:75`

```php
// 用户在表单中选择的支付方式 ID
$this->orderProcessService->setPayID($request->input('payway'));
```

### 2️⃣ 创建订单

**文件**: `app/Service/OrderProcessService.php:312`

```php
public function createOrder(): Order
{
    $order = new Order();
    $order->order_sn = strtoupper(Str::random(16)); // 生成订单号
    $order->pay_id = $this->payID;  // 设置支付方式
    // ... 计算价格、优惠等
    $order->save();
    return $order;
}
```

订单创建后会存储：
- `order_sn`: 唯一订单号（16位随机字符串）
- `pay_id`: 支付方式 ID（对应后台配置的支付网关）
- `actual_price`: 实际需要支付的金额
- `status`: 订单状态（待支付）

### 3️⃣ 跳转到支付页面

**路由**: `routes/common/pay.php:11`

```php
Route::get('pay-gateway/{handle}/{payway}/{orderSN}', 'PayController@redirectGateway');
```

**示例**: `https://yourdomain.com/pay-gateway/pay/alipay/ABC123DEF456`

**文件**: `app/Http/Controllers/PayController.php`

```php
public function redirectGateway($handle, $payway, $orderSN)
{
    // 1. 验证订单是否存在
    $this->checkOrder($orderSN);

    // 2. 加载支付网关配置
    $this->loadGateWay($orderSN, $payway);

    // 3. 跳转到对应的支付控制器
    return redirect()->to(url($this->payGateway->pay_handleroute . '/' . $payway . '/' . $orderSN));
}
```

### 4️⃣ 生成支付参数

**以支付宝为例**: `app/Http/Controllers/Pay/AlipayController.php:19-70`

```php
public function gateway(string $payway, string $orderSN)
{
    // 1. 加载支付网关配置和订单信息
    $this->loadGateWay($orderSN, $payway);

    // 2. 构建支付配置
    $config = [
        'app_id' => $this->payGateway->merchant_id,      // 商户 ID
        'ali_public_key' => $this->payGateway->merchant_key,  // 支付宝公钥
        'private_key' => $this->payGateway->merchant_pem,     // 商户私钥

        // ⚠️ 重要：支付成功后的回调地址
        'notify_url' => url($this->payGateway->pay_handleroute . '/notify_url'),

        // 用户支付成功后的跳转地址
        'return_url' => url('detail-order-sn', ['orderSN' => $this->order->order_sn]),
    ];

    // 3. 构建订单参数
    $order = [
        'out_trade_no' => $this->order->order_sn,       // 订单号
        'total_amount' => (float)$this->order->actual_price,  // 金额
        'subject' => $this->order->order_sn             // 订单标题
    ];

    // 4. 根据支付方式调用不同的支付 SDK
    switch ($payway){
        case 'alipayscan':  // 扫码支付
            $result = Pay::alipay($config)->scan($order);
            return $this->render('static_pages/qrpay', $result);

        case 'aliweb':      // 电脑网站支付
            return Pay::alipay($config)->web($order);

        case 'aliwap':      // 手机网站支付
            return Pay::alipay($config)->wap($order);
    }
}
```

**关键点**:
- `notify_url` 使用 Laravel 的 `url()` 函数生成
- `url()` 函数依赖 `.env` 中的 `APP_URL` 环境变量
- 如果 `APP_URL` 未配置，回调地址将不正确！

**生成的回调地址示例**:
```
https://yourdomain.com/pay/alipay/notify_url
```

### 5️⃣ 用户完成支付

用户在支付平台（支付宝、微信等）完成支付后：

1. **支付平台验证支付成功**
2. **支付平台向系统发送回调请求** → `POST /pay/alipay/notify_url`

### 6️⃣ 接收支付回调

**文件**: `app/Http/Controllers/Pay/AlipayController.php:77-107`

```php
public function notifyUrl(Request $request)
{
    // 1. 获取订单号
    $orderSN = $request->input('out_trade_no');
    $order = $this->orderService->detailOrderSN($orderSN);

    // 2. 获取支付网关配置
    $payGateway = $this->payService->detail($order->pay_id);

    // 3. 验证签名（防止伪造回调）
    $config = [
        'app_id' => $payGateway->merchant_id,
        'ali_public_key' => $payGateway->merchant_key,
        'private_key' => $payGateway->merchant_pem,
    ];
    $pay = Pay::alipay($config);
    $result = $pay->verify();

    // 4. 检查支付状态
    if ($result->trade_status == 'TRADE_SUCCESS') {
        // 5. 完成订单
        $this->orderProcessService->completedOrder(
            $result->out_trade_no,   // 订单号
            $result->total_amount,   // 支付金额
            $result->trade_no        // 支付平台交易号
        );
    }

    // 6. 返回成功响应给支付平台
    return 'success';
}
```

### 7️⃣ 订单完成处理

**文件**: `app/Service/OrderProcessService.php:385-438`

```php
public function completedOrder(string $orderSN, float $actualPrice, string $tradeNo)
{
    // 1. 验证订单存在且未完成
    $order = $this->orderService->detailOrderSN($orderSN);

    // 2. 验证支付金额是否一致
    if (bccomp($order->actual_price, $actualPrice, 2) != 0) {
        throw new \Exception('金额不一致');
    }

    // 3. 区分订单类型处理
    if ($order->type == Order::AUTOMATIC_DELIVERY) {
        // 自动发货：直接发送卡密
        $this->processAuto($order);
    } else {
        // 手动发货：通知管理员
        $this->processManual($order);
    }

    // 4. 发送通知（邮件、Telegram、Server酱等）
    MailSend::dispatch($order->email, $subject, $content);
}
```

### 8️⃣ 自动发货流程

**文件**: `app/Service/OrderProcessService.php:489-524`

```php
public function processAuto(Order $order): Order
{
    // 1. 从数据库获取卡密
    $carmis = $this->carmisService->withGoodsByAmountAndStatusUnsold(
        $order->goods_id,
        $order->buy_amount
    );

    // 2. 将卡密写入订单
    $carmisInfo = array_column($carmis, 'carmi');
    $order->info = implode(PHP_EOL, $carmisInfo);
    $order->status = Order::STATUS_COMPLETED;  // 已完成
    $order->save();

    // 3. 标记卡密为已售出
    $this->carmisService->soldByIDS($ids);

    // 4. 发送邮件给买家
    $mailData = [
        'product_name' => $order->goods->gd_name,
        'ord_info' => implode('<br/>', $carmisInfo),  // 卡密信息
        'order_id' => $order->order_sn,
    ];
    MailSend::dispatch($order->email, '购买成功', $mailBody);

    return $order;
}
```

---

## 后台配置步骤

### 第一步：添加支付网关

1. 登录后台：`https://yourdomain.com/admin`
2. 进入：**店铺管理** → **支付方式管理**
3. 点击 **新增**

### 第二步：填写支付网关信息

以支付宝为例：

| 字段 | 说明 | 示例值 |
|-----|------|--------|
| **支付方式名称** | 显示给用户的名称 | `支付宝` |
| **支付标识** | 图标标识 | `alipay` |
| **商户号 (merchant_id)** | 支付平台提供的商户 ID | `2088123456789012` |
| **商户密钥 (merchant_key)** | 支付平台的公钥 | `MIIBIjANBgkqhk...` |
| **商户私钥 (merchant_pem)** | 你的应用私钥 | `MIIEvQIBADANBg...` |
| **支付处理路由 (pay_handleroute)** | ⚠️ **非常重要** | `/pay/alipay` |
| **是否启用** | 开启或关闭此支付方式 | `✅ 启用` |

### 第三步：理解 pay_handleroute 字段

**pay_handleroute** 是支付网关最关键的配置！

它决定了：
1. 支付请求的路由前缀
2. 支付回调的路由路径

**必须与代码中的路由匹配！**

| 支付方式 | pay_handleroute | 对应路由 |
|---------|----------------|---------|
| 支付宝 | `/pay/alipay` | `Route::get('alipay/{payway}/{orderSN}', 'AlipayController@gateway')` |
| 微信 | `/pay/wepay` | `Route::get('wepay/{payway}/{orderSN}', 'WepayController@gateway')` |
| 码支付 | `/pay/mapay` | `Route::get('mapay/{payway}/{orderSN}', 'MapayController@gateway')` |

**错误示例**:
- ❌ `pay_handleroute = /alipay` （缺少 `/pay` 前缀）
- ❌ `pay_handleroute = /pay/ali` （路由名称不匹配）
- ✅ `pay_handleroute = /pay/alipay` （正确）

### 第四步：配置支付平台的回调地址

某些支付平台（如 Stripe、PayPal）需要在其后台手动配置回调地址：

**支付宝**:
- 登录 [支付宝开放平台](https://open.alipay.com/)
- 应用详情 → 接口加签方式 → 设置应用网关
- 填入：`https://yourdomain.com/pay/alipay/notify_url`

**Stripe**:
- 登录 [Stripe Dashboard](https://dashboard.stripe.com/)
- Developers → Webhooks → Add endpoint
- 填入：`https://yourdomain.com/pay/stripe/return_url`

**PayPal**:
- 登录 [PayPal Developer](https://developer.paypal.com/)
- My Apps & Credentials → 选择应用 → Webhooks
- 填入：`https://yourdomain.com/pay/paypal/notify_url`

---

## 重要配置项说明

### ⚠️ 必须配置 APP_URL

支付回调地址使用 Laravel 的 `url()` 函数生成，依赖于 `APP_URL` 环境变量。

#### Railway 配置方法：

1. 访问 Railway Dashboard
2. 选择项目 → Variables 标签
3. 添加环境变量：
   ```
   APP_URL=https://your-app-name.up.railway.app
   ```
4. 如果使用自定义域名：
   ```
   APP_URL=https://shop.yourdomain.com
   ```

#### 本地开发配置：

编辑 `.env` 文件：

```env
APP_URL=http://localhost
```

或使用 ngrok 进行测试：

```bash
ngrok http 80
# 将生成的公网地址设置为 APP_URL
APP_URL=https://abc123.ngrok.io
```

### 支付回调地址生成逻辑

**代码**: `app/Http/Controllers/Pay/AlipayController.php:28`

```php
'notify_url' => url($this->payGateway->pay_handleroute . '/notify_url'),
```

**实际生成过程**:

1. `$this->payGateway->pay_handleroute` = `/pay/alipay` (数据库配置)
2. 拼接 `/notify_url` = `/pay/alipay/notify_url`
3. `url()` 函数读取 `APP_URL` = `https://yourdomain.com`
4. 最终生成：`https://yourdomain.com/pay/alipay/notify_url`

**如果 APP_URL 未配置**:
- `url()` 可能返回 `http://localhost/pay/alipay/notify_url`
- 支付平台无法访问 localhost
- 回调失败，订单状态无法更新！

---

## 代码实现详解

### 支付路由定义

**文件**: `routes/common/pay.php:14-60`

```php
Route::group(['prefix' => 'pay', 'namespace' => 'Pay'], function () {
    // 支付宝
    Route::get('alipay/{payway}/{orderSN}', 'AlipayController@gateway');
    Route::post('alipay/notify_url', 'AlipayController@notifyUrl');

    // 微信
    Route::get('wepay/{payway}/{orderSN}', 'WepayController@gateway');
    Route::post('wepay/notify_url', 'WepayController@notifyUrl');

    // ... 其他支付方式
});
```

**路由说明**:
- `{payway}`: 支付子方式（如支付宝的扫码、网页、手机版）
- `{orderSN}`: 订单号
- `notify_url`: 支付平台回调的固定路径

### 支付控制器基类

**文件**: `app/Http/Controllers/PayController.php`

所有支付控制器都继承自 `PayController`，提供公共方法：

```php
/**
 * 加载支付网关配置
 */
protected function loadGateWay($orderSN, $payway)
{
    // 1. 查询订单
    $this->order = $this->orderService->detailOrderSN($orderSN);

    // 2. 查询支付网关配置
    $this->payGateway = $this->payService->detail($this->order->pay_id);

    // 3. 设置支付方式
    $this->payway = $payway;
}

/**
 * 验证订单
 */
public function checkOrder($orderSN)
{
    $order = $this->orderService->detailOrderSN($orderSN);

    // 订单不存在
    if (!$order) {
        throw new RuleValidationException('订单不存在');
    }

    // 订单已支付
    if ($order->status != Order::STATUS_OPEN) {
        throw new RuleValidationException('订单已支付或已过期');
    }
}
```

### 订单处理服务

**文件**: `app/Service/OrderProcessService.php`

核心业务逻辑：

1. **创建订单** (`createOrder()`)
   - 生成订单号
   - 计算价格（原价、优惠码、批发价）
   - 保存订单
   - 设置订单过期时间

2. **完成订单** (`completedOrder()`)
   - 验证订单和金额
   - 区分自动发货/手动发货
   - 发送邮件通知
   - 触发其他通知（Telegram、Server酱等）

3. **自动发货** (`processAuto()`)
   - 从数据库获取卡密
   - 标记卡密已售出
   - 发送卡密给买家

4. **手动发货** (`processManual()`)
   - 设置订单为待处理
   - 发送邮件通知管理员

---

## 常见问题

### Q1: 支付成功但订单状态未更新？

**原因**: 支付回调地址配置错误，支付平台无法通知系统。

**解决步骤**:

1. **检查 APP_URL 环境变量**:
   ```bash
   railway run php artisan tinker
   # 执行
   echo config('app.url');
   ```
   应该返回正确的域名，如 `https://your-app.up.railway.app`

2. **检查后台配置的 pay_handleroute**:
   - 登录后台 → 支付方式管理
   - 确认 `pay_handleroute` 字段为 `/pay/alipay` 格式

3. **查看支付平台的回调日志**:
   - 支付宝：应用详情 → 接口加签方式 → 网关日志
   - 检查是否有回调失败记录

4. **测试回调地址是否可访问**:
   ```bash
   curl -X POST https://yourdomain.com/pay/alipay/notify_url
   ```
   应该返回 `error` 或 `fail`（不应该是 404）

### Q2: 如何添加新的支付方式？

**需要修改的文件**:

1. **创建支付控制器**:
   ```php
   // app/Http/Controllers/Pay/NewPayController.php
   namespace App\Http\Controllers\Pay;

   class NewPayController extends PayController
   {
       public function gateway(string $payway, string $orderSN)
       {
           // 支付逻辑
       }

       public function notifyUrl(Request $request)
       {
           // 回调处理
       }
   }
   ```

2. **添加路由**:
   ```php
   // routes/common/pay.php
   Route::get('newpay/{payway}/{orderSN}', 'NewPayController@gateway');
   Route::post('newpay/notify_url', 'NewPayController@notifyUrl');
   ```

3. **在后台添加支付网关**:
   - pay_handleroute = `/pay/newpay`

### Q3: Railway 域名经常变化怎么办？

**问题**: Railway 的自动分配域名可能会变化。

**解决方案**: 绑定自定义域名

1. 在 Railway 项目中添加自定义域名
2. 在域名 DNS 中添加 CNAME 记录
3. 设置环境变量：
   ```
   APP_URL=https://shop.yourdomain.com
   ```

### Q4: 如何调试支付回调？

**方法 1**: 查看 Laravel 日志

```bash
railway run tail -f storage/logs/laravel.log
```

**方法 2**: 在回调方法中添加日志

```php
public function notifyUrl(Request $request)
{
    \Log::info('支付回调', [
        'request' => $request->all(),
        'headers' => $request->headers->all(),
    ]);
    // ... 处理逻辑
}
```

**方法 3**: 使用 Webhook 调试工具

- [RequestBin](https://requestbin.com/)
- [Webhook.site](https://webhook.site/)

临时将回调地址改为这些工具的 URL，查看支付平台发送的数据。

### Q5: 不同支付方式的 payway 参数有哪些？

**支付宝**:
- `alipayscan`: 扫码支付
- `aliweb`: 电脑网站支付
- `aliwap`: 手机网站支付

**微信**:
- `wxpay`: 扫码支付
- `wxwap`: 手机网站支付

**其他支付方式**: 查看对应控制器的 `gateway()` 方法中的 `switch` 语句

### Q6: 支付金额验证失败？

**错误信息**: "订单金额不一致"

**原因**: 回调中的支付金额与订单金额不匹配

**检查**:

1. 数据库中的订单金额：
   ```sql
   SELECT order_sn, actual_price FROM orders WHERE order_sn = 'ABC123';
   ```

2. 支付平台的订单金额

3. 确保都是 **相同的货币单位**（元或分）

---

## 总结

### 配置清单

✅ **Railway 环境变量**:
```
APP_URL=https://your-app.up.railway.app
```

✅ **后台支付网关配置**:
- 支付方式名称
- 商户号
- 商户密钥/私钥
- **pay_handleroute**（必须与路由匹配）

✅ **支付平台配置**（如需要）:
- 回调地址：`{APP_URL}/pay/{gateway}/notify_url`

### 关键代码位置

| 功能 | 文件路径 |
|-----|---------|
| 支付路由 | `routes/common/pay.php` |
| 订单创建 | `app/Service/OrderProcessService.php:312` |
| 支付基类 | `app/Http/Controllers/PayController.php` |
| 支付宝控制器 | `app/Http/Controllers/Pay/AlipayController.php` |
| 回调处理 | `app/Http/Controllers/Pay/AlipayController.php:77` |
| 订单完成 | `app/Service/OrderProcessService.php:385` |
| 自动发货 | `app/Service/OrderProcessService.php:489` |

### 支付流程总览

```
用户下单
  → 创建订单 (OrderProcessService::createOrder)
  → 跳转支付页面 (PayController::redirectGateway)
  → 生成支付参数 (AlipayController::gateway)
  → 用户完成支付
  → 支付平台回调 (AlipayController::notifyUrl)
  → 验证签名和金额
  → 完成订单 (OrderProcessService::completedOrder)
  → 发货并发送邮件
```

---

**祝你使用愉快！如有问题请查看日志或联系技术支持。** 🚀
