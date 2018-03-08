<?php
namespace console\models\apix;

/**
* 查询账户欠费
* http://p.apix.cn/apixlife/pay/utility/query_owe
* $params type 缴费类型
**/
class QueryOwe extends Base{
	private $api = 'http://p.apix.cn/apixlife/pay/utility/query_owe';
	protected $params = ['provname'=>'','cityname'=>'','type'=>'','corpid'=>'','corpname'=>'','account'=>'','cardid'=>''];
	private $config = [];

	 //001：水费、002：电费、003：燃气费
	public function setType($type){		
		$this->params['type'] = $type;
		$this->config = $this->config($type);
		return $this;
	}

	public function setAccount($account){
		$this->params['account'] = $account;
		return $this;
	}

	public function query(){
		$this->params['provname'] = urlencode($this->config['ProvinceName']);
		$this->params['cityname'] = urlencode($this->config['CityName']);
		$this->params['corpid'] = $this->config['PayUnitId'];
		$this->params['corpname'] = urlencode($this->config['PayUnitName']);
		$this->params['cardid'] = $this->config['ProductId'];

		$url = $this->toUrl($this->api,$this->params);
		return $this->httpRequest($url);
	}
}