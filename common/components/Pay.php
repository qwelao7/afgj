<?php

namespace common\components;

include_once("pay/weixin/sdkv3/WxPay.Api.php");
include_once("pay/weixin/sdkv3/WxPay.JsApiPay.php");

use Yii;
/**
 * Description of Pay
 *
 * @author Don.T
 */
class Pay extends \yii\base\Object {
    
    /**
     * 微信支付
     * @param type $openid      用户openId
     * @param type $outTradeNo  商户订单号
     * @param type $price       支付金额
     */
    public function weiXin($openId, $orderId, $price) {
        $outTradeNo = $orderId.'_'.($price*100);
        $tools = new \JsApiPay();
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("商户订单号:".$orderId);
        $input->SetAttach($orderId);
        $input->SetOut_trade_no($outTradeNo);
        $input->SetTotal_fee($price*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("afgj_goods");
        $input->SetNotify_url(Yii::$app->request->hostInfo.'/callback/wei-xin-pay');
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        
        return $jsApiParameters;
    }
    
    /**
     * 微信支付回调
     * @param type $xml
     */
    public function weiXinCallback() {
        //使用通用通知接口
        $notify = new pay\weixin\PayNotifyCallBack();
        $notify->Handle(false);
    }
}
