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

class UpdateWorkOrderModel extends Model
{

	public $order_id;
	public $work_sn;   //小钳工 工单号
	public $xqg_order_id; //小钳工 订单号
	public $state;
	public $remark;

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
		$this->error = $error;
	}


	public function rules()
	{
		return [
			[['order_id', 'work_sn', 'state'], 'required'],
			[['order_id', 'work_sn', 'remark', 'state','xqg_order_id'], 'string'],
		];
	}

	public function attributeLabels()
	{
		return [
			'order_id' => 'third_order_id',
			'work_sn' => 'third_party_sn',
			'state' => 'state',
			'xqg_order_id' => 'order_id',
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
			if (in_array($this->state, ['SUCCCESS', 'FAIL', 'WAIT'])) {
				if ($this->state == 'SUCCCESS') {
					if (!$this->xqg_order_id){
						$this->setError('需要小钳工订单号');
						return false;
					}
					$this->order->postscript = $this->remark;
					//todo 在第三方表中记录订单号
				}

				if ($this->state == 'FAIL') {
					$this->order->postscript = $this->remark;
				}

				if ($this->state == 'WAIT') {
					$this->order->postscript = $this->remark;

				}

			} else {
				$this->setError('异常状态');
				return false;
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