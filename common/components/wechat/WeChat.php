<?php
/**
 *
 * @author: XuYi
 * @date: 2016-10-27
 * @version: $Id$
 */

namespace common\components\wechat;


use common\models\ecs\EcsOrderInfo;
use common\models\ecs\EcsUsers;
use common\models\hll\HllEquipmentServiceCenter;
use common\models\hll\HllEquipmentServiceCenterFeedback;
use Yii;

use common\models\ecs\EcsWechatUser;
use common\models\hll\HllRedEnvelope;
use common\models\hll\HllRedEnvelopeDetail;
use EasyWeChat\Foundation\Application;
use EasyWeChat\Support\Collection;

/**
 * Class WeChat
 *
 ** @property \EasyWeChat\Foundation\Application $app The response component. This property is read-only.
 */
class WeChat extends \yii\base\Object
{

	public $app_id;
	public $secret;
	public $token;
	public $aes_key;

	public $payment;
	//'payment' => [
	//'merchant_id'        => 'your-mch-id',
	//'key'                => 'key-for-signature',
	//'cert_path'          => 'path/to/your/cert.pem',
	//'key_path'           => 'path/to/your/key',
	//],

	public $app;
	public $luck_money;

	public function init()
	{
		$options = [
			'payment' => $this->payment,
			'app_id' => $this->app_id,
			'secret' => $this->secret,
			'token' => $this->token,
			'aes_key' => $this->aes_key
		];

		if (IS_PROD_MACHINE || IS_TEST_MACHINE) { //生产机器走redis缓存
			$cacheDriver = new RedisCache();
			$redis = new \Redis();
			$redis->connect(REDIS_IP, 6379);
			$cacheDriver->setRedis($redis);
			$options['cache'] = $cacheDriver;
		}

		$this->app = new Application($options);

	}

	/**
	 * 发送微信红包
	 * 调用方法 Yii::$app->wechat->sendRedToUser(1)
	 * @param $red_id hll_red_envelope_detail 中的id
	 * @return array ['res'=>string,'msg'=>string]
	 */
	public function sendRedToUser($red_id)
	{
		$red_detail = HllRedEnvelopeDetail::findOne(['id' => $red_id, 'valid' => '1', 'send_status' => '1']);//防止重复发送
		$red = HllRedEnvelope::findOne(['id' => $red_detail->reid, 'valid' => '1']);
		$user = EcsWechatUser::findOne(['ect_uid' => $red_detail->user_id]);

		if (!$user || !$red_detail || !$red) {
			return [
				'res' => 'ERROR',
				'msg' => '数据异常',
			];
		}
		$luckyMoney = $this->app->lucky_money;
		$luckyMoneyData = [
			'mch_billno' => $this->payment['merchant_id'] . date('Ymd', time()) . '1234' . rand(100000, 999999),
			'send_name' => '回来啦社区',
			'wishing' => $red->wishing,
			're_openid' => $user->openid,
			'total_num' => 1,  //固定为1，可不传
			'total_amount' => $red_detail->remoney * 100,  //单位为分，不小于300
			'act_name' => $red->title,
			'remark' => '回来啦红包',
			// ...
		];
		$result = $luckyMoney->sendNormal($luckyMoneyData);

		if (!$result instanceof Collection) {
			return [
				'res' => 'ERROR',
				'msg' => '调用异常',
			];
		}
		$red_detail->taken_time = f_date(time());
		$red_detail->send_status = 2;
		$red_detail->save(false);

		return [
			'res' => $result->get('result_code'),
			'msg' => $result->get('return_msg'),
		];
		//return [
		//	'res' => 'SUCCESS',
		//	'msg' => 'OK',
		//];
	}

	public function sendSpringRedAward($open_id, $money)
	{
		$luckyMoney = $this->app->lucky_money;
		$luckyMoneyData = [
			'mch_billno' => $this->payment['merchant_id'] . date('Ymd', time()) . '1234' . rand(100000, 999999),
			'send_name' => '回来啦社区',
			'wishing' => '唯有家人最珍贵',
			're_openid' => $open_id,
			'total_num' => 1,  //固定为1，可不传
			'total_amount' => $money * 100,  //单位为分，不小于300
			'act_name' => '开心赢财神',
			'remark' => '回来啦红包',
			// ...
		];
		$result = $luckyMoney->sendNormal($luckyMoneyData);
	}

	public function sendMsg()
	{
		$notice = $this->app->notice;
		$userId = 'oRHHXvtk7GE5h9G6H4x9Mpd6ozdg';
		$templateId = '0LNwEfRNQaixxzQrrMnBdBVNvykuvoPIHo7ZVa-uxuY';
		$url = 'http://overtrue.me';
		$color = '#FF0000';
		$data = array(
			"first" => "恭喜你购买成功！",
			"reason" => "巧克力",
			"refund" => "39.8元",
			"remark" => "欢迎再次购买！",
		);
		$result = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver($userId)->send();
		return $result;

	}

