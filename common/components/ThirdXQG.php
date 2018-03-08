<?php
/**
 * 小钳工推送服务
 * User: xuyi
 * Date: 10/20
 */

namespace common\components;

use common\models\ar\order\EcsOrderGoods;
use common\models\ar\order\EcsOrderInfo;
use common\models\ar\system\EcsRegion;
use common\models\ar\third\HllThirdOrder;
use yii\helpers\ArrayHelper;
use Yii;

class ThirdXQG
{
	private $error;

	/**
	 * @return mixed
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @param mixed $error
	 */
	public function setError($error,$force=false)
	{
		if ($force){
			$this->error = $error;
		}else{
			$this->error .= ' '.$error.' |';
		}

	}

	public function pullOrderToThird($order_sn)
	{
		//获取key
		$res = $this->third_login();
		if ($res['code'] != 10000 || !$res['datas']['loginKey']) {
			$this->setError('请求token错误');
			return false;
		}
		$token = $res['datas']['loginKey'];

		//判断历史记录
		$order = EcsOrderInfo::findOne(['order_sn' => $order_sn]);
		$log = HllThirdOrder::findOne(['order_id' => $order->order_sn, 'valid' => '1']);
		if ($log && $log['order_push_status'] == 'success') {
			$this->setError('已经推送过');
			return false;
		}
		if (!$log) {
			$log = new HllThirdOrder();
			$log->order_id = $order->order_sn;
		}

		//推送订单
		$res = $this->third_send_order($token, $order->order_sn);
		$log->order_push_last_time = date('Y-m-d H:i:s',time());
		if (($res['code'] != 10000) || !($res['datas']['thirdPartyID'])) {
			$log->order_push_status = 'push_failed';
			$log->save();
			$this->setError('推送订单错误');
			return false;
		}
		$log->order_push_status = 'success';
		$log->third_work_order_id = $res['datas']['thirdPartyID'];
		$log->save();
		return true;


	}

	public function pullOrderFromDb($data)
	{
		$error = '';
		foreach ($data as $v){
			$res = $this->pullOrderToThird($v['order_sn']);
			if ($res){

			}else{
				$error[$v['order_sn']] = $this->getError();
				$this->setError('',true);
			}
		}
		if ($error){
			return $error;
		}else{
			return true;
		}

	}
	private function third_send_order($token, $order_sn,$goods_id=null)
	{
		$order = EcsOrderInfo::findOne(['order_sn' => $order_sn]);
		if (!$order) {
			$this->setError('错误的订单');
			return false;
		}
		if ($goods_id){
			$goods = [

			];
		}else{
			$goods = EcsOrderGoods::find()->select('ecs_order_goods.*')->leftJoin('ecs_goods', 'ecs_goods.goods_id=ecs_order_goods.goods_id')->where([
				'ecs_order_goods.order_id' => $order->order_id,
				'brand_id' => 3
			])->all();
		}

		if (!$goods) {
			$this->setError('没有合作的产品');
			return false;
		}
		$detail = [];
		$content= '';
		foreach ($goods as $g){
			$detail[] = ['goodsID' => $g['goods_sn'],'count' => $g['goods_number']];
			$content .= ' '.$g['goods_name'];
		}
		$address = EcsRegion::getName($order['country']).' '. EcsRegion::getName($order['province']). ' '.EcsRegion::getName($order['city']).' '.$order['address'];;
		$data = [
			'loginKey' => $token,
			'payState' => 'SUCCESS',
			'contact' => $order['consignee'],
			'contactMobile' => $order['mobile'],
			'serviceDate' => '待预约',
			'address' => $address,
			'serviceContent' => '服务内容:'.$content,
			'thirdOrderID' => $order->order_sn,
			'detailJsonArray' => json_encode($detail),
		];


		$curl = curl_init();
		$url = "http://test.wap.xiaoqiangong.com/APP/thirdParty/addWorkOrder.htm";
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => [
				"cache-control: no-cache",
			],
		]);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);
		if ($err) {
			$this->setError("cURL Error #:" . $err);
			return false;
		} else {
			return ArrayHelper::toArray(json_decode($response));
		}
	}

	private function third_login()
	{
		$curl = curl_init();
		$url = "http://test.wap.xiaoqiangong.com/APP/thirdParty/thirdPartyLogin.htm";
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_HTTPHEADER => [
				"cache-control: no-cache",
			],
		]);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, [
			'loginName' => 'yunzhen',
			'loginPass' => 'yunzhen!@#$',
		]);
		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);
		if ($err) {
			$this->setError("cURL Error #:" . $err);
			return false;
		} else {
			return ArrayHelper::toArray(json_decode($response));
		}
	}
}