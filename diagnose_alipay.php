<?php
/**
 * 支付宝配置诊断脚本
 * 用法：php diagnose_alipay.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== 支付宝配置诊断 ===\n\n";

// 1. 检查环境变量
echo "1. 环境配置\n";
echo "   APP_URL: " . env('APP_URL') . "\n";
echo "   APP_ENV: " . env('APP_ENV') . "\n";
echo "   LOG_CHANNEL: " . env('LOG_CHANNEL', 'stack') . "\n\n";

// 2. 检查支付宝配置
echo "2. 支付宝支付配置\n";
try {
    $payGateway = \App\Models\Pay::where('pay_check', 'alipay')
        ->where('is_open', 1)
        ->first();

    if (!$payGateway) {
        echo "   ❌ 未找到启用的支付宝支付配置\n\n";
        exit(1);
    }

    echo "   ✅ 支付方式ID: {$payGateway->id}\n";
    echo "   ✅ 支付名称: {$payGateway->pay_name}\n";
    echo "   ✅ App ID: {$payGateway->merchant_id}\n";
    echo "   ✅ 支付宝公钥长度: " . strlen($payGateway->merchant_key) . " 字符\n";
    echo "   ✅ 应用私钥长度: " . strlen($payGateway->merchant_pem) . " 字符\n";
    echo "   ✅ 处理路由: {$payGateway->pay_handleroute}\n";
    echo "   ✅ 是否启用: " . ($payGateway->is_open ? '是' : '否') . "\n\n";

    // 3. 生成回调地址
    echo "3. 回调地址\n";
    echo "   异步通知: " . url($payGateway->pay_handleroute . '/notify_url') . "\n";
    echo "   同步返回: " . url($payGateway->pay_handleroute . '/return_url') . "\n\n";

    // 4. 检查最近订单
    echo "4. 最近的支付宝订单\n";
    $recentOrders = \App\Models\Order::where('pay_id', $payGateway->id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['order_sn', 'status', 'actual_price', 'created_at']);

    if ($recentOrders->isEmpty()) {
        echo "   暂无订单\n\n";
    } else {
        foreach ($recentOrders as $order) {
            $statusText = [
                1 => '待支付',
                2 => '已完成',
                3 => '已失败',
                4 => '待确认'
            ][$order->status] ?? '未知';

            echo "   - {$order->order_sn}: {$statusText} (¥{$order->actual_price}) - {$order->created_at}\n";
        }
        echo "\n";
    }

    // 5. 测试签名验证（模拟）
    echo "5. 配置验证\n";

    if (strlen($payGateway->merchant_key) < 200) {
        echo "   ⚠️  警告：支付宝公钥太短（{$payGateway->merchant_key}字符），可能配置错误\n";
        echo "       支付宝公钥通常应该是 300+ 字符\n";
    } else {
        echo "   ✅ 支付宝公钥长度正常\n";
    }

    if (strlen($payGateway->merchant_pem) < 1000) {
        echo "   ⚠️  警告：应用私钥太短（{$payGateway->merchant_pem}字符），可能配置错误\n";
        echo "       应用私钥通常应该是 1500+ 字符\n";
    } else {
        echo "   ✅ 应用私钥长度正常\n";
    }

    if ($payGateway->pay_handleroute !== '/pay/alipay') {
        echo "   ❌ 错误：处理路由应该是 /pay/alipay\n";
    } else {
        echo "   ✅ 处理路由正确\n";
    }

    echo "\n";

    // 6. 检查路由
    echo "6. 路由检查\n";
    $routes = \Route::getRoutes();
    $alipayRoutes = [];

    foreach ($routes as $route) {
        $uri = $route->uri();
        if (strpos($uri, 'alipay') !== false) {
            $methods = implode('|', $route->methods());
            $alipayRoutes[] = "$methods $uri";
        }
    }

    if (empty($alipayRoutes)) {
        echo "   ❌ 未找到支付宝相关路由\n";
    } else {
        foreach ($alipayRoutes as $routeInfo) {
            echo "   ✅ $routeInfo\n";
        }
    }

    echo "\n=== 诊断完成 ===\n";

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
