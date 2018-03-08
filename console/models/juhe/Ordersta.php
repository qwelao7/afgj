<?php
namespace console\models\juhe;

/**
* 
* 
**/
class Ordersta extends Base{
	private $api = 'http://op.juhe.cn/ofpay/broadband/ordersta';
	protected $params = ['orderid'=>'1','key'=>''];

	public function setOrderid($orderid){
		$this->params['orderid'] = $orderid;
		return $this;
	}

	public function query(){
		return $this->httpRequest($this->api,$this->params);
	}
}