<?php
namespace console\models\apix;

/**
* 查询缴费单位
* http://p.apix.cn/apixlife/pay/utility/recharge_company
* $params type 缴费类型(燃气费:c2670 水费:c2681 电费:c2680)
**/
class RechargeCompany extends Base{
	private $api = 'http://p.apix.cn/apixlife/pay/utility/recharge_company';
	protected $params = ['provid'=>'v2056','cityid'=>'v2058','type'=>''];

	public function query(){
		$url = $this->toUrl($this->api,$this->params);
		return $this->httpRequest($url);
	}
}