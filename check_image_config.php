<?php
/**
 * 检查实际的图片配置
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== 当前图片存储配置检查 ===\n\n";

// 1. 检查 APP_URL
$appUrl = config('app.url');
echo "1. APP_URL 配置: " . ($appUrl ?: '(空)') . "\n\n";

// 2. 检查 admin disk 配置
$adminDisk = config('filesystems.disks.admin');
echo "2. Admin Disk 配置:\n";
echo "   - driver: {$adminDisk['driver']}\n";
echo "   - root: {$adminDisk['root']}\n";
echo "   - url: {$adminDisk['url']}\n\n";

// 3. 检查 admin 上传目录配置
$uploadConfig = config('admin.upload');
echo "3. Admin Upload 配置:\n";
echo "   - disk: {$uploadConfig['disk']}\n";
echo "   - image目录: {$uploadConfig['directory']['image']}\n";
echo "   - file目录: {$uploadConfig['directory']['file']}\n\n";

// 4. 模拟上传一张图片后的路径
$testImageName = 'abaa5f2818a93d6564e72a4782c1cf28.png';
$imageDirectory = $uploadConfig['directory']['image'];

echo "4. 模拟图片上传:\n";
echo "   - 原文件名: test.png\n";
echo "   - 生成文件名: $testImageName\n";
echo "   - 存储目录: $imageDirectory\n";
echo "   - 相对路径: $imageDirectory/$testImageName\n\n";

// 5. 使用 Storage 生成 URL
try {
    $storage = \Storage::disk('admin');
    $url = $storage->url("$imageDirectory/$testImageName");
    echo "5. Storage::disk('admin')->url() 生成的URL:\n";
    echo "   $url\n\n";
} catch (\Exception $e) {
    echo "5. 错误: {$e->getMessage()}\n\n";
}

// 6. 检查数据库中实际保存的格式
echo "6. 数据库中图片字段保存格式:\n";
try {
    $goods = \App\Models\Goods::orderBy('id', 'desc')->first();
    if ($goods && $goods->picture) {
        echo "   - 数据库值: {$goods->picture}\n";
        echo "   - picture_ulr() 生成: " . picture_ulr($goods->picture) . "\n";
    } else {
        echo "   - 暂无商品数据\n";
    }
} catch (\Exception $e) {
    echo "   - 错误: {$e->getMessage()}\n";
}

echo "\n=== 诊断完成 ===\n";
