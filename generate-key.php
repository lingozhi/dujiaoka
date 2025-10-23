<?php
/**
 * Laravel APP_KEY 生成器
 * 运行方式：php generate-key.php
 */

// 生成 32 字节的随机数据
$key = random_bytes(32);

// Base64 编码
$base64Key = base64_encode($key);

// 输出 Laravel 格式的 APP_KEY
echo "base64:" . $base64Key . PHP_EOL;
echo PHP_EOL;
echo "请将上面生成的密钥设置为 Railway 的 APP_KEY 环境变量" . PHP_EOL;
