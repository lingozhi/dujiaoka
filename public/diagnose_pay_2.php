<?php
/**
 * 诊断 ID=2 的支付配置
 * 访问: https://faka.opwan.ai/diagnose_pay_2.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

try {
    $pay = \App\Models\Pay::find(2);

    if (!$pay) {
        echo "❌ 未找到 ID=2 的配置\n";
        exit;
    }

    echo "=== ID=2 配置详情 ===\n\n";
    echo "支付方式名称: {$pay->pay_name}\n";
    echo "支付标识: {$pay->pay_check}\n";
    echo "处理路由: {$pay->pay_handleroute}\n";
    echo "是否启用: " . ($pay->is_open ? '是' : '否') . "\n\n";

    echo "=== merchant_id (App ID) ===\n";
    echo "{$pay->merchant_id}\n\n";

    echo "=== merchant_key ===\n";
    echo "长度: " . strlen($pay->merchant_key) . " 字符\n";
    if (strlen($pay->merchant_key) > 0) {
        echo "前50字符: " . substr($pay->merchant_key, 0, 50) . "...\n";
        echo "后30字符: ..." . substr($pay->merchant_key, -30) . "\n";

        // 判断类型
        if (strpos($pay->merchant_key, '-----BEGIN') !== false) {
            echo "⚠️  包含头尾标记（需要去掉）\n";
        }
        if (strpos($pay->merchant_key, "\n") !== false) {
            echo "⚠️  包含换行符（需要去掉）\n";
        }
    } else {
        echo "❌ 为空\n";
    }
    echo "\n";

    echo "=== merchant_pem ===\n";
    echo "长度: " . strlen($pay->merchant_pem) . " 字符\n";
    if (strlen($pay->merchant_pem) > 0) {
        echo "前50字符: " . substr($pay->merchant_pem, 0, 50) . "...\n";
        echo "后30字符: ..." . substr($pay->merchant_pem, -30) . "\n";

        // 判断类型
        if (strpos($pay->merchant_pem, '-----BEGIN') !== false) {
            echo "⚠️  包含头尾标记（需要去掉）\n";
        }
        if (strpos($pay->merchant_pem, "\n") !== false) {
            echo "⚠️  包含换行符（需要去掉）\n";
        }

        // 判断是否是私钥
        $firstChars = substr($pay->merchant_pem, 0, 10);
        if (strpos($firstChars, 'MIIEvQ') === 0 || strpos($firstChars, 'MIICXg') === 0 || strpos($firstChars, 'MIIE') === 0) {
            echo "✅ 看起来是私钥（以 {$firstChars} 开头）\n";
        } elseif (strpos($firstChars, 'MIIBIjAN') === 0) {
            echo "❌ 错误！这是公钥，不是私钥！\n";
            echo "   merchant_pem 必须填写应用私钥（长度约1600字符）\n";
        } else {
            echo "⚠️  无法判断类型\n";
        }
    } else {
        echo "❌ 为空\n";
    }
    echo "\n";

    echo "=== 问题诊断 ===\n";

    $issues = [];

    if (strlen($pay->merchant_key) == 0) {
        $issues[] = "merchant_key 为空";
    } elseif (strlen($pay->merchant_key) < 200) {
        $issues[] = "merchant_key 太短（{$pay->merchant_key}字符）";
    }

    if (strlen($pay->merchant_pem) == 0) {
        $issues[] = "merchant_pem 为空";
    } elseif (strlen($pay->merchant_pem) < 1000) {
        $issues[] = "merchant_pem 太短（{$pay->merchant_pem}字符），可能填成了公钥";
    }

    if (strpos(substr($pay->merchant_pem, 0, 10), 'MIIBIjAN') === 0) {
        $issues[] = "merchant_pem 填成了公钥！应该填私钥";
    }

    if (empty($issues)) {
        echo "✅ 未发现明显问题\n";
    } else {
        echo "发现以下问题:\n";
        foreach ($issues as $issue) {
            echo "- ❌ {$issue}\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
}
