<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckSystemConfig extends Command
{
    protected $signature = 'system:check-config';
    protected $description = 'Check system configuration in cache';

    public function handle()
    {
        $this->info('=== System Configuration Check ===');
        $this->info('');

        $config = Cache::get('system-setting');

        if (!$config) {
            $this->error('❌ 系统配置未找到！');
            $this->warn('请在管理后台重新保存系统设置');
            $this->info('');
            $this->info('访问：/admin/system-setting');
            return 1;
        }

        $this->info('✓ 系统配置已加载');
        $this->info('');

        // 检查邮件配置
        $this->line('【邮件配置】');
        $mailFields = [
            'driver' => 'Driver',
            'host' => 'Host',
            'port' => 'Port',
            'username' => 'Username',
            'password' => 'Password',
            'encryption' => 'Encryption',
            'from_address' => 'From Address',
            'from_name' => 'From Name',
        ];

        $mailConfigured = true;
        foreach ($mailFields as $key => $label) {
            $value = $config[$key] ?? null;
            if (empty($value)) {
                $this->error("❌ {$label}: 未配置");
                $mailConfigured = false;
            } else {
                $displayValue = ($key === 'password') ? '***已设置***' : $value;
                $this->line("✓ {$label}: {$displayValue}");
            }
        }

        $this->info('');

        if (!$mailConfigured) {
            $this->warn('⚠️  邮件配置不完整！');
            $this->info('');
            $this->info('请在管理后台 系统设置 → 邮件设置 中填写以下配置：');
            $this->line('');
            $this->line('Driver: smtp');
            $this->line('Host: smtp.feishu.cn');
            $this->line('Port: 465');
            $this->line('Username: no-reply@opwan.ai');
            $this->line('Password: Y5H2MrTLzJfFUH0a');
            $this->line('Encryption: ssl');
            $this->line('From Address: no-reply@opwan.ai');
            $this->line('From Name: claudex');
            return 1;
        }

        $this->info('✓ 所有邮件配置已正确设置！');
        $this->info('');
        $this->comment('可以使用以下命令测试邮件发送：');
        $this->line('php artisan email:test your_email@example.com');

        return 0;
    }
}
