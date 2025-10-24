<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShowMailConfig extends Command
{
    protected $signature = 'mail:show-config';
    protected $description = 'Show exactly what MailSend.php will use';

    public function handle()
    {
        $this->info('=== MailSend.php 实际使用的配置 ===');
        $this->info('');

        // 完全复制 MailSend.php 的逻辑
        $sysConfig = cache('system-setting', []);

        $this->line('【缓存配置】');
        if (empty($sysConfig)) {
            $this->warn('缓存为空');
        } else {
            $this->line('Host: ' . ($sysConfig['host'] ?? '未设置'));
            $this->line('Port: ' . ($sysConfig['port'] ?? '未设置'));
        }
        $this->info('');

        // 硬编码的默认配置
        $defaults = [
            'driver' => 'smtp',
            'host' => 'smtp.feishu.cn',
            'port' => '465',
            'username' => 'no-reply@opwan.ai',
            'password' => 'Y5H2MrTLzJfFUH0a',
            'encryption' => 'ssl',
            'from_address' => 'no-reply@opwan.ai',
            'from_name' => '独角数卡'
        ];

        // 三层后备机制
        $getValue = function($key, $cacheKey = null) use ($sysConfig, $defaults) {
            $cacheKey = $cacheKey ?? $key;

            $this->line("检查 {$cacheKey}:");

            // 优先使用缓存配置
            if (!empty($sysConfig[$cacheKey])) {
                $this->line("  从缓存获取: {$sysConfig[$cacheKey]}");
                return $sysConfig[$cacheKey];
            }

            // 其次使用 config 配置
            $configValue = config('mail.' . $key);
            if (!empty($configValue)) {
                $this->line("  从 config 获取: {$configValue}");
                return $configValue;
            }

            // 最后使用硬编码默认值
            $defaultValue = $defaults[$cacheKey] ?? '无默认值';
            $this->line("  使用硬编码默认值: {$defaultValue}");
            return $defaults[$cacheKey];
        };

        $this->line('【最终配置（MailSend.php 使用的值）】');
        $this->line('');

        $host = $getValue('host');
        $port = $getValue('port');
        $username = $getValue('username');
        $password = $getValue('password');
        $encryption = $getValue('encryption');
        $from_address = $getValue('from.address', 'from_address');
        $from_name = $getValue('from.name', 'from_name');

        $this->info('');
        $this->line('【总结】');
        $this->line("Host: " . ($host ?: '❌ 空值！'));
        $this->line("Port: " . ($port ?: '❌ 空值！'));
        $this->line("Username: " . ($username ?: '❌ 空值！'));
        $this->line("Password: " . ($password ? str_repeat('*', strlen($password)) : '❌ 空值！'));
        $this->line("Encryption: " . ($encryption ?: '❌ 空值！'));
        $this->line("From Address: " . ($from_address ?: '❌ 空值！'));
        $this->line("From Name: " . ($from_name ?: '❌ 空值！'));

        $this->info('');

        if (empty($host)) {
            $this->error('❌❌❌ 严重错误：Host 为空！');
            $this->error('这不应该发生，因为有硬编码默认值！');
            $this->warn('可能原因：');
            $this->line('1. Railway 还在部署新代码');
            $this->line('2. 代码版本不匹配');

            $this->info('');
            $this->comment('请检查 Railway 部署状态，确认最新 commit 已部署');
        } else {
            $this->info('✅ 配置正常！');
        }

        return 0;
    }
}
