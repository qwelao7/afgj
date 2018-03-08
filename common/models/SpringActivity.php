<?php
namespace common\models;

use common\models\ar\user\AccountFriend;
use common\models\ecs\EcsUsers;
use common\models\ecs\EcsWechatUser;
use common\models\hll\HllSpringAward;
use common\models\hll\HllSpringTask;
use common\models\hll\UserAddress;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii;

/**
 *
 * @author: xuyi
 * @date: 2017/1/14
 */
class SpringActivity extends Model
{
	const FIRST_AWARD = 288;
	const SECOND_AWARD = 88;
	const THIRD_AWARD = 8;

	private $_msg = '';

	/**
	 * @return string
	 */
	public function getMsg()
	{
		return $this->_msg;
	}

	/**
	 * @param string $msg
	 */
	public function setMsg($msg)
	{
		$this->_msg = $msg;
	}

	/**
	 * 完成任务
	 * @param $user_id
	 * @param $task_id
	 * @return bool
	 * 调用方法
	 * $activity = new SpringActivity();
	 * $res = $activity->finishTask($user_id,$task_id);
	 * if (!$res) $error = $activity->getMsg();
	 */
	public function finishTask($user_id, $task_id)
	{
		$task = HllSpringTask::findOne(['valid' => '1', 'task_id' => $task_id, 'user_id' => $user_id]);
		if ($task) {
			$this->setMsg('该任务了已经完成!');
			return false;
		}
		$transaction = HllSpringTask::getDb()->beginTransaction();
		try {
			switch ($task_id) {
				case HllSpringTask::TASK_HOME:
					$point = HllSpringTask::POINT_HOME;
					$task_name = '房产验证奖励';
					break;
				case HllSpringTask::TASK_NEIGHBOR:
					$point = HllSpringTask::POINT_NEIGHBOR;
					$task_name = '关注邻居奖励';
					break;
				case HllSpringTask::TASK_GREET:
					$point = HllSpringTask::POINT_GREET;
					$task_name = '社区拜年奖励';
					break;
				default:
					$point = 0;
					$task_name = '';
					break;
			}

			//记录
			$task = new HllSpringTask();
			$task->task_id = $task_id;
			$task->user_id = $user_id;
			$task->save();

			//发送积分
			$this->sendPoint($user_id, $point);

			//发送通知
			$this->sendTemplateMessage($user_id,$task_name,$point);
			$transaction->commit();
			return true;
		} catch (\Exception $e) {
			$transaction->rollBack();
			$this->setMsg($e->getMessage());
			return false;
		}

	}

	/**
	 * 抽奖活动发送红包
	 * @return bool
	 * 调用方法
	 * $activity = new SpringActivity();
	 * $res = $activity->raffle();
	 * if (!$res) $error = $activity->getMsg();
	 */
	public function raffle()
	{
		$transaction = HllSpringTask::getDb()->beginTransaction();

		try {
			//取得当前关注微信的所有用户
			$wx_user = EcsWechatUser::find()->select(['ect_uid', 'openid', 'nickname'])->where(['wechat_id' => 1])->indexBy('ect_uid')->asArray()->all();
			$wx_user_list = array_keys($wx_user);
			//select count(id) as c,user_id,task_id from hll_spring_task group by user_id,task_id having c>=2
			$data = HllSpringTask::getDb()->createCommand('select count(id) as c,sum(task_id) as s,user_id from hll_spring_task group by user_id having c=3 and s=6 ')->queryAll();
			$eligible_user = ArrayHelper::getColumn($data, 'user_id');
			$award_list = [];
			for ($i = 0; ;) {
				$c = count($eligible_user);
				//
				if ($c <= 0) {//没有满足条件的用户 结束活动
					break;
				}
				if ($i > 55) { //奖池抽干
					break;
				}
				$luck_key = array_rand($eligible_user, 1);

				if (!isset($eligible_user[$luck_key])) {//无人 终结活动
					break;
				}
				$luck_user = $eligible_user[$luck_key];
				if (!in_array($luck_user, $wx_user_list)) { //用户不在关注列表中
					unset($eligible_user[$luck_key]);
					continue;
				}
				unset($eligible_user[$luck_key]);
				if ($i == 0) {//一等奖
					$award_list[HllSpringAward::FIRST_AWARD][] = $luck_user;
				}
				if ($i >= 1 && $i <= 5) {//二等奖
					$award_list[HllSpringAward::SECOND_AWARD][] = $luck_user;
				}
				if ($i >= 6 && $i <= 55) {//三等奖
					$award_list[HllSpringAward::THIRD_AWARD][] = $luck_user;
				}
				$i++;
			}

			//保存中奖记录
			$award = new HllSpringAward();
			$award->saveAward($award_list);

			//发放红包
			foreach ($award_list as $a => $users) {
				foreach ($users as $u){
					if (isset($wx_user[$u])){
						$user = $wx_user[$u];
						$this->sendLuckMoney($user['openid'], $award);
					}
				}
			}
			$transaction->commit();
			return true;
		} catch (\Exception $e) {
			$transaction->rollBack();
			return false;
		}

	}

