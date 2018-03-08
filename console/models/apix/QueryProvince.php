<?php
namespace console\models\apix;

/**
*  查询缴费省份
* http://p.apix.cn/apixlife/pay/utility/query_province
**/
class QueryProvince extends Base{
	private $api = "http://p.apix.cn/apixlife/pay/utility/query_province";

	public function query(){
		return $this->httpRequest($this->api);
	}
}