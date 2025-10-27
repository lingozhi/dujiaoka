<?php
/**
 * 测试图片URL生成
 */

// 模拟不同的 APP_URL 配置
$testCases = [
    'http://dujiaoka.test' => '商品图片.jpg',
    'https://dujiaoka-production-c6cf.up.railway.app' => '商品图片.jpg',
    '' => '商品图片.jpg',  // 空的情况
];

echo "=== 图片URL生成测试 ===\n\n";

foreach ($testCases as $appUrl => $file) {
    echo "APP_URL: " . ($appUrl ?: '(空)') . "\n";
    echo "图片文件: $file\n";

    // 模拟 Storage::disk('admin')->url() 的逻辑
    $imageUrl = $appUrl . '/uploads/' . $file;

    echo "生成URL: $imageUrl\n";
    echo "是否正确: " . (strpos($imageUrl, 'https://') === 0 ? '✅ 是' : '❌ 否（生产环境需要 https）') . "\n";
    echo "\n";
}

echo "=== config/filesystems.php 中的配置 ===\n\n";
echo "'admin' => [\n";
echo "    'driver' => 'local',\n";
echo "    'root' => public_path('uploads'),\n";
echo "    'visibility' => 'public',\n";
echo "    'url' => env('APP_URL').'/uploads',  // 👈 这里依赖 APP_URL\n";
echo "],\n\n";

echo "=== picture_ulr() 函数逻辑 ===\n\n";
echo "function picture_ulr(\$file, \$getHost = false)\n";
echo "{\n";
echo "    if (\$getHost) return Storage::disk('admin')->url('');\n";
echo "    return \$file ? Storage::disk('admin')->url(\$file) : url('assets/common/images/default.jpg');\n";
echo "}\n\n";

echo "=== 问题分析 ===\n\n";
echo "1. 如果 Railway 没有设置 APP_URL 环境变量，图片路径会出错\n";
echo "2. 后台上传的图片保存在 public/uploads/ 目录\n";
echo "3. picture_ulr() 生成的URL格式：\$APP_URL/uploads/图片文件名\n";
echo "4. 必须在 Railway 设置正确的 APP_URL，否则图片无法显示\n\n";

echo "=== 解决方案 ===\n\n";
echo "在 Railway Dashboard 设置环境变量：\n";
echo "APP_URL=https://dujiaoka-production-c6cf.up.railway.app\n";