	/**
	 * 批量刷用户关注邻居任务
	 * @return bool
	 * 调用方法
	 * $activity = new SpringActivity();
	 * $res = $activity->checkUserNeighbor();
	 * if (!$res) $error = $activity->getMsg();
	 */
	public function checkUserNeighbor()
	{
		$wx_user = EcsWechatUser::find()->select(['ect_uid', 'openid', 'nickname'])->where(['wechat_id' => 1, 'subscribe' => '0'])->indexBy('ect_uid')->asArray()->all();
		$wx_user_list = array_keys($wx_user);
		$user = [];
		foreach ($wx_user_list as $user_id) {
			$friend = AccountFriend::find()->where(['account_id' => $user_id, 'status' => '4'])->one();
			if ($friend) {
				$user[] = ['user_id' => $user_id, 'openid' => $wx_user[$user_id]['openid']];
			}
		}
		$ecs_user = EcsUsers::find()->select(['mobile_phone', 'user_id'])->where(['user_id' => $wx_user_list])->indexBy('user_id')->all();
		$data = [];
		$time = date('Y-m-d H:i:s', time());

		foreach ($user as $u) {
			$phone = $ecs_user[$u['user_id']]['mobile_phone'];
			if ($phone) {
				$this->sendSms($phone, 'springTask', HllSpringTask::TASK_NEIGHBOR);
			}
			$data[] = [
				'user_id' => $u['user_id'],
				'task_id' => HllSpringTask::TASK_NEIGHBOR,
				'valid' => '1',
				'creater' => '1',
				'created_at' => $time,
				'updater' => '1',
				'updated_at' => $time,
			];
		}
		if ($data){
			//增加积分
			$point_sql = 'update ecs_users set pay_points=pay_points+' . HllSpringTask::POINT_NEIGHBOR . ' where user_id in (' . implode(ArrayHelper::getColumn($data, 'user_id'), ',') . ')';
			EcsUsers::getDb()->createCommand($point_sql)->execute();
			//记录任务完成
			HllSpringTask::getDb()->createCommand()->batchInsert('hll_spring_task', array_keys($data[1]), $data)->execute();
		}

	}

	/**
	 * 批量刷用户绑定房产任务
	 * @return bool
	 * 调用方法
	 * $activity = new SpringActivity();
	 * $res = $activity->checkUserHouse();
	 * if (!$res) $error = $activity->getMsg();
	 */
	public function checkUserHouse()
	{
		$wx_user = EcsWechatUser::find()->select(['ect_uid', 'openid', 'nickname'])->where(['wechat_id' => 1, 'subscribe' => '0'])->indexBy('ect_uid')->asArray()->all();
		$wx_user_list = array_keys($wx_user);
		$user = [];
		foreach ($wx_user_list as $user_id) {
			$address = UserAddress::find()->where(['account_id' => $user_id, 'owner_auth' => '1', 'valid' => '1'])->one();
			if ($address) {
				$user[] = ['user_id' => $user_id, 'openid' => $wx_user[$user_id]['openid']];
			}
		}

		$ecs_user = EcsUsers::find()->select(['mobile_phone', 'user_id'])->where(['user_id' => $wx_user_list])->indexBy('user_id')->all();
		$data = [];
		$time = date('Y-m-d H:i:s', time());

		foreach ($user as $u) {
			$phone = $ecs_user[$u['user_id']]['mobile_phone'];
			if ($phone) {
				$this->sendSms($phone, 'springTask', HllSpringTask::TASK_HOME);
			}
			$data[] = [
				'user_id' => $u['user_id'],
				'task_id' => HllSpringTask::TASK_HOME,
				'valid' => '1',
				'creater' => '1',
				'created_at' => $time,
				'updater' => '1',
				'updated_at' => $time,
			];
		}
		if ($data){
			//增加积分
			$point_sql = 'update ecs_users set pay_points=pay_points+' . HllSpringTask::POINT_HOME . ' where user_id in (' . implode(ArrayHelper::getColumn($data, 'user_id'), ',') . ')';
			EcsUsers::getDb()->createCommand($point_sql)->execute();
			//记录任务完成
			HllSpringTask::getDb()->createCommand()->batchInsert('hll_spring_task', array_keys($data[1]), $data)->execute();
		}

	}

