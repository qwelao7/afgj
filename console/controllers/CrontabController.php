<?php
namespace console\controllers;

use common\components\ThirdXQG;
use common\components\WxTmplMsg;
use common\models\ar\third\HllThirdOrder;
use common\models\ecs\EcsUsers;
use common\models\hll\HllUserCar;
use common\models\hll\HllUserPoints;
use common\models\hll\HllUserCarNotification;
use common\models\hll\UserEquipmentNotification;
use Yii;
use yii\console\Controller;
use yii\db\Query;
use yii\db\Exception;
use common\models\ecs\EcsUserAddress;
/**
 * 定时任务
 * Class CrontabController
 * @package console\controllers
 */
class CrontabController extends Controller
{

	/**
	 * 共享经济顺风车提醒
	 * @author zend.wang
	 * @date  2016-10-25 13:00
	 */
	public function actionRidesharingRemind()
	{

		$redis = Yii::$app->redis;
		Yii::warning('ride_sharing_remind begin:' . f_date(time()));
		while (true) {
			//获取已预约用户信息
			$content = $redis->lpop("ride_sharing_remind");
			if (!$content) break;
			$params = explode(":", $content);
			Yii::warning('ride_sharing_remind lpop:' . $content);
			//如果出发前5分钟,发提醒(车主取消的行程不算)
			//如果出发后30分钟,提醒感谢(车主取消的行程不算)
			$state = WxTmplMsg::rideSharingNotification($params[0], 0, $params[1]);
			Yii::warning('ride_sharing_remind send status:' . $state);
			$len = $redis->llen("ride_sharing_remind");
			if ($state == 2) { //发送不成功 重新加入队列
				$redis->rpush("ride_sharing_remind", $content);
				Yii::warning('ride_sharing_remind failed:' . $content);
				if (!$len) break;
			}
		}
		Yii::warning('ride_sharing_remind end:' . f_date(time()));
	}

	public function actionThirdPush()
	{
		$data = HllThirdOrder::find()->where([
			'valid' => '1',
			'order_push_status' => 'wait'
		])->all();
		$brand_list = [3];
		$list = [];
		foreach ($data as $value){
			$order = EcsOrderInfo::findOne(['order_sn'=>$value['order_id']]);
			if ($order['pay_status']=='2')
			{
				$goods = EcsOrderGoods::getDb()->createCommand('select ecs_goods.brand_id,cart.goods_id from ecs_order_goods as cart left join ecs_goods on ecs_goods.goods_id=cart.goods_id where cart.order_id='.$order['order_id'])->queryAll();
				foreach ($goods as $g){
					if (in_array($g['brand_id'],$brand_list)){
						$list[$value['order_id']] = [
							'order_sn' => $value['order_id'],
//							'goods_id' => $g['goods_id'],
						];
					}
				}

			}
		}
		$model = new ThirdXQG();
		$res = $model->pullOrderFromDb($list);
		if ($res ===true){
			Yii::info(''.date('Y-m-d H:i:s').' 执行 推送第三方 成功' );
		}else{
			Yii::info(''.date('Y-m-d H:i:s').' 执行 推送第三方 失败 原因:'.json_encode($res) );
		}

	}

