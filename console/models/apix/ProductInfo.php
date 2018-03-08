<?php
namespace console\models\apix;

/**
* 查询缴费方式
* http://p.apix.cn/apixlife/pay/utility/recharge_way
* $params  corpid 缴费单位ID 
* 
**/
class ProductInfo extends Base{
	private $api = 'http://p.apix.cn/apixlife/pay/utility/product_info';
	protected $params = ['provid'=>'v2056','cityid'=>'v2058','type'=>'','corpid'=>''];

	public function query(){
		$url = $this->toUrl($this->api,$this->params);
		return $this->httpRequest($url);
	}
}