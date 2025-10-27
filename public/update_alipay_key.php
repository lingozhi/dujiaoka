<?php
/**
 * 直接更新支付宝公钥
 * 访问: https://faka.opwan.ai/update_alipay_key.php?key=你的支付宝公钥
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

$key = $_GET['key'] ?? '';

if (empty($key)) {
    echo "使用方法:\n";
    echo "https://faka.opwan.ai/update_alipay_key.php?key=你的支付宝公钥\n\n";
    echo "例如:\n";
    echo "https://faka.opwan.ai/update_alipay_key.php?key=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCg...\n";
    exit;
}

try {
    // 查找支付宝配置（查找所有，不限制是否启用）
    $pays = \App\Models\Pay::where('pay_check', 'alipay')->get();

    if ($pays->isEmpty()) {
        echo "❌ 未找到任何支付宝配置\n";
        exit;
    }

    echo "找到 " . $pays->count() . " 个支付宝配置:\n\n";

    foreach ($pays as $pay) {
        echo "ID: {$pay->id}\n";
        echo "名称: {$pay->pay_name}\n";
        echo "启用状态: " . ($pay->is_open ? '是' : '否') . "\n";
        echo "旧的 merchant_key 长度: " . strlen($pay->merchant_key) . " 字符\n";

        // 更新公钥
        $pay->merchant_key = $key;

        // 如果未启用，自动启用
        if (!$pay->is_open) {
            $pay->is_open = 1;
            echo "✅ 已自动启用\n";
        }

        $pay->save();

        echo "✅ 新的 merchant_key 长度: " . strlen($pay->merchant_key) . " 字符\n";
        echo "✅ 更新成功！\n\n";
    }

    echo "=== 完成 ===\n";
    echo "所有支付宝配置已更新！\n";
    echo "请创建新订单测试。\n";

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
}
