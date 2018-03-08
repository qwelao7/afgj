<?php
namespace console\models\apix;

/**
* 查询缴费类型
* http://p.apix.cn/apixlife/pay/utility/recharge_type
* $params cityid 城市ID
**/
class RechargeType extends Base{
	private $api = 'http://p.apix.cn/apixlife/pay/utility/recharge_type';
	protected $params = ['provid'=>'v2056','cityid'=>'v2058'];

	public function query(){
		$url = $this->toUrl($this->api,$this->params);
		return $this->httpRequest($url);
	}
}