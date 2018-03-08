<?php
namespace console\models\apix;

/**
* 查询历史订单列表 
* http://p.apix.cn/apixlife/pay/utility/order_list
* $params type 缴费类型
**/
class OrdeList extends Base{
	private $api = 'http://p.apix.cn/apixlife/pay/utility/order_list';
	protected $params = ['page'=>'1','page_size'=>'10','account'=>'','orderid'=>''];
	
	public function setPage($page){
		$this->params['page'] = $page;
	}

	public function setPageSize($page_size){
		$this->params['page_size'] = $page_size;
	}

	public function setAccount($account){
		$this->params['account'] = $account;
	}

	public function setOrderid($orderid){
		$this->params['orderid'] = $orderid;
	}

	public function query(){
		$url = $this->toUrl($this->api,$this->params);
		return $this->httpRequest($url);
	}
}