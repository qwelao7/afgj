<?php
/**
 *
 * @author: XuYi
 * @date: 2016-10-18
 * @version: $Id$
 */
namespace api\modules\third\models;

use common\models\ar\order\EcsOrderGoods;
use common\models\ar\order\EcsOrderInfo;
use yii\base\Model;

class UpdateOrderModel extends Model
{

	public $order_id; //ecs 订单号
	public $xqg_order_id; //小钳工 订单号
	public $work_sn;   //小钳工 工单号
	public $state;   //小钳工 状态
	public $remark;   //小钳工 备注
	public $service_date;   //小钳工 服务时间
	public $begin_time;   //小钳工 开始时间
	public $end_time;   //小钳工 结束时间
	public $detail;   //小钳工


	private $error = '';
	private $order = null;
	private $goods = null;

	/**
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @param string $error
	 */
	public function setError($error)
	{
		$this->error .= $error;
	}


	public function rules()
	{
		return [
			[['order_id', 'work_sn', 'state','xqg_order_id'], 'required'],
			[['order_id','work_sn','state','remark','service_date'], 'string'],
			[['detail'], 'safe'],
		];
	}

	public function attributeLabels()
	{
		return [
			'order_id' => 'third_order_id',
			'work_sn' => 'third_party_sn',
			'xqg_order_id' => 'order_id',
			'state' => 'state',
		];
	}

	/**
	 * 修改订单状态和备注
	 * @return bool
	 */
	public function updateOrder()
	{
		$translation = EcsOrderInfo::getDb()->beginTransaction();
		try{
			if (!($this->order instanceof EcsOrderInfo)) {
				if (!$this->getOrder()) {
					$this->setError('订单不存在');
					return false;
				};
			}
			if (in_array($this->state, ['0', '1', '2', '3', '4', '5'])) {
				//订单的状态;0未确认,1确认,2已取消,3无效,4退货
				if ($this->state == 1) {
					$this->order->shipping_status = '2';
				}

				if ($this->state == 0) {
					$this->order->shipping_status = '0';
				}

				if ($this->state == 2) {
					$this->order->shipping_status = '0';
					$this->order->order_status = '2';
					//todo 取消订单流程
				}
				$this->order->order_status = '';
				$this->order->postscript = $this->remark;
				//

			} else {
				$this->setError('state不在约定之中');
				return false;
			}
			if ($this->service_date) {
				$this->order->to_buyer = '将于' . date('Y-m-d', $this->service_date) . '为您提供服务';
			}
			if (!$this->order->inv_type){
				$this->order->inv_type = '0';
			}

			if ($this->order->save()){
				$translation->commit();
				return true;
			}else{
				$this->setError('系统异常');
				$translation->rollBack();
				return false;
			}
		}catch (\Exception $e){
			$this->setError('数据库异常');
			$translation->rollBack();
			return false;
		}


	}

	public function makeNewOrder()
	{

	}

	private function getOrder()
	{
		$order = EcsOrderInfo::findOne(['order_sn' => $this->order_id]);
		if (!$order) {
			return false;
		}
		$this->order = $order;
		$this->getGoods();
		return true;
	}

	private function getGoods()
	{
		$goods = EcsOrderGoods::find()->where(['order_id' => $this->order_id])->all();
		if (!$goods) {
			return false;
		}
		$this->goods = $goods;
		return true;
	}

}