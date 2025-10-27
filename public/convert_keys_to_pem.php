<?php
/**
 * 将纯文本密钥转换为完整 PEM 格式并保存到数据库
 * 访问: https://faka.opwan.ai/convert_keys_to_pem.php
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

    echo "=== 转换前 ===\n\n";
    echo "merchant_key 长度: " . strlen($pay->merchant_key) . " 字符\n";
    echo "merchant_key 前50字符: " . substr($pay->merchant_key, 0, 50) . "...\n";
    echo "merchant_key 是否有头尾: " . (strpos($pay->merchant_key, '-----BEGIN') !== false ? '是' : '否') . "\n\n";

    echo "merchant_pem 长度: " . strlen($pay->merchant_pem) . " 字符\n";
    echo "merchant_pem 前50字符: " . substr($pay->merchant_pem, 0, 50) . "...\n";
    echo "merchant_pem 是否有头尾: " . (strpos($pay->merchant_pem, '-----BEGIN') !== false ? '是' : '否') . "\n\n";

    // 转换支付宝公钥
    if (strpos($pay->merchant_key, '-----BEGIN') === false) {
        echo "=== 转换 merchant_key (支付宝公钥) ===\n\n";
        $cleanAliKey = str_replace([
            '-----BEGIN PUBLIC KEY-----',
            '-----END PUBLIC KEY-----',
            "\r", "\n", ' '
        ], '', $pay->merchant_key);

        $aliKeyLines = str_split($cleanAliKey, 64);
        $formattedAliKey = "-----BEGIN PUBLIC KEY-----\n" .
            implode("\n", $aliKeyLines) .
            "\n-----END PUBLIC KEY-----";

        echo "转换后长度: " . strlen($formattedAliKey) . " 字符\n";
        echo "转换后内容:\n";
        echo $formattedAliKey . "\n\n";

        // 测试公钥是否有效
        $resource = @openssl_pkey_get_public($formattedAliKey);
        if ($resource === false) {
            echo "❌ 转换后的公钥无效！\n";
            while ($msg = openssl_error_string()) {
                echo "   - {$msg}\n";
            }
            exit;
        } else {
            echo "✅ 转换后的公钥有效\n\n";
            $pay->merchant_key = $formattedAliKey;
        }
    } else {
        echo "merchant_key 已经是完整 PEM 格式，无需转换\n\n";
    }

    // 转换应用私钥
    if (strpos($pay->merchant_pem, '-----BEGIN') === false) {
        echo "=== 转换 merchant_pem (应用私钥) ===\n\n";
        $cleanPrivKey = str_replace([
            '-----BEGIN RSA PRIVATE KEY-----',
            '-----END RSA PRIVATE KEY-----',
            '-----BEGIN PRIVATE KEY-----',
            '-----END PRIVATE KEY-----',
            "\r", "\n", ' '
        ], '', $pay->merchant_pem);

        $firstChars = substr($cleanPrivKey, 0, 6);
        echo "私钥前6字符: {$firstChars}\n";

        $privKeyLines = str_split($cleanPrivKey, 64);

        // 根据前缀判断格式
        if (strpos($firstChars, 'MIIEv') === 0 || strpos($firstChars, 'MIIC') === 0) {
            echo "判断为 PKCS#1 格式，使用 RSA PRIVATE KEY\n";
            $formattedPrivKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
                implode("\n", $privKeyLines) .
                "\n-----END RSA PRIVATE KEY-----";
        } else {
            echo "判断为 PKCS#8 格式，使用 PRIVATE KEY\n";
            $formattedPrivKey = "-----BEGIN PRIVATE KEY-----\n" .
                implode("\n", $privKeyLines) .
                "\n-----END PRIVATE KEY-----";
        }

        echo "转换后长度: " . strlen($formattedPrivKey) . " 字符\n";
        echo "转换后前200字符:\n";
        echo substr($formattedPrivKey, 0, 200) . "\n...\n\n";

        // 测试私钥是否有效
        $resource = @openssl_pkey_get_private($formattedPrivKey);
        if ($resource === false) {
            echo "❌ 转换后的私钥无效！\n";
            while ($msg = openssl_error_string()) {
                echo "   - {$msg}\n";
            }
            exit;
        } else {
            echo "✅ 转换后的私钥有效\n";
            $details = openssl_pkey_get_details($resource);
            echo "密钥位数: " . $details['bits'] . " bits\n\n";
            $pay->merchant_pem = $formattedPrivKey;
        }
    } else {
        echo "merchant_pem 已经是完整 PEM 格式，无需转换\n\n";
    }

    // 保存到数据库
    echo "=== 保存到数据库 ===\n\n";
    $pay->save();

    echo "✅ 转换完成！密钥已保存为完整 PEM 格式\n\n";
    echo "=== 转换后 ===\n\n";
    echo "merchant_key 长度: " . strlen($pay->merchant_key) . " 字符\n";
    echo "merchant_pem 长度: " . strlen($pay->merchant_pem) . " 字符\n\n";
    echo "现在可以测试支付了！\n";

} catch (\Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
