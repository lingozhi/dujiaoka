<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SetSystemMailConfig extends Command
{
    protected $signature = 'system:set-mail-config';
    protected $description = 'Set email configuration in system settings cache';

    public function handle()
    {
        $this->info('=== 设置邮件配置到系统缓存 ===');
        $this->info('');

        // 获取现有配置
        $config = Cache::get('system-setting', []);

        // 设置邮件配置
        $mailConfig = [
            'driver' => 'smtp',
            'host' => 'smtp.feishu.cn',
            'port' => '465',
            'username' => 'no-reply@opwan.ai',
            'password' => 'Y5H2MrTLzJfFUH0a',
            'encryption' => 'ssl',
            'from_address' => 'no-reply@opwan.ai',
            'from_name' => '独角数卡',
        ];

        // 合并配置
        $config = array_merge($config, $mailConfig);

        // 保存到缓存
        Cache::put('system-setting', $config);

        $this->info('✓ 邮件配置已保存到系统缓存！');
        $this->info('');

        // 验证配置
        $saved = Cache::get('system-setting');
        $this->line('【已保存的配置】');
        foreach ($mailConfig as $key => $value) {
            $displayValue = ($key === 'password') ? '***已设置***' : $value;
            $actual = $saved[$key] ?? '未找到';
            if ($actual === $value) {
                $this->line("✓ {$key}: {$displayValue}");
            } else {
                $this->error("❌ {$key}: 保存失败");
            }
        }

        $this->info('');
        $this->info('✓ 配置完成！现在可以发送邮件了。');
        $this->info('');
        $this->comment('测试邮件发送：');
        $this->line('php artisan email:test your_email@example.com');

        return 0;
    }
}
