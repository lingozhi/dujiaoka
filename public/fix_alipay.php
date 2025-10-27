<?php
/**
 * 修复支付宝配置
 * 访问: https://faka.opwan.ai/fix_alipay.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

header('Content-Type: text/html; charset=utf-8');

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payId = $_POST['pay_id'] ?? 2;
    $aliPublicKey = trim($_POST['ali_public_key'] ?? '');
    $appPrivateKey = trim($_POST['app_private_key'] ?? '');

    $errors = [];

    // 验证支付宝公钥
    if (empty($aliPublicKey)) {
        $errors[] = '支付宝公钥不能为空';
    } else {
        // 清理并测试
        $cleanAliKey = str_replace(['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\r", "\n", ' '], '', $aliPublicKey);
        if (strlen($cleanAliKey) < 200) {
            $errors[] = '支付宝公钥太短，请检查是否完整';
        }
    }

    // 验证应用私钥
    if (empty($appPrivateKey)) {
        $errors[] = '应用私钥不能为空';
    } else {
        // 清理
        $cleanPrivKey = str_replace([
            '-----BEGIN RSA PRIVATE KEY-----',
            '-----END RSA PRIVATE KEY-----',
            '-----BEGIN PRIVATE KEY-----',
            '-----END PRIVATE KEY-----',
            "\r", "\n", ' '
        ], '', $appPrivateKey);

        if (strlen($cleanPrivKey) < 1000) {
            $errors[] = '应用私钥太短（' . strlen($cleanPrivKey) . '字符），请检查是否完整或填成了公钥';
        } else {
            // 测试私钥是否有效
            $testKey = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($cleanPrivKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
            $resource = @openssl_pkey_get_private($testKey);

            if ($resource === false) {
                // 尝试 PKCS#8 格式
                $testKey = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($cleanPrivKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
                $resource = @openssl_pkey_get_private($testKey);
            }

            if ($resource === false) {
                $errors[] = '应用私钥无效！OpenSSL 无法解析，请检查是否填错了内容';
            } else {
                $details = openssl_pkey_get_details($resource);
                if ($details['bits'] < 2048) {
                    $errors[] = '私钥位数太少（' . $details['bits'] . ' bits），建议使用2048位';
                }
            }
        }
    }

    // 如果没有错误，更新数据库
    if (empty($errors)) {
        try {
            $pay = \App\Models\Pay::find($payId);
            if (!$pay) {
                $errors[] = "未找到 ID={$payId} 的配置";
            } else {
                // 保存纯密钥内容（不带头尾）
                $pay->merchant_key = $cleanAliKey;
                $pay->merchant_pem = $cleanPrivKey;
                $pay->is_open = 1; // 启用
                $pay->save();

                echo '<div style="background:#d4edda;color:#155724;padding:20px;margin:20px;border:1px solid #c3e6cb;border-radius:5px;">';
                echo '<h2>✅ 更新成功！</h2>';
                echo '<p>支付宝配置已更新并启用</p>';
                echo '<p>支付宝公钥长度: ' . strlen($cleanAliKey) . ' 字符</p>';
                echo '<p>应用私钥长度: ' . strlen($cleanPrivKey) . ' 字符</p>';
                echo '<p><a href="/test_alipay_key.php">测试密钥</a> | <a href="/">返回首页</a></p>';
                echo '</div>';

                echo '<script>setTimeout(function(){ window.location.href="/"; }, 3000);</script>';
                exit;
            }
        } catch (\Exception $e) {
            $errors[] = '更新失败: ' . $e->getMessage();
        }
    }

    // 显示错误
    if (!empty($errors)) {
        echo '<div style="background:#f8d7da;color:#721c24;padding:20px;margin:20px;border:1px solid #f5c6cb;border-radius:5px;">';
        echo '<h3>❌ 验证失败</h3><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
}

// 显示表单
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>修复支付宝配置</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        textarea { width: 100%; height: 150px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 12px; }
        button { background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .help { color: #666; font-size: 14px; margin-top: 5px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border: 1px solid #bee5eb; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>🔧 修复官方支付宝配置（ID=2）</h1>

    <div class="info">
        <strong>说明：</strong>
        <ul>
            <li>支付宝公钥：从支付宝开放平台 → 应用 → 开发信息 → 点击"<strong>查看支付宝公钥</strong>"获取</li>
            <li>应用私钥：你自己生成的私钥（使用支付宝密钥生成工具生成）</li>
            <li>可以带头尾（-----BEGIN...-----）也可以不带，系统会自动处理</li>
        </ul>
    </div>

    <form method="POST">
        <input type="hidden" name="pay_id" value="2">

        <div class="form-group">
            <label>支付宝公钥（Alipay Public Key）*</label>
            <textarea name="ali_public_key" placeholder="粘贴从支付宝后台"查看支付宝公钥"获取的内容（约392字符）&#10;例如: MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA..."></textarea>
            <div class="help">从支付宝后台获取，不是应用公钥！</div>
        </div>

        <div class="form-group">
            <label>应用私钥（Application Private Key）*</label>
            <textarea name="app_private_key" placeholder="粘贴你自己生成的应用私钥（约1600字符）&#10;例如: MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC..."></textarea>
            <div class="help">你自己生成的私钥，不是应用公钥或支付宝公钥！</div>
        </div>

        <button type="submit">✅ 更新配置并启用</button>
    </form>

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
        <h3>🔗 相关链接</h3>
        <ul>
            <li><a href="https://open.alipay.com/" target="_blank">支付宝开放平台</a></li>
            <li><a href="https://opendocs.alipay.com/common/02kipl" target="_blank">下载密钥生成工具</a></li>
            <li><a href="/test_alipay_key.php">测试当前密钥</a></li>
        </ul>
    </div>
</body>
</html>
