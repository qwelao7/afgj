<?php

namespace common\components\pay\weixin;

include_once("sdkv3/WxPay.Api.php");
include_once("sdkv3/WxPay.Notify.php");

use Yii;
use common\models\ar\order\ServiceOrder;
use common\models\ar\order\ServiceOrderPayment;
/**
 * Description of PayNotifyCallBack
 *
 * @author Don.T
 */
class PayNotifyCallBack extends \WxPayNotify {
    
	//查询订单
	public function Queryorder($transactionId) {
		$input = new \WxPayOrderQuery();
		$input->SetTransaction_id($transactionId);
		$result = \WxPayApi::orderQuery($input);
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS") {
            //处理订单支付逻辑
            $outTradeNo = explode('_', $result['out_trade_no']);
            $orderId = $outTradeNo[0];
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $order = ServiceOrder::findOne($orderId);
                $order->paystatus = 1;
				$order->updater = $order->user_id;
                $order->save();
                $payment = new ServiceOrderPayment;
                $payment->order_id = $orderId;
                $payment->transaction_id = $result['transaction_id'];
                $payment->pay_type = 1;
                $payment->pay_account = $result['openid'];
                $payment->pay_price = $result['total_fee']/100;
                $payment->pay_remark = $result['attach'];
                $payment->save();
                $transaction->commit();
            } catch (\yii\db\Exception $ex) {
				Yii::error("Callback Queryorder:".$ex->getMessage());
				Yii::error("Callback Queryorder:".$ex->getTraceAsString());
                $transaction->rollback(); 
                return false;
            }
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg) {
		Yii::info("call back:" . json_encode($data),'callback');
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}
		return true;
	}
}
