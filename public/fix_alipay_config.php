<?php
/**
 * 修复官方支付宝配置 (ID=2)
 * 访问: https://faka.opwan.ai/fix_alipay_config.php?ali_key=支付宝公钥&app_key=应用私钥
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$aliKey = $_GET['ali_key'] ?? '';
$appKey = $_GET['app_key'] ?? '';

if (empty($aliKey) || empty($appKey)) {
    echo "使用方法:\n";
    echo "https://faka.opwan.ai/fix_alipay_config.php?ali_key=支付宝公钥&app_key=应用私钥\n\n";
    echo "参数说明:\n";
    echo "- ali_key: 支付宝公钥（从支付宝后台\"查看支付宝公钥\"获取，约392字符）\n";
    echo "- app_key: 应用私钥（你自己生成的，约1600字符）\n\n";
    echo "这个脚本会:\n";
    echo "1. 更新 ID=2 的官方支付宝配置\n";
    echo "2. 启用 ID=2\n";
    echo "3. 禁用 ID=14 的易支付配置\n";
    exit;
}

try {
    echo "=== 开始修复配置 ===\n\n";

    // 1. 更新 ID=2 的官方支付宝配置
    $officialPay = \App\Models\Pay::find(2);

    if (!$officialPay) {
        echo "❌ 错误: 未找到 ID=2 的配置\n";
        exit;
    }

    echo "1. 更新 ID=2 ({$officialPay->pay_name})\n";
    echo "   旧配置:\n";
    echo "   - merchant_key 长度: " . strlen($officialPay->merchant_key) . " 字符\n";
    echo "   - merchant_pem 长度: " . strlen($officialPay->merchant_pem) . " 字符\n";
    echo "   - is_open: " . ($officialPay->is_open ? '是' : '否') . "\n\n";

    // 更新配置
    $officialPay->merchant_id = '2021006104605238';
    $officialPay->merchant_key = $aliKey;
    $officialPay->merchant_pem = $appKey;
    $officialPay->pay_handleroute = '/pay/alipay';
    $officialPay->is_open = 1; // 启用
    $officialPay->save();

    echo "   ✅ 已更新并启用\n";
    echo "   新配置:\n";
    echo "   - App ID: {$officialPay->merchant_id}\n";
    echo "   - merchant_key 长度: " . strlen($officialPay->merchant_key) . " 字符\n";
    echo "   - merchant_pem 长度: " . strlen($officialPay->merchant_pem) . " 字符\n";
    echo "   - pay_handleroute: {$officialPay->pay_handleroute}\n";
    echo "   - is_open: 是\n\n";

    // 2. 禁用 ID=14 的易支付配置
    $yipay = \App\Models\Pay::find(14);

    if ($yipay) {
        echo "2. 禁用 ID=14 ({$yipay->pay_name})\n";
        $yipay->is_open = 0;
        $yipay->save();
        echo "   ✅ 已禁用易支付配置\n\n";
    } else {
        echo "2. 未找到 ID=14 的易支付配置\n\n";
    }

    // 3. 验证配置
    echo "=== 验证配置 ===\n";
    echo "访问检查页面确认: https://faka.opwan.ai/check_alipay_config.php\n\n";

    echo "=== 完成 ===\n";
    echo "✅ 官方支付宝配置已启用！\n";
    echo "✅ 易支付配置已禁用！\n\n";
    echo "下一步:\n";
    echo "1. 创建新订单测试\n";
    echo "2. 使用支付宝支付\n";
    echo "3. 查看 Railway 日志确认回调成功\n";

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
