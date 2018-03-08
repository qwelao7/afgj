<?php
use common\components\duiba\Duiba;

/**
 *
 * @author: XuYi
 * @date: 2016-10-19
 * @version: $Id$
 */


namespace api\modules\duiba\controllers;


use common\models\hll\HllDuibaOrders;
use common\models\hll\HllUserPoints;
use common\models\hll\HllUserPointsLog;
use yii\rest\Controller;
use yii;
use yii\base\Exception;
/**
 * Class IndexController
 * @package api\modules\duiba\controllers
 * @property Duiba $duiba The user component.
 */
class IndexController extends Controller
{
	public $duiba;

	public function behaviors()
	{
		$behaviors = parent::behaviors();
		$behaviors['contentNegotiator'] = [
			'class' => yii\filters\ContentNegotiator::className(),
			'formats' => [
				'application/json' => yii\web\Response::FORMAT_JSON,
			],
		];
		return $behaviors;
	}

	public function beforeAction($action)
	{
		$this->duiba = Yii::$app->duiba;
		return parent::beforeAction($action);
	}


	public function actionTest()
	{
		return ['code' => 10000, 'msg' => '接口正常'];
	}

	/**
	 * 消费积分接口
	 * 返回格式 成功:
	 * {'status': 'ok','errorMessage': '','bizId': '20140730192133033','credits': '100'}
	 *            失败
	 * {'status': 'fail','errorMessage': '失败原因（显示给用户）','credits': '100'}
	 * @return string
	 */
	public function actionConsume()
	{
		$request = Yii::$app->request->get();
		unset($request['m']);
		$res = $this->duiba->handelConsumeRequest($request);
		if ($res !== false) {
			//保存兑吧订单
			$data = array_merge([
				'ip' => Yii::$app->request->get('ip'),
				'waitAudit' => Yii::$app->request->get('waitAudit'),
				'type' => Yii::$app->request->get('type'),
				'params' => Yii::$app->request->get('params'),
				'uid' => Yii::$app->request->get('uid'),
			], $res);

			$now_points = HllUserPoints::getUserPoints($data['uid'],16396,4);
			$points = $data['credits'];//消费的友元
			if ($now_points < $points) {
				return [
					'status' => 'fail',
					'credits' => $now_points,
					'errorMessage' => '可用积分不足'
				];
			}

			$duiba_order_num = $res['orderNum'];
			$model = HllDuibaOrders::findOne(['duiba_order_num' => $duiba_order_num]);
			if ($model instanceof HllDuibaOrders) {
				return [
					'status' => 'fail',
					'credits' => $now_points,
					'errorMessage' => '重复兑换.' . $this->duiba->getErrorMsg()
				];
			}
            $trans = Yii::$app->db->beginTransaction();
			$model = new HllDuibaOrders();
			$model->duiba_order_num = $duiba_order_num;
			$model->user_id = $data['uid'];
			$model->description = $data['description'];
			$model->credits = $points;
			$model->order_status = 2;
			$model->credits_status = 1;
			$model->save(false);


			$pay_points = $points;
			$data['item_id'] = 0;
			$data['icon'] = 'http://pub.huilaila.net/defaultpic/huilailalogo.jpg';
			$data['remark'] = '友元商城消费积分';
			$data['category'] = 'duiba';
			$data['type'] = HllUserPointsLog::EXPEND_POINT_TYPE;
			$data['scenes'] = HllUserPointsLog::$scenes_type[2];
			$data['change_reason'] = '友元商城收入';
			$data['business_id'] = 0;
			$data['order_id'] = $model->id;
			$data['unique_id'] = $duiba_order_num;
            try{
                HllUserPoints::LanYuanExpendPoints($model->user_id, $pay_points,$data,16396);//扣友元
                $trans->commit();
            }catch (Exception $e){
                $trans->rollBack();
                $mes = $e->getMessage();
                return [
                    'status' => 'fail',
                    'credits' => 0,
                    'errorMessage' => $mes
                ];
            }

			return [
				'status' => 'ok',
				'credits' => HllUserPoints::getUserPoints($data['uid'],16396,4),
				'errorMessage' => $this->duiba->getErrorMsg(),
				'bizId' => 'hll'.time().rand(0, 9) .rand(0, 9) .rand(0, 9) .rand(0, 9) ,
			];
		} else {
			return [
				'status' => 'fail',
				'credits' => 0,
				'errorMessage' => 'sign fail'
			];
		}

	}

	/**
	 *
	 */
	public function actionNotify()
	{

		$request = Yii::$app->request->get();

		unset($request['m']);
		$res = $this->duiba->handelNotifyRequest($request);
		if ($res === false) {
			exit('verify sign failed');
		}

		$res = array_merge($res, [
			'orderNum' => Yii::$app->request->get('orderNum'),
			'bizId' => Yii::$app->request->get('bizId'),

		]);

		$model = HllDuibaOrders::findOne(['duiba_order_num' => $res['orderNum']]);
		if ($model instanceof HllDuibaOrders) {
			if ($model->order_status != 1 || $model->credits_status != 1) {
				exit('fail');
			}
			if ($res['success'] == 'true') {
				$model->order_status = 2;
			} else {//失败返回积分 并且记录失败
				$point_log = HllUserPointsLog::find()->select(['id','point'])
					->where(['unique_id' => $res['orderNum'], 'valid' => 1])->asArray()->all();
				if($point_log){
					$trans = Yii::$app->db->beginTransaction();
					try{
						foreach ($point_log as $item) {
							$point = HllUserPoints::findOne($item['id']);
							$point->point += $item['point'];
							$points_log = new HllUserPointsLog();
							$points_log->user_id = $model->user_id;
							$points_log->point_id = $item['id'];
							$points_log->point = $item['point'];
							$points_log->type = HllUserPointsLog::INCOME_POINT_TYPE;
							$points_log->scenes = HllUserPointsLog::$scenes_type[2];
							$points_log->remark = '对吧返回友元';
							if($point->save() && $points_log->save(false)){
								continue;
							}else{
								throw new Exception('database error',101);
							}
						}
						$trans->commit();
					}catch (Exception $e){
						$trans->rollBack();
					}
				}
				$model->order_status = 3;
				$model->credits_status = 2;
				$model->description .= '错误:'.$res['errorMessage'];
			}
			$model->save();

			exit('ok');
		}

		exit('fail');

	}
}

