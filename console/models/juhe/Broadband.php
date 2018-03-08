<?php
namespace console\models\juhe;

/**
* 
* 
**/
class Broadband extends Base{
	private $api = 'http://op.juhe.cn/ofpay/broadband/onlineorder';
	protected $params = ['teltype'=>'1','phoneno'=>'','pervalue'=>'','chargetype'=>'1','orderid'=>'','key'=>'','sign'=>''];

	public function setTeltype($teltype){
		$this->params['teltype'] = $teltype;
		return $this;
	}

	public function setPhoneno($phoneno){
		$this->params['phoneno'] = $phoneno;
		return $this;
	}

	public function setPervalue($pervalue){
		$this->params['pervalue'] = $pervalue;
		return $this;
	}

	public function setChargetype($chargetype){
		$this->params['chargetype'] = $chargetype;
		return $this;
	}

	public function setOrderid($orderid){
		$this->params['orderid'] = $orderid;
		return $this;
	}

	public function query(){
		$this->params['sign'] = $this->sign();
		return $this->httpRequest($this->api,$this->params);
	}

	private function sign(){
		return md5($this->OpenId.$this->AppKey.$this->params['phoneno'].$this->params['pervalue'].$this->params['orderid']);
	}
}