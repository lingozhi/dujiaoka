<?php

namespace App\Http\Controllers\Pay;

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\PayController;
use Illuminate\Http\Request;
use Yansongda\Pay\Pay;

class AlipayController extends PayController
{

    /**
     * 支付宝支付网关
     *
     * @param string $payway
     * @param string $orderSN
     */
    public function gateway(string $payway, string $orderSN)
    {
        try {
            // 加载网关
            $this->loadGateWay($orderSN, $payway);
            $config = [
                'app_id' => $this->payGateway->merchant_id,
                'ali_public_key' => $this->payGateway->merchant_key,
                'private_key' => $this->payGateway->merchant_pem,
                'notify_url' => url($this->payGateway->pay_handleroute . '/notify_url'),
                'return_url' => url('detail-order-sn', ['orderSN' => $this->order->order_sn]),
                'http' => [ // optional
                    'timeout' => 10.0,
                    'connect_timeout' => 10.0,
                ],
            ];
            $order = [
                'out_trade_no' => $this->order->order_sn,
                'total_amount' => (float)$this->order->actual_price,
                'subject' => $this->order->order_sn
            ];
            switch ($payway){
                case 'zfbf2f':
                case 'alipayscan':
                    try{
                        $result = Pay::alipay($config)->scan($order)->toArray();
                        $result['payname'] = $this->order->order_sn;
                        $result['actual_price'] = (float)$this->order->actual_price;
                        $result['orderid'] = $this->order->order_sn;
                        $result['jump_payuri'] = $result['qr_code'];
                        return $this->render('static_pages/qrpay', $result, __('dujiaoka.scan_qrcode_to_pay'));
                    } catch (\Exception $e) {
                        return $this->err(__('dujiaoka.prompt.abnormal_payment_channel') . $e->getMessage());
                    }
                case 'aliweb':
                    try{
                        $result = Pay::alipay($config)->web($order);
                        return $result;
                    } catch (\Exception $e) {
                        return $this->err(__('dujiaoka.prompt.abnormal_payment_channel') . $e->getMessage());
                    }
                case 'aliwap':
                    try{
                        $result = Pay::alipay($config)->wap($order);
                        return $result;
                    } catch (\Exception $e) {
                        return $this->err(__('dujiaoka.prompt.abnormal_payment_channel') . $e->getMessage());
                    }
            }
        } catch (RuleValidationException $exception) {
            return $this->err($exception->getMessage());
        }
    }


    /**
     * 异步通知
     */
    public function notifyUrl(Request $request)
    {
        // 记录回调数据（调试用）
        \Log::info('支付宝回调接收', [
            'method' => $request->method(),
            'data' => $request->all(),
            'ip' => $request->ip(),
            'headers' => $request->headers->all()
        ]);

        $orderSN = $request->input('out_trade_no');
        if (!$orderSN) {
            \Log::error('支付宝回调缺少订单号', ['data' => $request->all()]);
            return 'error';
        }

        $order = $this->orderService->detailOrderSN($orderSN);
        if (!$order) {
            \Log::error('支付宝回调订单不存在', ['orderSN' => $orderSN]);
            return 'error';
        }

        // 检查订单是否已经完成
        if ($order->status == \App\Models\Order::STATUS_COMPLETED) {
            \Log::info('支付宝回调订单已完成', ['orderSN' => $orderSN]);
            return 'success';
        }

        $payGateway = $this->payService->detail($order->pay_id);
        if (!$payGateway) {
            \Log::error('支付宝回调支付网关不存在', ['pay_id' => $order->pay_id]);
            return 'error';
        }

        if($payGateway->pay_handleroute != '/pay/alipay'){
            \Log::error('支付宝回调路由不匹配', [
                'expected' => '/pay/alipay',
                'actual' => $payGateway->pay_handleroute
            ]);
            return 'fail';
        }

        $config = [
            'app_id' => $payGateway->merchant_id,
            'ali_public_key' => $payGateway->merchant_key,
            'private_key' => $payGateway->merchant_pem,
        ];

        \Log::info('支付宝回调配置', [
            'app_id' => $payGateway->merchant_id,
            'has_ali_public_key' => !empty($payGateway->merchant_key),
            'has_private_key' => !empty($payGateway->merchant_pem)
        ]);

        $pay = Pay::alipay($config);
        try{
            // 验证签名
            $result = $pay->verify();

            \Log::info('支付宝回调签名验证成功', [
                'orderSN' => $orderSN,
                'trade_status' => $result->trade_status,
                'trade_no' => $result->trade_no ?? null,
                'total_amount' => $result->total_amount ?? null
            ]);

            if ($result->trade_status == 'TRADE_SUCCESS' || $result->trade_status == 'TRADE_FINISHED') {
                try {
                    $this->orderProcessService->completedOrder(
                        $result->out_trade_no,
                        $result->total_amount,
                        $result->trade_no
                    );
                    \Log::info('支付宝订单处理成功', [
                        'orderSN' => $orderSN,
                        'amount' => $result->total_amount,
                        'trade_no' => $result->trade_no
                    ]);
                } catch (\Exception $e) {
                    \Log::error('支付宝订单处理失败', [
                        'orderSN' => $orderSN,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return 'fail';
                }
            } else {
                \Log::warning('支付宝回调状态非成功', [
                    'orderSN' => $orderSN,
                    'trade_status' => $result->trade_status
                ]);
            }
            return 'success';
        } catch (\Exception $exception) {
            \Log::error('支付宝回调异常', [
                'orderSN' => $orderSN,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            return 'fail';
        }
    }



}
