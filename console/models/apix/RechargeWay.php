<?php
namespace console\models\apix;

/**
* 查询缴费方式
* http://p.apix.cn/apixlife/pay/utility/recharge_way
* $params type 缴费类型(燃气费:c2670 水费:c2681 电费:c2680) corpid 缴费单位ID
* 
**/
class RechargeWay extends Base{
	private $api = 'http://p.apix.cn/apixlife/pay/utility/recharge_way';
	protected $params = ['provid'=>'v2056','cityid'=>'v2058','type'=>'','corpid'=>''];

	public function query(){
		$url = $this->toUrl($this->api,$this->params);
		return $this->httpRequest($url);
	}
}