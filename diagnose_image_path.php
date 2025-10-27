<?php
/**
 * 诊断图片路径生成问题
 */

echo "=== 图片路径生成诊断 ===\n\n";

// 模拟不同的 APP_URL 配置
$testConfigs = [
    [
        'name' => '正确配置（Railway生产环境）',
        'app_url' => 'https://dujiaoka-production-c6cf.up.railway.app',
        'expected' => 'https://dujiaoka-production-c6cf.up.railway.app/uploads/product.jpg'
    ],
    [
        'name' => '错误配置（本地环境）',
        'app_url' => 'http://dujiaoka.test',
        'expected' => 'http://dujiaoka.test/uploads/product.jpg'
    ],
    [
        'name' => '未配置（空字符串）',
        'app_url' => '',
        'expected' => '/uploads/product.jpg'
    ],
    [
        'name' => 'Railway未设置环境变量',
        'app_url' => null,
        'expected' => '/uploads/product.jpg'
    ],
];

$imagePath = 'product.jpg';

foreach ($testConfigs as $config) {
    echo "【{$config['name']}】\n";
    echo "APP_URL: " . ($config['app_url'] ?: '(空)') . "\n";

    // 模拟 Storage::disk('admin')->url() 的逻辑
    // config/filesystems.php: 'url' => env('APP_URL').'/uploads'
    if ($config['app_url']) {
        $generatedUrl = $config['app_url'] . '/uploads/' . $imagePath;
    } else {
        $generatedUrl = '/uploads/' . $imagePath;
    }

    echo "生成URL: $generatedUrl\n";
    echo "期望URL: {$config['expected']}\n";

    if ($generatedUrl === $config['expected']) {
        echo "状态: ✅ 符合预期\n";
    } else {
        echo "状态: ❌ 不符合预期\n";
    }

    // 检查是否是相对路径
    if (strpos($generatedUrl, 'http') === false) {
        echo "⚠️ 警告: 这是相对路径，浏览器可能无法正确加载图片！\n";
    }

    echo "\n";
}

echo "=== 问题原因分析 ===\n\n";
echo "如果你在前台看到图片路径是 /uploads/xxx.jpg（相对路径），原因是：\n";
echo "1. Railway 环境变量中没有设置 APP_URL\n";
echo "2. 或者 APP_URL 设置为空字符串\n";
echo "3. 导致 config/filesystems.php 中的配置生成了相对路径\n\n";

echo "=== config/filesystems.php 配置逻辑 ===\n\n";
echo "'admin' => [\n";
echo "    'driver' => 'local',\n";
echo "    'root' => public_path('uploads'),\n";
echo "    'visibility' => 'public',\n";
echo "    'url' => env('APP_URL').'/uploads',  // 如果 APP_URL 为空，这里就是 '/uploads'\n";
echo "],\n\n";

echo "=== 后台 vs 前台的区别 ===\n\n";
echo "后台 (/admin/goods/3/edit):\n";
echo "  - 使用 Dcat Admin 的 image 字段\n";
echo "  - 图片上传后直接显示相对路径也能工作\n";
echo "  - 因为是在同一个域名下访问\n\n";

echo "前台 (首页/商品列表):\n";
echo "  - 使用 picture_ulr() 函数生成图片URL\n";
echo "  - 依赖 Storage::disk('admin')->url()\n";
echo "  - 需要完整的 APP_URL 才能生成正确的绝对路径\n\n";

echo "=== 解决方案 ===\n\n";
echo "必须在 Railway Dashboard 设置环境变量：\n";
echo "  变量名: APP_URL\n";
echo "  变量值: https://dujiaoka-production-c6cf.up.railway.app\n\n";

echo "设置后，图片URL会从：\n";
echo "  /uploads/product.jpg  ❌\n";
echo "变成：\n";
echo "  https://dujiaoka-production-c6cf.up.railway.app/uploads/product.jpg  ✅\n";
