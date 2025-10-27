<?php
/**
 * 创建官方支付宝配置
 * 访问: https://faka.opwan.ai/create_alipay_official.php?ali_key=支付宝公钥&app_key=应用私钥
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$aliKey = $_GET['ali_key'] ?? '';
$appKey = $_GET['app_key'] ?? '';

if (empty($aliKey) || empty($appKey)) {
    echo "使用方法:\n";
    echo "https://faka.opwan.ai/create_alipay_official.php?ali_key=支付宝公钥&app_key=应用私钥\n\n";
    echo "参数说明:\n";
    echo "- ali_key: 支付宝公钥（从支付宝后台获取，约392字符）\n";
    echo "- app_key: 应用私钥（你自己生成的，约1600字符）\n";
    exit;
}

try {
    // 检查是否已存在官方支付宝配置
    $existing = \App\Models\Pay::where('pay_check', 'alipay')
        ->where('pay_handleroute', '/pay/alipay')
        ->first();

    if ($existing) {
        echo "找到现有的官方支付宝配置 (ID: {$existing->id})\n";
        echo "正在更新...\n\n";

        $existing->merchant_id = '2021006104605238';
        $existing->merchant_key = $aliKey;
        $existing->merchant_pem = $appKey;
        $existing->is_open = 1;
        $existing->save();

        $pay = $existing;
        $action = '更新';
    } else {
        echo "未找到官方支付宝配置，正在创建...\n\n";

        $pay = new \App\Models\Pay();
        $pay->pay_name = '官方支付宝';
        $pay->merchant_id = '2021006104605238';
        $pay->merchant_key = $aliKey;
        $pay->merchant_pem = $appKey;
        $pay->pay_check = 'alipay';
        $pay->pay_client = 1; // PC端
        $pay->pay_method = 1; // 跳转
        $pay->pay_handleroute = '/pay/alipay';
        $pay->is_open = 1;
        $pay->save();

        $action = '创建';
    }

    echo "✅ {$action}成功！\n\n";
    echo "=== 配置信息 ===\n";
    echo "ID: {$pay->id}\n";
    echo "支付方式名称: {$pay->pay_name}\n";
    echo "App ID: {$pay->merchant_id}\n";
    echo "支付宝公钥长度: " . strlen($pay->merchant_key) . " 字符\n";
    echo "应用私钥长度: " . strlen($pay->merchant_pem) . " 字符\n";
    echo "处理路由: {$pay->pay_handleroute}\n";
    echo "是否启用: " . ($pay->is_open ? '是' : '否') . "\n\n";

    echo "=== 下一步 ===\n";
    echo "1. 禁用易支付配置（避免混淆）\n";
    echo "2. 创建新订单测试\n";
    echo "3. 查看日志确认回调成功\n";

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
