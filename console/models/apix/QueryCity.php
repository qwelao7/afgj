<?php
namespace console\models\apix;

/**
* 查询缴费城市
* http://p.apix.cn/apixlife/pay/utility/query_city
* $params provid 省份ID
**/
class QueryCity extends Base{
	private $api = "http://p.apix.cn/apixlife/pay/utility/query_city";
	protected $params = ["provid"=>"v2056"];

	public function query(){
		$url = $this->toUrl($this->api,$this->params);
		return $this->httpRequest($url);
	}
}