<?php
namespace mobile\modules\v2\controllers;

use common\components\ThirdXQG;
use common\components\wechat\WeChat;
use common\components\WxTmplMsg;
use common\models\ecs\EcsOrderInfo;
use common\models\ecs\EcsWechatUser;
use common\models\hll\Community;
use common\models\ecs\EcsUsers;
use common\models\hll\HllCustInvite;
use common\models\hll\HllEquipmentServiceCenterFeedback;
use common\models\hll\HllEvents;
use common\models\hll\HllEventsApply;
use common\models\hll\HllHouseOwner;
use common\models\hll\HllUserPoints;
use common\models\hll\HllUserPointsLog;
use common\models\SpiderModel;
use common\models\SpringActivity;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\filters\Cors;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use mobile\modules\v2\models\User;
use common\models\ar\user\AccountAddress;
use yii\db\Query;
use yii\caching\DbDependency;
use yii\data\ActiveDataProvider;
use yii\web\Response;
use yii\log\Logger;

/**
 * 公共接口控制器,如获取服务器时间,获取引导页等。。。。
 * 无需登录
 * Class SiteController
 * @package api\modules\v1\controllers
 */
class SiteController extends \yii\rest\Controller
{

	public function actions()
	{
		return [
			//网页验证码
			'captcha' => [
				'class' => 'common\components\NumberCaptchaAction',
				'minLength' => 4,
				'maxLength' => 4,
				'backColor' => 0xf3f3f3
			],
			//手机验证码
			'captchaphone' => [
				'class' => 'common\components\NumberCaptchaAction',
				'height' => 1,
				'width' => 1,
				'minLength' => 6,
				'maxLength' => 6,
				'backColor' => 0xf3f3f3
			],
		];
	}

	public function behaviors()
	{
		return ArrayHelper::merge(parent::behaviors(), [
			[
				'class' => Cors::className(),
				'cors' => [
					'Origin' => f_params('origins'),//定义允许来源的数组
					'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],//允许动作的数组
				],
			],
		]);
	}

	/**
	 * @SWG\Post(path="/site/signupmobile",
	 *   tags={"site"},
	 *   summary="检测用户",
	 *   description="根据手机号检测用户是否存在",
	 *   consumes={"application/x-www-form-urlencoded","application/json"},
	 *   produces={"application/xml", "application/json"},
	 *   @SWG\Parameter(
	 *        in = "formData",
	 *        name = "mobile",
	 *        description = "手机号",
	 *        required = true,
	 *        type = "string",
	 *     ),
	 *   @SWG\Response(
	 *         response="200",
	 *         description="成功操作",
	 *         @SWG\Schema(ref="#/definitions/ApiResponse")
	 *     ),
	 *   @SWG\Response(response=401, description="该手机号已存在"),
	 *   @SWG\Response(response=409, description="参数有误"),
	 * )
	 */
	public function actionSignupmobile()
	{
		$response = new ApiResponse();

		$data = Yii::$app->request->post();
		if (!$data || empty($data['mobile']) || !f_checkMobile($data['mobile'])) {
			$response->data = new ApiData(9, '参数mobile有误');
			return $response;
		}
		$count = EcsUsers::find()->where(['mobile_phone' => $data['mobile']])->count();
		if ($count) {
			$response->data = new ApiData(1, '该手机号已存在');
		} else {
			$response->data = new ApiData(0);
		}
		return $response;
	}

	/**
	 *
	 * @SWG\Post(path="/site/smscaptch",
	 *     tags={"site"},
	 *     summary="短信验证码",
	 *     description="发送手机短信验证码",
	 *   consumes={"application/x-www-form-urlencoded","application/json"},
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *        in = "formData",
	 *        name = "mobile",
	 *        description = "手机号",
	 *        required = true,
	 *        type = "string",
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="成功操作",
	 *         @SWG\Schema(ref="#/definitions/ApiResponse")
	 *     ),
	 *   @SWG\Response(response=401, description="发送失败"),
	 *   @SWG\Response(response=402, description="输入验证码错误，请重新获取"),
	 *   @SWG\Response(response=409, description="参数mobile或code有误"),
	 * )
	 *
	 */
	public function actionSmscaptch()
	{
		$response = new ApiResponse();
		$data = Yii::$app->request->post();

		if (!$data || empty($data['mobile']) || !f_checkMobile($data['mobile'])) {
			$response->data = new ApiData(9, '参数mobile或code有误');
			return $response;
		}

		$smsCaptch = $this->createAction('captchaphone')->getVerifyCode(true);//生成短信验证码
		if (IS_DEV_MACHINE) {
			$response->data = new ApiData(0, ' 发送成功');
			$response->data->info = $smsCaptch;
		} else {
			if (Yii::$app->sms->send($data['mobile'], 'bindMobile', ['code' => $smsCaptch])) {
				Yii::warning("sms captch send success:{$smsCaptch} {$data['mobile']}", 'client');
				$response->data = new ApiData(0, ' 发送成功');
			} else {
				Yii::error("sms captch send fail:{$smsCaptch} {$data['mobile']}", 'client');
				$response->data = new ApiData(1, ' 发送失败');
			}
		}
		return $response;
	}
    
