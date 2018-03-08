<?php
namespace console\models\apix;

/**
* 查询用户余额(APIX余额)  
*  http://p.apix.cn/apixlife/pay/utility/user_balance
* $params type 缴费类型
**/
class UserBalance extends Base{
	private $api = 'http://p.apix.cn/apixlife/pay/utility/user_balance';

	public function query(){
		$url = $this->toUrl($this->api,$this->params);
		return $this->httpRequest($url);
	}
}