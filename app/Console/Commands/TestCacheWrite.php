<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestCacheWrite extends Command
{
    protected $signature = 'cache:test-write';
    protected $description = 'Test cache write functionality for system settings';

    public function handle()
    {
        $this->info('=== 缓存读写测试 ===');
        $this->info('');

        // 检查缓存驱动
        $driver = config('cache.default');
        $this->line("缓存驱动: {$driver}");

        // 检查缓存目录
        if ($driver === 'file') {
            $cacheDir = storage_path('framework/cache/data');
            $this->line("缓存目录: {$cacheDir}");

            if (!is_dir($cacheDir)) {
                $this->warn("缓存目录不存在，尝试创建...");
                if (@mkdir($cacheDir, 0755, true)) {
                    $this->info("✓ 缓存目录创建成功");
                } else {
                    $this->error("❌ 缓存目录创建失败");
                    return 1;
                }
            } else {
                $this->info("✓ 缓存目录存在");
            }

            // 检查目录权限
            if (is_writable($cacheDir)) {
                $this->info("✓ 缓存目录可写");
            } else {
                $this->error("❌ 缓存目录不可写");
                return 1;
            }
        }
        $this->info('');

        // 测试写入
        $this->line('【测试 1: 写入缓存】');
        $testData = [
            'test_key' => 'test_value_' . time(),
            'host' => 'smtp.test.com',
            'port' => '587',
        ];

        try {
            $result = Cache::put('cache-test', $testData);
            $this->info("✓ 写入成功 (返回值: " . var_export($result, true) . ")");
        } catch (\Exception $e) {
            $this->error("❌ 写入失败: " . $e->getMessage());
            return 1;
        }
        $this->info('');

        // 测试读取
        $this->line('【测试 2: 读取缓存】');
        try {
            $retrieved = Cache::get('cache-test');
            if ($retrieved) {
                $this->info("✓ 读取成功");
                $this->line("数据: " . json_encode($retrieved, JSON_UNESCAPED_UNICODE));

                if ($retrieved['test_key'] === $testData['test_key']) {
                    $this->info("✓ 数据完整性验证通过");
                } else {
                    $this->error("❌ 数据不一致");
                }
            } else {
                $this->error("❌ 读取失败（返回空）");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ 读取失败: " . $e->getMessage());
            return 1;
        }
        $this->info('');

        // 测试 system-setting 缓存
        $this->line('【测试 3: 读取 system-setting 缓存】');
        $systemSetting = Cache::get('system-setting');
        if ($systemSetting) {
            $this->info("✓ system-setting 缓存存在");
            $this->line("邮件配置:");
            $this->line("  Host: " . ($systemSetting['host'] ?? '未设置'));
            $this->line("  Port: " . ($systemSetting['port'] ?? '未设置'));
            $this->line("  Username: " . ($systemSetting['username'] ?? '未设置'));
        } else {
            $this->warn("⚠️  system-setting 缓存为空（尚未在管理后台保存过）");
        }
        $this->info('');

        // 清理测试数据
        Cache::forget('cache-test');
        $this->line('【清理】测试数据已删除');
        $this->info('');

        $this->info('✅ 缓存功能正常！');
        return 0;
    }
}
