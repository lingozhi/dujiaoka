<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SetFeishuMailConfig extends Command
{
    protected $signature = 'mail:set-feishu';
    protected $description = 'Set Feishu email configuration directly to cache';

    public function handle()
    {
        $this->info('=== 设置飞书邮箱配置 ===');
        $this->info('');

        // 飞书邮箱配置（使用新密码）
        $config = [
            'driver' => 'smtp',
            'host' => 'smtp.feishu.cn',
            'port' => '465',
            'username' => 'no-reply@opwan.ai',
            'password' => 'e4ggOjilNVagDnOn',
            'encryption' => 'ssl',
            'from_address' => 'no-reply@opwan.ai',
            'from_name' => '独角数卡',
        ];

        $this->line('即将保存以下配置到缓存:');
        foreach ($config as $key => $value) {
            $displayValue = ($key === 'password') ? str_repeat('*', strlen($value)) : $value;
            $this->line("  {$key}: {$displayValue}");
        }
        $this->info('');

        try {
            // 获取现有配置
            $existingConfig = Cache::get('system-setting', []);

            // 合并邮件配置
            $newConfig = array_merge($existingConfig, $config);

            // 保存到缓存
            $result = Cache::put('system-setting', $newConfig);

            $this->info('✓ 配置已保存到缓存！');
            $this->info('');

            // 验证保存
            $saved = Cache::get('system-setting');
            if ($saved && isset($saved['host']) && $saved['host'] === 'smtp.feishu.cn') {
                $this->info('✓ 验证通过：配置已成功保存');
                $this->line("  Host: {$saved['host']}");
                $this->line("  Port: {$saved['port']}");
                $this->line("  Username: {$saved['username']}");
            } else {
                $this->error('❌ 验证失败：配置保存后读取不正确');
                $this->line('Saved config: ' . json_encode($saved));
                return 1;
            }

            $this->info('');
            $this->info('现在测试 SMTP 连接...');
            $this->info('');

            // 测试连接
            $this->call('mail:test-connection', [
                'host' => 'smtp.feishu.cn',
                '--port' => 465
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ 保存配置失败：' . $e->getMessage());
            return 1;
        }
    }
}