	public function actionImagecaptch()
	{
		$response = new ApiResponse();

		$this->createAction('captcha')->getVerifyCode(true);//服务端重新生成图像验证码
		$imgCaptch = $this->createAction('captcha')->getVerifyCode();//获取图形验证码

		$response->data = new ApiData(0, ' 发送成功');
		$response->data->info = $imgCaptch;

		return $response;
	}

	/**
	 * 发送语音验证码
	 */
	public function actionVoiceCode()
	{
		$response = new ApiResponse();
		$data = Yii::$app->request->post();
		$product = '回来啦社区';

		if (!$data || empty($data['mobile']) || !f_checkMobile($data['mobile'])) {
			$response->data = new ApiData(9, '参数mobile或code有误');
			return $response;
		}

		$smsCaptch = $this->createAction('captchaphone')->getVerifyCode(true);//生成验证码
		if (IS_DEV_MACHINE) {
			$response->data = new ApiData(0, ' 发送成功');
			$response->data->info = $smsCaptch;
		} else {
			if (Yii::$app->sms->sendYuYin($data['mobile'], 'bindMobile', ['code' => $smsCaptch, 'product' => $product])) {
				Yii::warning("voice captch send success:{$smsCaptch} {$data['mobile']}", 'client');
				$response->data = new ApiData(0, ' 发送成功');
			} else {
				Yii::error("voice captch send fail:{$smsCaptch} {$data['mobile']}", 'client');
				$response->data = new ApiData(1, ' 发送失败');
			}
		}

		return $response;
	}

	/**
	 *
	 * @SWG\Post(path="/site/bindmobile",
	 *     tags={"site"},
	 *     summary="绑定手机号",
	 *     description="用户第一次登录时,绑定手机号",
	 *     consumes={"application/x-www-form-urlencoded","application/json"},
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *        in = "formData",
	 *        name = "mobile",
	 *        description = "手机号",
	 *        required = true,
	 *        type = "string",
	 *     ),
	 *     @SWG\Parameter(
	 *        in = "formData",
	 *        name = "code",
	 *        description = "验证码",
	 *        required = true,
	 *        type = "string",
	 *     ),
	 *     @SWG\Parameter(
	 *        in = "formData",
	 *        name = "openid",
	 *        description ="用户微信code",
	 *        required = false,
	 *        type = "string",
	 *     ),
	 *     @SWG\Parameter(
	 *        in = "formData",
	 *        name = " invitation_code",
	 *        description ="邀请码",
	 *        required = false,
	 *        type = "string",
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="成功操作",
	 *         @SWG\Schema(ref="#/definitions/ApiResponse")
	 *     ),
	 *   @SWG\Response(response=401, description="手机号绑定失败,请先关注回来啦社区公众号"),
	 *   @SWG\Response(response=402, description="输入验证码错误，请重新输入"),
	 *   @SWG\Response(response=403, description="登录失败，请重试"),
	 *   @SWG\Response(response=409, description="参数mobile或code有误"),
	 * )
	 *
	 */
	public function actionBindMobile()
	{
		$response = new ApiResponse();
		$data = Yii::$app->request->post();

		$id = Yii::$app->user->id;
		if (!$data || empty($data['mobile']) || empty($data['code']) || !f_checkMobile($data['mobile'])) {
			$response->data = new ApiData(9, '参数mobile或code有误');
			return $response;
		}
		$smsCaptch = $this->createAction('captchaphone')->getVerifyCode();//获取短信验证码
		if (strcmp($smsCaptch, $data['code']) != 0) {
			$response->data = new ApiData(2, '输入验证码错误，请重新输入');
			return $response;
		}

		$trans = Yii::$app->db->beginTransaction();
		try {
			$userId = EcsUsers::registerOrBindMobile($data, $id);
			if ($userId) {
				$trans->commit();
				$response->data = new ApiData(0, '手机号绑定成功');
				HllHouseOwner::createInitialAddress($data['mobile']);
			} else {
				$response->data = new ApiData(1, '手机号绑定失败');
			}
		} catch (Exception $ex) {
			$trans->rollBack();
            Yii::error("bind user phone({$data['mobile']}) exception: ". $ex->getMessage(), 'error');
			$response->data = new ApiData(3, '绑定异常，请重试');
		}
		return $response;
	}

