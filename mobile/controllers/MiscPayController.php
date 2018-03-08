<?php
namespace mobile\controllers;

use Yii;
use yii\web\Controller;
use EasyWeChat\Payment\Order;

class MiscPayController extends Controller {

    public function actionIndex(){
        $payment = Yii::$app->wechat->payment;

        $attributes = [
            'trade_type'       => 'JSAPI', // JSAPI，NATIVE，APP...
            'body'             => 'iPad mini 16G 白色',
            'detail'           => 'iPad mini 16G 白色',
            'out_trade_no'     => '1217752501201407033233368018',
            'total_fee'        => 5388, // 单位：分
            'notify_url'       => 'http://xxx.com/order-notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
            'openid'           => '当前用户的 openid', // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
        ];
        $order = new Order($attributes);
        $result = $payment->prepare($order);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS'){
            $prepayId = $result->prepay_id;
        }
    }

    public function actionNotify(){

        $response = Yii::$app->wechat->payment->handleNotify(
            function($notify, $successful){
            // 你的逻辑
            return true; // 或者错误消息
        });
        $response;
    }

}
