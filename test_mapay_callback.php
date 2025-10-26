<?php
/**
 * 码支付回调测试脚本
 * 用于测试回调签名验证是否正确
 */

// 从URL中获取的实际回调参数
$data = [
    'money' => '1',
    'name' => 'XOME8AATV5YT2XOK',
    'out_trade_no' => 'XOME8AATV5YT2XOK',
    'pid' => '10178',
    'sign' => '38709fd86796a10c5003f00b2063ae5b',
    'sign_type' => 'MD5',
    'trade_no' => '20251027001115359945',
    'trade_status' => 'TRADE_SUCCESS',
    'type' => 'alipay'
];

$key = '40OuICCg5aUcnPrLN5GB';

// 验证签名
ksort($data);
reset($data);
$sign = '';
foreach ($data as $k => $val) {
    if ($k == "sign" || $k == "sign_type" || $val == "") continue;
    if ($sign != '') {
        $sign .= "&";
    }
    $sign .= "$k=$val";
}

echo "签名字符串: $sign\n\n";

$calculatedSign = md5($sign . $key);

echo "计算的签名: $calculatedSign\n";
echo "接收的签名: {$data['sign']}\n\n";

if ($calculatedSign == $data['sign']) {
    echo "✅ 签名验证成功！\n";
} else {
    echo "❌ 签名验证失败！\n";
}

echo "\n--- 模拟发送回调请求 ---\n";
echo "curl -X POST 'https://dujiaoka-production-c6cf.up.railway.app/pay/mapay/notify_url' \\\n";
foreach ($data as $k => $v) {
    echo "  -d '$k=$v' \\\n";
}
echo "\n";
