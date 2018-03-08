<?php
namespace console\models\juhe;

/**
* 查询商品信息
**/
class Fixtelquery extends Base{
	private $api = 'http://op.juhe.cn/ofpay/broadband/fixtelquery';
	protected $params = ['teltype'=>'1','phoneno'=>'','pervalue'=>'','chargetype'=>'1','key'=>''];

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

	public function query(){
		return $this->httpRequest($this->api,$this->params);
	}
}