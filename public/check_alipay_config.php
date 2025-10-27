<?php
/**
 * 支付宝配置检查页面
 * 访问: https://faka.opwan.ai/check_alipay_config.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

echo "=== 支付宝配置检查 ===\n\n";

try {
    $payGateway = \App\Models\Pay::where('pay_check', 'alipay')
        ->where('is_open', 1)
        ->first();

    if (!$payGateway) {
        echo "❌ 未找到启用的支付宝配置\n";
        exit;
    }

    echo "✅ 支付方式ID: {$payGateway->id}\n";
    echo "✅ 支付名称: {$payGateway->pay_name}\n";
    echo "✅ App ID: {$payGateway->merchant_id}\n\n";

    // 检查 merchant_key（支付宝公钥）
    echo "=== merchant_key (支付宝公钥) ===\n";
    echo "长度: " . strlen($payGateway->merchant_key) . " 字符\n";
    if (strlen($payGateway->merchant_key) > 0) {
        echo "前30字符: " . substr($payGateway->merchant_key, 0, 30) . "...\n";
        echo "后30字符: ..." . substr($payGateway->merchant_key, -30) . "\n";

        // 检查格式问题
        if (strpos($payGateway->merchant_key, '-----BEGIN') !== false) {
            echo "⚠️  警告: 包含 -----BEGIN，应该去掉头尾！\n";
        }
        if (strpos($payGateway->merchant_key, "\n") !== false) {
            echo "⚠️  警告: 包含换行符，应该去掉所有换行！\n";
        }
        if (strpos($payGateway->merchant_key, ' ') !== false) {
            echo "⚠️  警告: 包含空格，应该去掉所有空格！\n";
        }
    } else {
        echo "❌ 为空！\n";
    }
    echo "\n";

    // 检查 merchant_pem（应用私钥）
    echo "=== merchant_pem (应用私钥) ===\n";
    echo "长度: " . strlen($payGateway->merchant_pem) . " 字符\n";
    if (strlen($payGateway->merchant_pem) > 0) {
        echo "前30字符: " . substr($payGateway->merchant_pem, 0, 30) . "...\n";
        echo "后30字符: ..." . substr($payGateway->merchant_pem, -30) . "\n";
    } else {
        echo "❌ 为空！\n";
    }
    echo "\n";

    // 判断可能的问题
    echo "=== 可能的问题 ===\n";

    $mk_len = strlen($payGateway->merchant_key);
    $mp_len = strlen($payGateway->merchant_pem);

    if ($mk_len == 0) {
        echo "❌ merchant_key 为空！必须填写支付宝公钥\n";
    } elseif ($mk_len < 200) {
        echo "⚠️  merchant_key 太短（{$mk_len}字符），可能配置错误\n";
    } elseif ($mk_len > 500) {
        echo "⚠️  merchant_key 太长（{$mk_len}字符），可能包含了多余内容\n";
    } else {
        echo "✅ merchant_key 长度正常（{$mk_len}字符）\n";
    }

    if ($mp_len == 0) {
        echo "❌ merchant_pem 为空！必须填写应用私钥\n";
    } elseif ($mp_len < 1000) {
        echo "⚠️  merchant_pem 太短（{$mp_len}字符），可能填成了公钥\n";
    } elseif ($mp_len > 2000) {
        echo "⚠️  merchant_pem 太长（{$mp_len}字符），可能包含了多余内容\n";
    } else {
        echo "✅ merchant_pem 长度正常（{$mp_len}字符）\n";
    }

    echo "\n=== 下一步 ===\n";
    echo "1. 访问支付宝开放平台: https://open.alipay.com/\n";
    echo "2. 控制台 → 应用 (App ID: {$payGateway->merchant_id})\n";
    echo "3. 开发信息 → 接口加签方式\n";
    echo "4. 点击【查看支付宝公钥】按钮（不是看应用公钥！）\n";
    echo "5. 复制弹出的公钥内容（不要头尾）\n";
    echo "6. 填入后台 merchant_key 字段\n";

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
}
