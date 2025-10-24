<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\MailServiceProvider;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class MailSend implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务最大尝试次数。
     *
     * @var int
     */
    public $tries = 2;

    /**
     * 任务运行的超时时间。
     *
     * @var int
     */
    public $timeout = 30;

    private $to;

    private $content;

    private $title;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $to, string $title, string $content)
    {
        $this->to = $to;
        $this->title = $title;
        $this->content = $content;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $body = $this->content;
        $title = $this->title;
        $sysConfig = cache('system-setting', []);

        // 硬编码的默认配置（作为最终兜底）
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

        // 三层后备机制，确保不会出现空值
        $getValue = function($key, $cacheKey = null) use ($sysConfig, $defaults) {
            $cacheKey = $cacheKey ?? $key;
            // 优先使用缓存配置
            if (!empty($sysConfig[$cacheKey])) {
                return $sysConfig[$cacheKey];
            }
            // 其次使用 config 配置
            $configValue = config('mail.' . $key);
            if (!empty($configValue)) {
                return $configValue;
            }
            // 最后使用硬编码默认值
            return $defaults[$cacheKey];
        };

        $mailConfig = [
            'driver' => $getValue('driver'),
            'host' => $getValue('host'),
            'port' => $getValue('port'),
            'username' => $getValue('username'),
            'from'      =>  [
                'address'   =>   $getValue('from.address', 'from_address'),
                'name'      =>  $getValue('from.name', 'from_name')
            ],
            'password' => $getValue('password'),
            'encryption' => $getValue('encryption')
        ];
        $to = $this->to;
        //  覆盖 mail 配置
        config([
            'mail'  =>  array_merge(config('mail'), $mailConfig)
        ]);
        // 重新注册驱动
        (new MailServiceProvider(app()))->register();
        Mail::send(['html' => 'email.mail'], ['body' => $body], function ($message) use ($to, $title){
            $message->to($to)->subject($title);
        });
    }
}
