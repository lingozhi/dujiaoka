<?php
/**
 * 测试密钥格式化
 * 访问: https://faka.opwan.ai/test_key_format.php
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

    echo "=== 原始私钥信息 ===\n\n";
    echo "长度: " . strlen($pay->merchant_pem) . " 字符\n";
    echo "前10字符: " . substr($pay->merchant_pem, 0, 10) . "\n";
    echo "是否包含换行: " . (strpos($pay->merchant_pem, "\n") !== false ? '是' : '否') . "\n";
    echo "是否包含头尾: " . (strpos($pay->merchant_pem, '-----BEGIN') !== false ? '是' : '否') . "\n\n";

    // 方法1: 使用 str_split
    echo "=== 方法1: str_split ===\n\n";
    $cleanKey1 = str_replace([
        '-----BEGIN RSA PRIVATE KEY-----',
        '-----END RSA PRIVATE KEY-----',
        '-----BEGIN PRIVATE KEY-----',
        '-----END PRIVATE KEY-----',
        "\r", "\n", ' '
    ], '', $pay->merchant_pem);

    $keyLines1 = str_split($cleanKey1, 64);
    $formatted1 = "-----BEGIN PRIVATE KEY-----\n" .
        implode("\n", $keyLines1) .
        "\n-----END PRIVATE KEY-----";

    echo "格式化后长度: " . strlen($formatted1) . "\n";
    echo substr($formatted1, 0, 200) . "\n...\n";

    $resource1 = @openssl_pkey_get_private($formatted1);
    if ($resource1 === false) {
        echo "❌ PKCS#8 格式无效\n";
        while ($msg = openssl_error_string()) {
            echo "   - {$msg}\n";
        }
    } else {
        echo "✅ PKCS#8 格式有效\n";
        $details = openssl_pkey_get_details($resource1);
        echo "密钥位数: " . $details['bits'] . " bits\n";
    }
    echo "\n";

    // 方法2: 尝试 PKCS#1 格式
    echo "=== 方法2: PKCS#1 格式 ===\n\n";
    $formatted2 = "-----BEGIN RSA PRIVATE KEY-----\n" .
        implode("\n", $keyLines1) .
        "\n-----END RSA PRIVATE KEY-----";

    echo "格式化后长度: " . strlen($formatted2) . "\n";
    echo substr($formatted2, 0, 200) . "\n...\n";

    $resource2 = @openssl_pkey_get_private($formatted2);
    if ($resource2 === false) {
        echo "❌ PKCS#1 格式无效\n";
        while ($msg = openssl_error_string()) {
            echo "   - {$msg}\n";
        }
    } else {
        echo "✅ PKCS#1 格式有效\n";
        $details = openssl_pkey_get_details($resource2);
        echo "密钥位数: " . $details['bits'] . " bits\n";
    }
    echo "\n";

    // 方法3: 使用 chunk_split
    echo "=== 方法3: chunk_split (PKCS#8) ===\n\n";
    $formatted3 = "-----BEGIN PRIVATE KEY-----\n" .
        trim(chunk_split($cleanKey1, 64, "\n")) .
        "\n-----END PRIVATE KEY-----";

    echo "格式化后长度: " . strlen($formatted3) . "\n";
    echo substr($formatted3, 0, 200) . "\n...\n";

    $resource3 = @openssl_pkey_get_private($formatted3);
    if ($resource3 === false) {
        echo "❌ chunk_split PKCS#8 格式无效\n";
        while ($msg = openssl_error_string()) {
            echo "   - {$msg}\n";
        }
    } else {
        echo "✅ chunk_split PKCS#8 格式有效\n";
        $details = openssl_pkey_get_details($resource3);
        echo "密钥位数: " . $details['bits'] . " bits\n";
    }
    echo "\n";

    // 方法4: 使用 chunk_split (PKCS#1)
    echo "=== 方法4: chunk_split (PKCS#1) ===\n\n";
    $formatted4 = "-----BEGIN RSA PRIVATE KEY-----\n" .
        trim(chunk_split($cleanKey1, 64, "\n")) .
        "\n-----END RSA PRIVATE KEY-----";

    $resource4 = @openssl_pkey_get_private($formatted4);
    if ($resource4 === false) {
        echo "❌ chunk_split PKCS#1 格式无效\n";
        while ($msg = openssl_error_string()) {
            echo "   - {$msg}\n";
        }
    } else {
        echo "✅ chunk_split PKCS#1 格式有效\n";
        $details = openssl_pkey_get_details($resource4);
        echo "密钥位数: " . $details['bits'] . " bits\n";
    }

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
}
