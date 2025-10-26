<?php
namespace App\Http\Controllers\Pay;


use App\Exceptions\RuleValidationException;
use App\Http\Controllers\PayController;
use Illuminate\Http\Request;

class MapayController extends PayController
{
    // ⚠️ 硬编码配置 - 码支付 (epay 接口)
    private $hardcodedConfig = [
        'submit_url' => 'https://mzf.akwl.net/xpay/epay/submit.php',  // 提交地址
        'pid' => '10178',                           // 商户ID
        'key' => '40OuICCg5aUcnPrLN5GB',           // 商户密钥
    ];

    public function gateway(string $payway, string $orderSN)
    {
        try {
            // 加载网关（获取订单信息）
            $this->loadGateWay($orderSN, $payway);

            // 支付类型映射
            $payType = '';
            switch ($payway){
                case 'mqq':      // 码QQ
                    $payType = 'qqpay';
                    break;
                case 'mzfb':     // 码支付宝
                    $payType = 'alipay';
                    break;
                case 'mwx':      // 码微信
                default:
                    $payType = 'wxpay';
                    break;
            }

            // 构造标准易支付参数
            $parameter = [
                'pid' => $this->hardcodedConfig['pid'],
                'type' => $payType,
                'out_trade_no' => $this->order->order_sn,
                'notify_url' => url('/pay/mapay/notify_url'),
                'return_url' => url('detail-order-sn', ['orderSN' => $this->order->order_sn]),
                'name' => $this->order->order_sn,
                'money' => (float)$this->order->actual_price,
                'sign' => '',
                'sign_type' => 'MD5'
            ];

            // 生成签名
            ksort($parameter);
            reset($parameter);
            $sign = '';
            foreach ($parameter as $key => $val) {
                if ($key == "sign" || $key == "sign_type" || $val == "") continue;
                if ($sign != '') {
                    $sign .= "&";
                }
                $sign .= "$key=$val";
            }
            $sign = md5($sign . $this->hardcodedConfig['key']);
            $parameter['sign'] = $sign;

            // 构造提交表单
            $sHtml = "<form id='mapaysubmit' name='mapaysubmit' action='" . $this->hardcodedConfig['submit_url'] . "' method='get'>";
            foreach($parameter as $key => $val) {
                $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }
            $sHtml = $sHtml."<input type='submit' value=''></form>";
            $sHtml = $sHtml."<script>document.forms['mapaysubmit'].submit();</script>";

            return $sHtml;
        } catch (RuleValidationException $exception) {
            return $this->err($exception->getMessage());
        }
    }


    public function notifyUrl(Request $request)
    {
        $data = $request->all();

        // 记录回调数据（调试用）
        \Log::info('码支付回调接收', [
            'method' => $request->method(),
            'data' => $data,
            'ip' => $request->ip()
        ]);

        // 获取订单号
        $orderSN = $data['out_trade_no'] ?? '';
        if (!$orderSN) {
            \Log::error('码支付回调缺少订单号', ['data' => $data]);
            return 'fail';
        }

        // 查询订单
        $order = $this->orderService->detailOrderSN($orderSN);
        if (!$order) {
            \Log::error('码支付回调订单不存在', ['orderSN' => $orderSN]);
            return 'fail';
        }

        // 检查订单是否已经完成
        if ($order->status == \App\Models\Order::STATUS_COMPLETED) {
            \Log::info('码支付回调订单已完成', ['orderSN' => $orderSN]);
            return 'success';
        }

        // 验证签名
        ksort($data);
        reset($data);
        $sign = '';
        foreach ($data as $key => $val) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            if ($sign != '') {
                $sign .= "&";
            }
            $sign .= "$key=$val";
        }

        $calculatedSign = md5($sign . $this->hardcodedConfig['key']);

        // 验证签名是否正确
        if (!isset($data['trade_no']) || $calculatedSign != $data['sign']) {
            \Log::error('码支付回调签名验证失败', [
                'data' => $data,
                'calculated_sign' => $calculatedSign,
                'received_sign' => $data['sign'] ?? '',
                'sign_string' => $sign
            ]);
            return 'fail';
        }

        \Log::info('码支付回调签名验证成功', ['orderSN' => $orderSN]);

        // 验证支付状态
        if ($data['trade_status'] == 'TRADE_SUCCESS') {
            try {
                // 完成订单
                $this->orderProcessService->completedOrder(
                    $data['out_trade_no'],  // 订单号
                    $data['money'],          // 支付金额
                    $data['trade_no']        // 支付平台交易号
                );
                \Log::info('码支付订单处理成功', [
                    'orderSN' => $orderSN,
                    'money' => $data['money'],
                    'trade_no' => $data['trade_no']
                ]);
            } catch (\Exception $e) {
                \Log::error('码支付订单处理失败', [
                    'orderSN' => $orderSN,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return 'fail';
            }
        } else {
            \Log::warning('码支付回调状态非成功', [
                'orderSN' => $orderSN,
                'trade_status' => $data['trade_status'] ?? 'unknown'
            ]);
        }

        return 'success';
    }




}


