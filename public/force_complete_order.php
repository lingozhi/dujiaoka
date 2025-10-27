<?php
/**
 * 强制完成订单（跳过签名验证）
 * 访问: https://faka.opwan.ai/force_complete_order.php?order_sn=订单号&amount=金额&trade_no=支付宝交易号
 *
 * 警告：使用前请确认订单确实已在支付宝后台显示支付成功！
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$orderSN = $_GET['order_sn'] ?? '';
$amount = $_GET['amount'] ?? '';
$tradeNo = $_GET['trade_no'] ?? '';

if (empty($orderSN)) {
    echo "使用方法:\n";
    echo "https://faka.opwan.ai/force_complete_order.php?order_sn=订单号&amount=金额&trade_no=支付宝交易号\n\n";
    echo "示例:\n";
    echo "https://faka.opwan.ai/force_complete_order.php?order_sn=JYHHAVUUNV8KSNCG&amount=0.01&trade_no=2025102722001462541451876907\n\n";
    echo "⚠️  警告：使用前请先在支付宝后台确认订单确实已支付成功！\n";
    exit;
}

try {
    echo "=== 强制完成订单 ===\n\n";
    echo "订单号: {$orderSN}\n";
    echo "金额: {$amount}\n";
    echo "支付宝交易号: {$tradeNo}\n\n";

    // 获取订单
    $orderService = app(\App\Service\OrderService::class);
    $order = $orderService->detailOrderSN($orderSN);

    if (!$order) {
        echo "❌ 订单不存在\n";
        exit;
    }

    echo "当前订单状态: ";
    switch ($order->status) {
        case 1:
            echo "待支付\n\n";
            break;
        case 2:
            echo "已完成\n\n";
            echo "✅ 订单已经完成，无需处理\n";
            echo "查看订单: https://faka.opwan.ai/detail-order-sn/{$orderSN}\n";
            exit;
        default:
            echo "其他状态({$order->status})\n\n";
    }

    // 如果没有提供金额，使用订单金额
    if (empty($amount)) {
        $amount = $order->actual_price;
        echo "使用订单金额: {$amount}\n";
    }

    // 如果没有提供交易号，使用订单号
    if (empty($tradeNo)) {
        $tradeNo = 'MANUAL_' . $orderSN;
        echo "使用手动交易号: {$tradeNo}\n";
    }

    echo "\n=== 开始完成订单 ===\n\n";

    $orderProcessService = app(\App\Service\OrderProcessService::class);

    try {
        $orderProcessService->completedOrder($orderSN, $amount, $tradeNo);

        echo "✅ 订单完成成功！\n\n";

        // 重新查询订单状态
        $order = $orderService->detailOrderSN($orderSN);
        echo "新订单状态: ";
        switch ($order->status) {
            case 1:
                echo "待支付（未成功更新？）\n";
                break;
            case 2:
                echo "已完成 ✅\n";
                break;
            default:
                echo "其他状态({$order->status})\n";
        }

        echo "\n查看订单: https://faka.opwan.ai/detail-order-sn/{$orderSN}\n";

    } catch (\Exception $e) {
        echo "❌ 完成订单失败: " . $e->getMessage() . "\n";
        echo "\n详细错误:\n";
        echo $e->getTraceAsString() . "\n";
    }

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