	/**
	 * 发送友元信息
	 * @param $user EcsWechatUser
	 * @param $point
	 * @return mixed
	 */
	public function sendYouYuanMsg($user, $point, $all_point,$remark)
	{
		$notice = $this->app->notice;
		$userId = $user->openid;
		$templateId = Yii::$app->params['wxTmplMsg']['thanks_account_notice']['template_id'];;
		$url = 'http://www.huilaila.net/points-index.html';
		$data = [
			"first" => "您有新友元到账，详情如下！",
			"account" => $user->nickname,
			"time" => date('Y-m-d H:i:s'),
			"type" => "系统发放",
			"creditChange" => "到帐",
			"number" => ($point ) . '友元',
			"creditName" => '友元',
			"amount" => ($all_point) . '友元',
			'remark' => $remark?'发放留言:'.$remark:'',
		];
		$result = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver($userId)->send();
		return $result;

	}

	/**
	 * @param $user EcsWechatUser
	 * $params $id 活动id
	 * @param $status
	 * @param $reason
	 * @return bool
	 */
	public function sendEventMsg($id, $user, $status, $reason)
	{
		$notice = $this->app->notice;
		$userId = $user->openid;
		$templateId = Yii::$app->params['wxTmplMsg']['task_handle_notice']['template_id'];;
		$url = 'http://www.huilaila.net/event-detail.html?id=' . $id . '&dir=started&type=1';
		$data = [
			"first" => $status == 1 ? "您创建的活动已经通过审核。" : "您创建的活动审核未通过，请修改。",
			"keyword1" => '活动审核',
			"keyword2" => '提醒',
			"remark" => $status == 1 ? "" : "失败原因:" . $reason,
		];
		$result = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver($userId)->send();
		return $result;

	}

	/**
	 * @param $user EcsWechatUser
	 * @param $status
	 * @param $reason
	 * @return bool
	 */
	public function sendBookError($isbn, $userId)
	{
		$notice = $this->app->notice;
		$templateId = Yii::$app->params['wxTmplMsg']['task_handle_notice']['template_id'];;
		$url = '';
		$data = [
			"first" => '书号为' . $isbn . '的图书信息采集失败',
			"keyword1" => '图书上架',
			"keyword2" => '提醒',
			"remark" => '请拍摄图书的正反面,发送给回来啦服务号,工作人员收集信息后会上架该图书',
		];
		$result = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver($userId)->send();
		return $result;

	}

	/**
	 * @param $order EcsOrderInfo
	 * @param $money
	 * @return mixed
	 */
	public function sendOrderPriceMsg($order, $money, $reason)
	{
		$user_id = $order->user_id;
		$user = EcsWechatUser::findOne(['ect_uid' => $user_id]);
		if (!$user || !$user->openid) {
			return false;
		}
		$notice = $this->app->notice;
		$templateId = Yii::$app->params['wxTmplMsg']['order_price_change']['template_id'];
		$url = Yii::$app->params['afgjDomain'] . '/order-detail.html?classify=1&id=' . $order->order_id;
		$data = [
			"first" => '您好，您的订单有更新',
			"keyword1" => $order->order_sn,
			"keyword2" => '待付款',
			"remark" => "订单总价已经调整.\n\r调整前:￥" . money_format('%.2n', $money) . "\r\r调整后:￥" . money_format('%.2n', $order->order_amount) .
				($reason ? "\n\r调整原因:" . $reason : ''),
		];
		$result = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver($user->openid)->send();
		return $result;

	}

	/**
	 * @param $feedback HllEquipmentServiceCenterFeedback
	 * @return bool
	 */
	public function sendFeedbackMsg($feedback)
	{
		$user_id = $feedback->creater;
		$user = EcsWechatUser::findOne(['ect_uid' => $user_id]);
		if (!$user || !$user->openid) {
			return false;
		}
		$center = HllEquipmentServiceCenter::findOne($feedback->esc_id);

		$notice = $this->app->notice;
		$templateId = Yii::$app->params['wxTmplMsg']['task_handle_notice']['template_id'];
		$url = $feedback->process_status == 3? Yii::$app->params['afgjDomain'] .'/points-index.html?'.time():'';
		$data = [
			"first" => '您有一条任务处理通知。',
			"keyword1" => "设施维修点纠错处理结果",
			"keyword2" => '提醒',
			"remark" => $feedback->process_status == 3 ? '您上报的维修点' . $center->company_name . '信息异常，经核实属实，已经处理，感谢您的反馈，奖励您1友元，谢谢' : '您上报的维修点' . $center->company_name . '信息异常，经核实为误报，请知悉。',
		];
		$result = $notice->uses($templateId)->withUrl($url)->andData($data)->andReceiver($user->openid)->send();
		return $result;

	}

	/**
	 * @return \EasyWeChat\QRCode\QRCode
	 */
	public function getQrcode()
	{
		return $this->app->qrcode;
	}

	/**
	 * @return \EasyWeChat\Notice\Notice
	 */
	public function getNotice()
	{
		return $this->app->notice;
	}
}