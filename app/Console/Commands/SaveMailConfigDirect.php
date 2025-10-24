<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SaveMailConfigDirect extends Command
{
    protected $signature = 'mail:save-config
                            {--host=smtp.feishu.cn}
                            {--port=465}
                            {--username=no-reply@opwan.ai}
                            {--password=e4ggOjilNVagDnOn}
                            {--encryption=ssl}
                            {--from_address=no-reply@opwan.ai}
                            {--from_name=独角数卡}';

    protected $description = 'Save mail configuration directly to cache (bypass admin panel)';

    public function handle()
    {
        $this->info('=== 直接保存邮件配置到缓存 ===');
        $this->info('');

        // 获取现有配置
        $existingConfig = Cache::get('system-setting', []);

        // 邮件配置
        $mailConfig = [
            'driver' => 'smtp',
            'host' => $this->option('host'),
            'port' => $this->option('port'),
            'username' => $this->option('username'),
            'password' => $this->option('password'),
            'encryption' => $this->option('encryption'),
            'from_address' => $this->option('from_address'),
            'from_name' => $this->option('from_name'),
        ];

        $this->line('即将保存以下邮件配置:');
        foreach ($mailConfig as $key => $value) {
            $displayValue = ($key === 'password') ? str_repeat('*', strlen($value)) : $value;
            $this->line("  {$key}: {$displayValue}");
        }
        $this->info('');

        try {
            // 合并配置
            $newConfig = array_merge($existingConfig, $mailConfig);

            // 保存到缓存（永久）
            Cache::forever('system-setting', $newConfig);

            $this->info('✓ 配置已保存！');
            $this->info('');

            // 验证
            $saved = Cache::get('system-setting');
            if ($saved && isset($saved['host'])) {
                $this->info('✓ 验证通过：');
                $this->line("  Host: {$saved['host']}");
                $this->line("  Port: {$saved['port']}");
                $this->line("  Username: {$saved['username']}");
                $this->line("  Encryption: {$saved['encryption']}");
            } else {
                $this->error('❌ 验证失败：配置读取不正确');
                return 1;
            }

            $this->info('');
            $this->line('现在可以使用邮件功能了！');
            $this->info('');
            $this->comment('测试邮件发送：');
            $this->line('php artisan email:test your@email.com');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ 保存失败: ' . $e->getMessage());
            return 1;
        }
    }
}
