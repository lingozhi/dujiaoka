<?php
/**
 * 测试支付宝密钥格式
 * 访问: https://faka.opwan.ai/test_alipay_key.php
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

    echo "=== 原始密钥信息 ===\n\n";
    echo "merchant_pem 长度: " . strlen($pay->merchant_pem) . " 字符\n";
    echo "merchant_pem 前50字符: " . substr($pay->merchant_pem, 0, 50) . "...\n\n";

    // 模拟格式化过程
    echo "=== 格式化过程 ===\n\n";

    // 清理密钥
    $cleanKey = str_replace([
        '-----BEGIN RSA PRIVATE KEY-----',
        '-----END RSA PRIVATE KEY-----',
        '-----BEGIN PRIVATE KEY-----',
        '-----END PRIVATE KEY-----',
        "\r",
        "\n",
        ' '
    ], '', $pay->merchant_pem);

    echo "清理后长度: " . strlen($cleanKey) . " 字符\n";
    echo "清理后前50字符: " . substr($cleanKey, 0, 50) . "...\n\n";

    $firstChars = substr($cleanKey, 0, 6);
    echo "前6字符: {$firstChars}\n\n";

    // 判断格式
    if (strpos($firstChars, 'MIIEv') === 0 || strpos($firstChars, 'MIIC') === 0) {
        $format = 'PKCS#1 (RSA PRIVATE KEY)';
        $header = '-----BEGIN RSA PRIVATE KEY-----';
        $footer = '-----END RSA PRIVATE KEY-----';
    } else {
        $format = 'PKCS#8 (PRIVATE KEY)';
        $header = '-----BEGIN PRIVATE KEY-----';
        $footer = '-----END PRIVATE KEY-----';
    }

    echo "判断格式: {$format}\n\n";

    // 格式化
    $formatted = $header . "\n" . wordwrap($cleanKey, 64, "\n", true) . "\n" . $footer;

    echo "=== 格式化后的密钥 ===\n\n";
    echo $formatted . "\n\n";

    // 测试是否能加载为资源
    echo "=== 测试密钥是否有效 ===\n\n";

    $resource = openssl_pkey_get_private($formatted);

    if ($resource === false) {
        echo "❌ 密钥无效！OpenSSL 错误:\n";
        while ($msg = openssl_error_string()) {
            echo "   - {$msg}\n";
        }
        echo "\n";
        echo "这说明 merchant_pem 配置的内容不是有效的私钥！\n";
        echo "请检查是否填成了公钥或者密钥已损坏。\n";
    } else {
        echo "✅ 密钥有效！可以正常使用\n";
        $details = openssl_pkey_get_details($resource);
        echo "密钥类型: " . ($details['type'] == OPENSSL_KEYTYPE_RSA ? 'RSA' : '其他') . "\n";
        echo "密钥位数: " . $details['bits'] . " bits\n";
    }

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
}
