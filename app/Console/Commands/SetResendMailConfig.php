<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SetResendMailConfig extends Command
{
    protected $signature = 'mail:set-resend {api_key}';
    protected $description = 'Set Resend email configuration with API key';

    public function handle()
    {
        $apiKey = $this->argument('api_key');

        if (!str_starts_with($apiKey, 're_')) {
            $this->error('❌ API Key 格式错误！应该以 re_ 开头');
            $this->info('');
            $this->line('请检查你的 Resend API Key 是否正确复制。');
            return 1;
        }

        $this->info('=== 配置 Resend 邮件服务 ===');
        $this->info('');

        // Resend 配置
        $config = [
            'driver' => 'smtp',
            'host' => 'smtp.resend.com',
            'port' => '587',
            'username' => 'resend',
            'password' => $apiKey,
            'encryption' => 'tls',
            'from_address' => 'onboarding@resend.dev',
            'from_name' => '独角数卡',
        ];

        $this->line('即将保存以下配置:');
        foreach ($config as $key => $value) {
            $displayValue = ($key === 'password') ? 're_***************' : $value;
            $this->line("  {$key}: {$displayValue}");
        }
        $this->info('');

        try {
            // 获取现有配置
            $existingConfig = Cache::get('system-setting', []);

            // 合并配置
            $newConfig = array_merge($existingConfig, $config);

            // 保存到缓存
            Cache::forever('system-setting', $newConfig);

            $this->info('✓ Resend 配置已保存！');
            $this->info('');

            // 验证
            $saved = Cache::get('system-setting');
            if ($saved && isset($saved['host']) && $saved['host'] === 'smtp.resend.com') {
                $this->info('✓ 验证通过！');
                $this->line('');
                $this->line('配置详情:');
                $this->line("  Host: {$saved['host']}");
                $this->line("  Port: {$saved['port']}");
                $this->line("  Username: {$saved['username']}");
                $this->line("  Encryption: {$saved['encryption']}");
                $this->line("  From: {$saved['from_address']}");
            } else {
                $this->error('❌ 验证失败');
                return 1;
            }

            $this->info('');
            $this->info('🎉 配置完成！现在测试邮件发送...');
            $this->info('');

            // 提示测试
            $this->line('运行以下命令测试邮件:');
            $this->comment('php artisan email:test your_email@example.com');
            $this->info('');
            $this->line('或访问管理后台测试:');
            $this->comment('/admin/email-test');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ 保存失败: ' . $e->getMessage());
            return 1;
        }
    }
}