	/**
	 *
	 * @SWG\Post(path="/site/login",
	 *     tags={"site"},
	 *     summary="用户登录",
	 *     description="通过手机号密码登录",
	 *     consumes={"application/x-www-form-urlencoded","application/json"},
	 *     produces={"application/json"},
	 *     @SWG\Parameter(
	 *        in = "formData",
	 *        name = "mobile",
	 *        description = "手机号",
	 *        required = true,
	 *        type = "string",
	 *     ),
	 *     @SWG\Parameter(
	 *        in = "formData",
	 *        name = "password",
	 *        description ="密码",
	 *        required = true,
	 *        type = "string",
	 *     ),
	 *     @SWG\Response(
	 *         response="200",
	 *         description="成功操作",
	 *         @SWG\Schema(ref="#/definitions/ApiResponse")
	 *     ),
	 *   @SWG\Response(response=409, description="参数mobile或password有误"),
	 *   @SWG\Response(response=401, description="登录失败"),
	 *   @SWG\Response(response=402, description="无法获取用户信息"),
	 * )
	 *
	 */
	public function actionLogin()
	{

		$response = new ApiResponse();
		$data = Yii::$app->request->post();

		if (!$data || empty($data['mobile']) || empty($data['password']) || !f_checkMobile($data['mobile'])) {
			$response->data = new ApiData(9, '参数mobile或password有误');
			return $response;
		}

		$user = User::findOne(['primary_mobile' => $data['mobile'], 'password' => md5($data['password'])]);
		if ($user) {
			if (Yii::$app->user->login($user)) {
				$response->data = new ApiData(0, '登录成功');
				$response->data->info = Yii::$app->user->identity->weixin_code;
			} else {
				$response->data = new ApiData(1, '登录失败');
			}
		} else {
			$response->data = new ApiData(2, '无法获取用户信息');
		}
		return $response;
	}


	public function actionStartcaptcha()
	{
		$response = new ApiResponse();
        $verifier = Yii::$app->ne_captch_verifier;
        $validate = $_POST['NECaptchaValidate']; // 获得验证码二次校验数据
        $result = $verifier->verify($validate,"");
		$response->data = new ApiData(0);
		$response->data->info = $result;
		return $response;
	}

	/**
	 * 获取区域信息
	 * @param int $regionType 地区分类
	 * @param int $parentId 上级区域ID
	 * @return ApiResponse
	 * @author zend.wang
	 * @date  2016-09-01 13:00
	 */
	public function actionRegion($regionType = 1, $parentId = 1)
	{
		$response = new ApiResponse();
		$key = "region_{$regionType}_{$parentId}";
		$cache = Yii::$app->cache;
		$data = $cache->get($key);
		if ($data === false) {
			$data = (new Query())->select(['region_id', 'region_name', 'parent_id', 'region_type'])
				->from("ecs_region")
				->where(["parent_id" => $parentId, "region_type" => $regionType])
				->all();
			if (!$data) {
				$response->data = new ApiData(1, '参数异常,无相关数据');
				return $response;
			}
			$dependency = new DbDependency(['sql' => "SELECT MAX(region_id) FROM ecs_region"]);
			Yii::$app->cache->set($key, $data, 3600, $dependency);
		}
		$response->data = new ApiData();
		$response->data->info = $data;
		return $response;
	}

