<?php
namespace console\models\apix;

/**
* 查询订单状态 
* http://p.apix.cn/apixlife/pay/utility/order_list
* $params type 缴费类型
**/
class OrderState extends Base{
	private $api = 'http://p.apix.cn/apixlife/pay/utility/order_list';
	protected $params = ['orderid'=>''];

	 //001：水费、002：电费、003：燃气费
	public function setOrderid($orderid){		
		$this->params['orderid'] = $orderid;
		return $this;
	}

	public function query(){
		$url = $this->toUrl($this->api,$this->params);
		return $this->httpRequest($url);
	}
}