	/**
	 * 每天定时发送用户车辆提醒
	 */
	public function actionCarNotification () {
		$fields = ['id', 'account_id', 'now_km'];
		$time = date('Y-m-d', time());

		Yii::warning('car_notification_remind begin' . f_date(time()));
		$list = HllUserCar::find()->select($fields)->where(['valid'=>1])->asArray()->all();

		if ($list) {
			foreach ($list as &$item) {
				//更新某个车辆全部警告信息及车辆表的警告状态和警告数量
				$result = HllUserCarNotification::updateWarnningAll($item['id']);
				if ($result) {
					//某辆车的警告提醒
					//重新获取车辆信息
					$car = HllUserCar::find()->where(['id'=>$item['id'], 'valid'=>1])->one();
					$count = HllUserCarNotification::find()->where(['car_id' => $car->id, 'alert_status' => 1, 'valid'=>1])->count();
					if (!empty($car) && $car['alert_status'] == 0 && $car->warnning_num > 0) {
						//获取用户信息
						$user = EcsUsers::getUser($item['account_id'], ['t1.user_id', 't2.openid']);
						if (!$user || $user['openid'] == '') return false;

						//发送模板消息
						$state = WxTmplMsg::carNotice($user, $car, $count);
						//更新车辆警告状态
						if ($state) {
							//变更车辆警告状态
							Yii::$app->db->createCommand()->update('hll_user_car',['alert_status' => 1],
								'id=:id',[':id'=>$car->id])->execute();

							//变更车辆提醒的警告状态
							$sql = 'update hll_user_car_notification set has_alert = 1 where has_alert = 0 and alert_status = 1 and car_id = '.$car->id;
							Yii::$app->db->createCommand($sql)->execute();
						}
					}
				}
			}
		}

		Yii::warning('car_notification_remind end:' . f_date(time()));
	}

	/**
	 * 每月提醒用户更新里程数
	 */
	public function actionUpdateCarKm () {
		$fields = ['id', 'account_id', 'car_num', 'record_km_date'];
		$curTime = time();
		
		Yii::warning('update_car_km_remind begin' . f_date(time()));
		$list = HllUserCar::find()->select($fields)->where(['valid'=>1])->asArray()->all();
		if ($list) {
			foreach ($list as &$item) {
				$next_date = strtotime($item['record_km_date'].'+1 month');
				if ($curTime >= $next_date) {
					//获取用户信息
					$user = EcsUsers::getUser($item['account_id'], ['t1.user_id', 't2.openid']);
					if (!$user) {
						continue;
					}else{
						$state = WxTmplMsg::carUpdateKm($user, $item);
						Yii::warning('update_car_km_remind send status:' . $state);
					}
				}
			}
		}

		Yii::warning('update_car_km_remind end:' . f_date(time()));
	}

	/**
	 * 每天更新设备提醒
	 * @return bool
	 * @throws \yii\db\Exception
	 */
	public function actionEquipmentNotification(){

		Yii::warning('equipment_notification_remind begin' . f_date(time()));
		//更新提醒状态
		$notification_id = UserEquipmentNotification::updateEquipmentInfo();
		if($notification_id){
			foreach($notification_id as $item){
				//获取需发送的的提醒信息及数量
				$notification = (new Query())->select(['t2.id','t2.account_id','t2.address_id','t2.model','t3.name'])
					->from('hll_user_equipment_notification as t1')
					->leftJoin('hll_user_equipment as t2','t2.id = t1.equipment_id')
					->leftJoin('hll_equipment_brand as t3','t3.id = t2.brand')
					->where(['t1.id'=>$item,'t1.alert_status'=>1,'t1.is_send'=>0,'t1.valid'=>1])
					->andWhere(['t2.valid'=>1,'t2.status'=>2])->one();
				//获取房产信息
				if($notification){
					//获取用户信息
					$user = EcsUsers::getUser($notification['account_id'], ['t1.user_id', 't2.openid']);
					if (!$user || $user['openid'] == '') return false;
					//发送模板消息
                    $state = WxTmplMsg::equipmentNotice($user, $notification);
					if ($state) {
						//变更提醒的警告状态
						$notification_id = implode(',',$notification_id);
						$sql = "update hll_user_equipment_notification set is_send = 1 where id IN ($notification_id)";
						Yii::$app->db->createCommand($sql)->execute();
					}
				}
			}
		}
		Yii::warning('car_notification_remind end:' . f_date(time()));
	}