	/**
	 * 根据$cityId获取该城市下所有的小区
	 * @param $cityId
	 * @return ApiResponse
	 * @author kaikai.qin
	 * @date  2016-10-08 12:09
	 */
	public function actionCommuntity($cityId, $keywords = '')
	{
		$response = new ApiResponse();
		//获取城市名
		$city = (new Query())->select(['region_name'])->from('ecs_region')->where(['region_id' => $cityId])->one();
		//获取指定城市的小区信息 返回键值对
		$data = (new Query())->select(['t1.id', 't1.name', 't2.region_name as district'])
			->from('hll_community as t1')
			->leftJoin('ecs_region as t2', 't1.district = t2.region_id')
			->where(['city' => $cityId, 'valid' => 1])
			->orderBy([ 'displayorder' => SORT_DESC,'firstletter' => SORT_ASC,]);
		//根据关键字搜索
		if ($keywords) {
			$keywords = trim($keywords);
			$data->andWhere(['like', 't1.name', $keywords]);
		}
		//每页显示30数据
		$dataProvider = new ActiveDataProvider([
			'query' => $data,
			'pagination' => [
				'pageSize' => 30,
			]
		]);
		//对数据进行分页处理
		if ($dataProvider && $dataProvider->count > 0) {
			$response->data = new ApiData();
			$data = $dataProvider->getModels();
			foreach ($data as &$item) {
				$item['city'] = $city['region_name'];
			}
			$info['list'] = $data;
			$info['pagination']['total'] = $dataProvider->getTotalCount();//总数
			$info['pagination']['pageSize'] = $dataProvider->getPagination()->pageSize;
			$info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
		} else {
			$response->data = new ApiData('110', '无相关数据');
			$info['list'] = [];
			$info['pagination']['total'] = 0;
			$info['pagination']['pageSize'] = 0;
			$info['pagination']['pageCount'] = 1;
		}
		$response->data->info = $info;
		return $response;
	}

	/**
	 * 获取所有城市信息
	 * @param int $regionType 地区分类
	 * @param int $parentId 上级区域ID
	 * @return ApiResponse
	 * @author zend.wang
	 * @date  2016-09-01 13:00
	 */
	public function actionCity()
	{
		$response = new ApiResponse();
		$key = "region_city__map";
		$cache = Yii::$app->cache;
		$data = $cache->get($key);
		if ($data === false) {

			$list = (new Query())->select(['region_id', 'region_name', 'first_letter'])
				->from("ecs_region")
				->where(["region_type" => 2])
				->all();
			if (!$list) {
				$response->data = new ApiData(1, '参数异常,无相关数据');
				return $response;
			}
			$data = ['热门' => [['region_id' => 220, 'region_name' => '南京'],
                ['region_id' => 232, 'region_name' => '镇江'],
                ['region_id' => 229, 'region_name' => '徐州'],
                ['region_id' => 222, 'region_name' => '无锡'],
                ['region_id' => 221, 'region_name' => '苏州']
            ],];
			foreach ($list as $item) {
				$first_letter = $item['first_letter'];
				unset($item['first_letter']);
				$data[$first_letter][] = $item;
			}

			Yii::$app->cache->set($key, $data, 7200);
		}

		$currentCity = EcsUsers::getGeoLocationCity();

		$response->data = new ApiData();
		$response->data->info = ['current' => $currentCity, 'city' => $data];
		return $response;
	}

	public function actionSearchCity()
	{
		$response = new ApiResponse();
		$data = Yii::$app->request->get('city');
		$city = (new Query())->select(['region_id', 'region_name'])
			->from("ecs_region")
			->where(["region_type" => 2])
			->andWhere(['like', 'region_name', $data])
			->all();
		if (!$city) {
			$response->data = new ApiData(110, '无相关数据');
			return $response;
		}
		$response->data = new ApiData();
		$response->data->info['citys'] = $city;
		return $response;
	}

	/**
	 * 房产认证消息
	 * @author zend.wang
	 * @date  2016-06-08 13:00
	 */
	public function actionHouseAuthNotice($authId, $address_id)
	{
		if (!$authId) {
			echo 'params exception';
			exit;
		}
		$result = WxTmplMsg::houseAuthNotice($authId, $address_id);
		echo $result;
		exit;
	}