	/**
	 * 获取用户任务完成状态
	 * @param $user_id
	 * @return array
	 */
	public static function getUserTaskStatus($user_id)
	{
		return HllSpringTask::checkUserTaskStatus($user_id);
	}

	/**
	 * 获取所有用户任务完成状态
	 * @return array
	 */
	public static function getUsersTask()
	{
		return HllSpringTask::checkUsersTask();
	}

	public static function getUsersAwardStatus()
	{
		return HllSpringAward::getUsersAward();
	}
    public static function getAwardResult() {
        return HllSpringAward::getAwardResult();
    }

	private function sendPoint($user_id, $point)
	{
		if ($point) {
			$point_sql = 'update ecs_users set pay_points=pay_points+' . (int)$point . ' where user_id = ' . (int)$user_id . ' ';
			EcsUsers::getDb()->createCommand($point_sql)->execute();
			return true;
		}
		return false;

	}

	private function sendSms($phone, $template, $type)
	{
		$taskname = '';
		switch ($type) {
			case 1:
				$taskname = '我家在这里';
				break;
			case 2:
				$taskname = '邻居你好啊';
				break;
			case 3:
				$taskname = '给您拜年啦';
				break;
		}
		Yii::$app->sms->send($phone, $template, ['taskname' => $taskname]);
	}

	private function sendTemplateMessage($user_id,$task_name,$point)
	{
		$ecs = EcsUsers::findOne(['user_id' => $user_id]);
		$wechat = EcsWechatUser::findOne(['ect_uid' => $user_id]);
		$template_id = Yii::$app->params['wxTmplMsg']['spring_task_notice']['template_id'];
		$notice = Yii::$app->wechat->getNotice();
		$result = $notice->send([
			'touser' => $wechat['openid'],
			'template_id' => $template_id,
			'url' => ECTOUCH_USER_HOME_URL,
			'topcolor' => '',
			'data' => [
				'first' => '欢迎参加“回来啦社区”新春特别活动-唯有家人最珍贵！',
				'account' => $wechat['nickname'],
				'time' => date('Y-m-d H:i:s', time()),
				'type' => ''.$task_name,
				'creditChange' => '到帐',
				'number' => $point,
				'creditName' => '账户积分',
				'amount' => $ecs['pay_points'] - $point,
				'remark' => '',
			],
		]);


	}

	//发红包
	private function sendLuckMoney($open_id, $award)
	{
		$money = 0;
		if ($award == HllSpringAward::FIRST_AWARD) {
			$money = SpringActivity::FIRST_AWARD;
		}
		if ($award == HllSpringAward::SECOND_AWARD) {
			$money = SpringActivity::SECOND_AWARD;
		}
		if ($award == HllSpringAward::THIRD_AWARD) {
			$money = SpringActivity::THIRD_AWARD;
		}
		Yii::$app->wechat->sendSpringRedAward($open_id, $money);
	}

	/** 初一至初五触发 **/
	public function triggerSendTemplate($user_id, $task_id)
	{
        $time = time();
		$end = strtotime('2017-02-01 08:00:00');
		if (($time < $end)) {
			$this->finishTask($user_id, $task_id);
		}
	}


	public function batchSendLuckMoney(){
		$wx_user = EcsWechatUser::find()->select(['ect_uid', 'openid', 'nickname'])->where(['wechat_id' => 1])->indexBy('ect_uid')->asArray()->all();
		$list = HllSpringAward::find()->where(['valid'=>'1'])->asArray()->all();
		foreach ($list as $use){
			$award = $use['award_grade'];
			$open_id = $wx_user[$use['user_id']]['openid'];
			$this->sendLuckMoney($open_id,$award);
		}
	}
}
