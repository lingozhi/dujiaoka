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

        // 优先使用缓存配置，如果缓存为空则使用环境变量（Railway 持久化配置）
        $mailConfig = [
            'driver' => $sysConfig['driver'] ?? env('MAIL_MAILER', 'smtp'),
            'host' => $sysConfig['host'] ?? env('MAIL_HOST', ''),
            'port' => $sysConfig['port'] ?? env('MAIL_PORT', '465'),
            'username' => $sysConfig['username'] ?? env('MAIL_USERNAME', ''),
            'from'      =>  [
                'address'   =>   $sysConfig['from_address'] ?? env('MAIL_FROM_ADDRESS', ''),
                'name'      =>  $sysConfig['from_name'] ?? env('MAIL_FROM_NAME', '独角发卡')
            ],
            'password' => $sysConfig['password'] ?? env('MAIL_PASSWORD', ''),
            'encryption' => $sysConfig['encryption'] ?? env('MAIL_ENCRYPTION', '')
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
