<?php
namespace console\models\apix;

/**
* 水电煤充值 
* http://p.apix.cn/apixlife/pay/utility/utility_recharge
* $params type 缴费类型(001：水费、002：电费、003：燃气费)
* $params contractid 合同号
* $params paymentday 账期
* $params orderid 账期
* $params fee 费用
* $params sign 签名
* $params callback_url 回调地址
**/
class UtilityRecharge extends Base{
	private $api = 'http://p.apix.cn/apixlife/pay/utility/utility_recharge';	
	private $callback_url = "";
	protected $params = ['provid'=>'','cityid'=>'','type'=>'','corpid'=>'','cardid'=>'','account'=>'','contractid'=>'','paymentday'=>'','orderid'=>'','fee'=>'','sign'=>'','callback_url'=>''];

	public function setType($type){		
		$this->params['type'] = $type;		
		$this->config = $this->config($type);
		return $this;
	}

	public function setAccount($account){
		$this->params['account'] = $account;
		return $this;
	}

	public function setContractid($contractid){
		$this->params['contractid'] = $contractid;
		return $this;
	}

	public function setPaymentday($paymentday){
		$this->params['paymentday'] = $paymentday;
		return $this;
	}

	public function setOrderid($orderid){
		$this->params['orderid'] = $orderid;
		return $this;
	}

	public function setFee($fee){
		$this->params['fee'] = $fee;
		return $this;
	}

	private function sign($params){
		return md5($params['provid'].$params['cityid'].$params['type'].$params['corpid'].$params['cardid'].$params['account'].$params['orderid']);
	}

	public function query(){
		$this->params['provid'] = $this->config['ProvinceId'];
		$this->params['cityid'] = $this->config['CityId'];
		$this->params['corpid'] = $this->config['PayUnitId'];
		$this->params['cardid'] = $this->config['ProductId'];

		$this->params['sign'] = $this->sign($this->params);

		$this->params['callback_url'] = $this->callback_url;

		$url = $this->toUrl($this->api,$this->params);
		return $this->httpRequest($url);
	}

	//回调地址验证签名
	public function checkSign($orderid,$ordertime){
		return md5($this->apixKey.$orderid.$ordertime);
	}
}