	public function actionCommunityFeedbackNotification(){
		Yii::warning('community_feedback_notification begin' . f_date(time()));

		$list = (new Query())->select(['t2.work_id','t3.next_status_id'])->from('hll_feedback_apply as t1')
			->leftJoin('hll_wf_case as t2','t2.id = t1.case_id')
			->leftJoin('hll_wf_flow as t3','t3.current_status_id = t2.status_id')
			->where(['t1.valid'=>1,'t2.work_id'=>2,'t3.work_id'=>2,'t2.valid'=>1,'t3.valid'=>1])
			->all();
		if($list){
			foreach($list as &$item){
				$user_id = (new Query())->select(['user_id'])->from('hll_wf_flow')
					->where(['current_status_id'=>$item['next_status_id'],'work_id'=>$item['work_id'],'valid'=>1])->scalar();
				$item['user_id'] = $user_id;
			}
			//获取用户信息
			$user = EcsUsers::getUser($list['user_id'], ['t1.user_id', 't2.openid']);
			if (!$user || $user['openid'] == '') return false;
			//发送模板消息
			$state = WxTmplMsg::FeedbackNotice($user, $list);
			if ($state) {
				return true;
			}
		}
	}

	/**
	 * 生成刮刮卡
	 * @throws \yii\db\Exception
	 */
	public function actionGameScratchCreate(){
		Yii::warning('community_feedback_notification begin' . f_date(time()));
		$date = date("Y-m-d",time());
		$card = (new Query())->select(['point_num','part_num','scid','id'])->from('hll_game_scratch_card_points')
			->where(['game_date'=>$date,'valid'=>1,'send_status'=>1])->one();
		if($card){
			$point = [];
			$card['point_num'] = $card['point_num'] - $card['part_num'];
			for($i = $card['part_num']; $i > 0; $i--){
				if ($i == 1) {
					$money = $card['point_num'];
				}else{
					$min = 1;

					$max = $card['point_num'] / $i * 2;

					$money = round($max * $this->f_randomFloat());
					if ($money < $min) $money = $min;
				}
				$point[$i][] = $money + 1;
				$point[$i][] = $date;
				$point[$i][] = '0000-00-00 00:00:00';
				$point[$i][] = intval($card['scid']);
				$card['point_num'] = $card['point_num'] - $money;
			}
			$connection = Yii::$app->db;
			$num = $connection->createCommand()->batchInsert('hll_game_scratch_card_detail', ['point', 'game_date','taken_time','scid'], $point)->execute();
			if($num > 0){
				$connection->createCommand()->update('hll_game_scratch_card_points',['send_status'=>2],'id='.$card['id'])->execute();
			}
		}
	}

	/**
	 * 检查未支付订单，退回友元
	 */
	public function actionCheckOrder(){
		Yii::warning('check_order_payback begin' . f_date(time()));
		$order_list = (new Query())->select(['t1.order_id'])->from('ecs_order_info as t1')
			->innerJoin('ecs_users as t2','t2.user_id = t1.user_id')
			->where(['t1.order_status'=>0,'t1.shipping_status'=>0,'t1.pay_status'=>0])
			->andWhere(['>','integral',0])->column();

		$point_id = [];
		if($order_list){
			foreach($order_list as $item){
				$point_log = (new Query())->select(['id'])->from('hll_user_points_log')
					->where(['category'=>'goods','order_id'=>$item,'type'=>1,'valid'=>1])
					->column();
				if($point_log){
					$point_id = array_merge($point_id,$point_log);
				}
			}
		}
		$trans = Yii::$app->db->beginTransaction();
		try{
			foreach($point_id as $item){
				$result = HllUserPoints::payPointsBack($item);
				if($result){
					continue;
				}else{
					throw new Exception('error',101);
				}
			}
			$order_list = implode(',',$order_list);
			$sql = 'UPDATE ecs_order_info SET order_status = 2 WHERE order_id IN ('.$order_list.')';
			Yii::$app->db->createCommand($sql)->execute();
			$trans->commit();
		}catch (Exception $e){
			Yii::error('check_order_payback error' . f_date(time()));
			$trans->rollBack();
		}
	}

	private function f_randomFloat($min = 0, $max = 1)
	{
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}

}