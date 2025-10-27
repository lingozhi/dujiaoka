<?php
/**
 * 查询支付宝订单状态并完成订单
 * 访问: https://faka.opwan.ai/query_and_complete_order.php?order_sn=订单号
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$orderSN = $_GET['order_sn'] ?? '';

if (empty($orderSN)) {
    echo "使用方法:\n";
    echo "https://faka.opwan.ai/query_and_complete_order.php?order_sn=订单号\n\n";
    echo "示例:\n";
    echo "https://faka.opwan.ai/query_and_complete_order.php?order_sn=JYHHAVUUNV8KSNCG\n";
    exit;
}

try {
    echo "=== 查询订单 {$orderSN} ===\n\n";

    // 获取订单
    $orderService = app(\App\Service\OrderService::class);
    $order = $orderService->detailOrderSN($orderSN);

    if (!$order) {
        echo "❌ 订单不存在\n";
        exit;
    }

    echo "订单信息:\n";
    echo "- 订单号: {$order->order_sn}\n";
    echo "- 金额: {$order->actual_price}\n";
    echo "- 状态: " . ($order->status == 1 ? '待支付' : ($order->status == 2 ? '已完成' : '其他')) . "\n";
    echo "- 支付方式ID: {$order->pay_id}\n\n";

    if ($order->status == 2) {
        echo "✅ 订单已经完成，无需处理\n";
        exit;
    }

    // 获取支付配置
    $payService = app(\App\Service\PayService::class);
    $payGateway = $payService->detail($order->pay_id);

    if (!$payGateway) {
        echo "❌ 支付配置不存在\n";
        exit;
    }

    if ($payGateway->pay_handleroute != '/pay/alipay') {
        echo "❌ 不是支付宝支付订单\n";
        exit;
    }

    echo "=== 查询支付宝订单状态 ===\n\n";

    // 配置
    $config = [
        'app_id' => $payGateway->merchant_id,
        'ali_public_key' => $payGateway->merchant_key,
        'private_key' => $payGateway->merchant_pem,
    ];

    $pay = \Yansongda\Pay\Pay::alipay($config);

    // 查询订单
    $result = $pay->find($orderSN);

    echo "支付宝返回:\n";
    echo json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    $tradeStatus = $result->trade_status ?? '';
    $totalAmount = $result->total_amount ?? '';
    $tradeNo = $result->trade_no ?? '';

    echo "交易状态: {$tradeStatus}\n";
    echo "支付金额: {$totalAmount}\n";
    echo "支付宝交易号: {$tradeNo}\n\n";

    if ($tradeStatus == 'TRADE_SUCCESS' || $tradeStatus == 'TRADE_FINISHED') {
        echo "=== 订单已支付，开始完成订单 ===\n\n";

        $orderProcessService = app(\App\Service\OrderProcessService::class);
        $orderProcessService->completedOrder($orderSN, $totalAmount, $tradeNo);

        echo "✅ 订单完成成功！\n\n";
        echo "查看订单: https://faka.opwan.ai/detail-order-sn/{$orderSN}\n";
    } else {
        echo "⚠️  订单未支付或状态异常: {$tradeStatus}\n";
    }

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