	/**
	 * 后台验证房产，发送模板消息
	 * @param $userId
	 * @param $communityId
	 * @param $addressDesc
	 * @return ApiResponse
	 */
	public function actionSendPointByBindHouse($userId, $communityId, $addressDesc){
		$response = new ApiResponse();
		$send_log = (new Query())->from('hll_community_point_log')
			->where(['desc'=>$addressDesc,'type_id'=>1,'valid'=>1])->count();
		$community_name = (new Query())->select(['name'])->from('hll_community')
			->where(['id'=>$communityId,'valid'=>1])->scalar();
		if($send_log){
			$response->data = new ApiData();
		}else{
			$flag = HllHouseOwner::sendPointByCommunity($userId,$communityId,$addressDesc);
			if($flag > 1){
				$user = EcsUsers::getUser($userId, ['t1.user_id', 't2.openid','t2.nickname']);
				$left_point = HllUserPoints::getUserPoints($userId);
				$type = $communityId == 19668 ? 3 : 2;
				WxTmplMsg::PointChangeNotice($user,$flag,$left_point,$community_name,$type);
			}
			$response->data = new ApiData();
		}

		return $response;
	}

	public function actionPushOrderToThird()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;
		$order_id = Yii::$app->request->get('order_id', 41);
		if (!$order_id) {
			return ['code' => 20000, 'msg' => 'missing of order_id'];
		}
		$model = new ThirdXQG;
		$res = $model->pullOrderToThird($order_id);
		return ['code'=>10000,'msg'=>'','res'=>$res,'error'=>$model->getError()];
	}

	public function actionTestLuck(){

		$res = Yii::$app->wechat->sendRedToUser(1);
		var_dump($res);exit();
	}

	public function actionTestMsg(){
		$res = Yii::$app->wechat->sendMsg();
		var_dump($res);exit();
	}

	public function actionFinishTask($account_id,$task_id)
	{
		if (!$account_id || !$task_id) {
			echo 'params exception';
			exit;
		}
		$activity = new SpringActivity();
		$result = $activity->finishTask($account_id,$task_id);
		echo $result;
		exit;
	}

	public function actionUserInviteNotice($invite_id)
	{
		if (!$invite_id) {
			echo 'params exception';
			exit;
		}
		return HllCustInvite::sendSms($invite_id);
	}

	public function actionUserPointNotice($user_id,$point,$log_id)
	{

		if (!$user_id || !$point ||! $log_id) {
			echo 'params exception';
			exit;
		}
		$log = HllUserPointsLog::findOne(['unique_id'=>$log_id]);
		if (!$log){
			echo 'user did\'t exist';
			exit;
		}
		$user = EcsWechatUser::findOne(['ect_uid'=>$log->user_id]);
		if (!$user){
			echo 'user did\'t exist';
			exit;
		}
		$all_point = HllUserPoints::getUserPoints($log->user_id);
		return Yii::$app->wechat->sendYouYuanMsg($user,$log->point,$all_point,$log->remark);
	}

	//微信支付成功后发生积分变化信息
	public function actionPointChange($apply_id,$user_id,$unique_id,$title){
		$response = new ApiResponse();
		$apply = HllEventsApply::findOne(['id'=>$apply_id]);
		$events = HllEvents::findOne(['id'=>$apply->events_id]);
		if($events->apply_check == 1){
			$creater = EcsUsers::getUser($events->creater,['t1.user_id', 't2.openid']);
			WxTmplMsg::EventCheckNotice($creater, $apply_id, $events->title,$unique_id);
			$response->data = new ApiData(0,'等待管理员审核！');
			$response->data->info['id'] = $apply_id;
		}else{
			$user = EcsUsers::getUser($user_id, ['t1.user_id', 't2.openid']);
			WxTmplMsg::EventApplyNotice($user, $events->id,$events->title);
			if($unique_id != '0'){
				$point = (new Query())->select(["SUM(point) as point",'user_id'])->from('hll_user_points_log')
					->where(['unique_id' => $unique_id, 'user_id'=>$user_id,'valid' => 1])->one();
				$left_point = HllUserPoints::getUserPoints($user_id);
				$user = EcsUsers::getUser($point['user_id'], ['t1.user_id', 't2.openid','t2.nickname']);
				$title = $events->title.'活动消费';
				WxTmplMsg::PointChangeNotice($user,$point['point'],$left_point,$title);
				$creater = EcsUsers::getUser($events->creater, ['t1.user_id', 't2.openid','t2.nickname']);
				$title = $events->title.'活动收入';
				$left_point = HllUserPoints::getUserPoints($events->creater);
				WxTmplMsg::PointChangeNotice($creater,$point['point'],$left_point,$title);
			}
			$response->data = new ApiData(0,'报名成功！');
			$response->data->info['id'] = $apply_id;
		}
		return $response;
	}

	public function actionGetCurrentTime(){
		$response = new ApiResponse();
		$response->data = new ApiData();
		$response->data->info = time();
		return $response;
	}

	public function actionUserEventNotice($id, $status, $reason = '')
	{
		if (!$id || !$status) {
			echo 'params exception';
			exit;
		}
		$event = HllEvents::findOne(['id' => $id, 'valid' => '1']);
		if (!$event) {
			echo 'data error';
			exit;
		}
		$user = EcsWechatUser::findOne(['ect_uid' => $event->creater]);
		if (!$user) {
			echo 'user did\'t exist';
			exit;
		}
		return Yii::$app->wechat->sendEventMsg($id, $user, $status, $reason);

	}

	public function actionBookSpiderNotComplete(){
		SpiderModel::addBookByIsbn();
	}

	public function actionEditOrderPrice()
	{

		$order_id = Yii::$app->request->post('order_id',0) + 0;
		$money = Yii::$app->request->post('money',0) + 0;
		$reason = Yii::$app->request->post('reason','');
		if (!$order_id){

			echo 'error param';exit();
		}
		$order = EcsOrderInfo::findOne($order_id);
		if (!$order){

			echo 'error order';exit();
		}
		return Yii::$app->wechat->sendOrderPriceMsg($order,$money,$reason);
	}

	public function actionFeedbackMessage()
	{

		$feedback_id = Yii::$app->request->post('id',0) + 0;


		if (!$feedback_id){

			echo 'error param';exit();
		}
		$feedback = HllEquipmentServiceCenterFeedback::findOne($feedback_id);
		if (!$feedback){

			echo 'error order';exit();
		}
		if($feedback->process_status == 3){
			$year = strtotime(date("Y-m-d",time() + 3600 * 24 * 31 * 12));
			$time = date('Y-m-d H:i:s', strtotime(date('Y-m', strtotime(date('Y-m-d', $year + 3600 * 24 * 31))))-1);
			$trans = Yii::$app->db->beginTransaction();
			try{
				$point = HllUserPoints::findOne(['user_id'=>$feedback->creater,'expire_time'=>$time,'valid'=>1]);
				if(!$point){
					$point = new HllUserPoints();
					$point->user_id = $feedback->creater;
					$point->point = 100;
					$point->expire_time = $time;
				}else{
					$point->point += 100;
				}
				if($point->save()){
					$point_log = new HllUserPointsLog();
					$point_log->user_id = $feedback->creater;
					$point_log->point = 100;
					$point_log->type = 3;
					$point_log->scenes = 'admin';
					$point_log->remark = '反馈信息有效获得友元';
					if($point_log->save()){
						$trans->commit();
					}else{
						throw new Exception($point_log->getErrors(),102);
					}
				}else{
					throw new Exception($point->getErrors(),101);
				}
			}catch (Exception $e){
				$trans->rollBack();
			}
		}
		return Yii::$app->wechat->sendFeedbackMsg($feedback);
	}
	
	public function actionVerifylsm() {
		$response = new ApiResponse();

		$url = 'https://captcha.luosimao.com/api/site_verify';

		$data = [
			'api_key' => Yii::$app->params['catpchaKey'],
			'response' => Yii::$app->request->post('captcha')
		];

		$ch = curl_init();
		curl_setopt($ch, 10002, $url);
		curl_setopt($ch, 10036, 'POST');
		curl_setopt($ch, 64, FALSE);
		curl_setopt($ch, 81, FALSE);
		curl_setopt($ch, 10023, ['Accept-Charset: utf-8']);
		curl_setopt($ch, 52, 1);
		curl_setopt($ch, 58, 1);
		curl_setopt($ch, 19913, true);
		curl_setopt($ch, 10015, $data);
		$tmpInfo = curl_exec($ch);
		curl_close($ch);

		$ret = json_decode($tmpInfo, true);
		if (json_last_error() == 0) {
			$response->data = new ApiData();
			$response->data->info['result'] = $ret;
		} else {
			$response->data = new ApiData(200, '验证失败');
		}

		return $response;
	}
}
