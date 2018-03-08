<?php

namespace api\modules\third\controllers;

use api\modules\third\models\UpdateOrderModel;
use yii\rest\Controller;
use yii;


class OrderController extends Controller
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();
		$behaviors['authenticator'] = [
			'class' => yii\filters\auth\HttpBearerAuth::className(),
		];
		$behaviors['contentNegotiator'] = [
			'class' => yii\filters\ContentNegotiator::className(),
			'formats' => [
				'application/json' => yii\web\Response::FORMAT_JSON,
			],
		];
		return $behaviors;
	}

	public function actionIndex()
	{
		return ['code' => 10000, 'msg' => '接口正常'];
	}

	public function actionUpdate()
	{
		$third_order_id = Yii::$app->request->post('third_order_id');
		$order_id = Yii::$app->request->post('order_id');
		$third_party_sn = Yii::$app->request->post('third_party_sn');
		$state = Yii::$app->request->post('state');
		$remark = Yii::$app->request->post('remark');
		$service_date = Yii::$app->request->post('service_date');
		$begin_time = Yii::$app->request->post('begin_time');
		$end_time = Yii::$app->request->post('end_time');
		$detail = Yii::$app->request->post('detail');
		$model = new UpdateOrderModel();
		$model->load(['UpdateOrderModel'=>[
			'order_id' => $third_order_id,
			'work_sn' => $third_party_sn,
			'state' => $state,
			'remark' => $remark,
			'service_date' => $service_date,
			'begin_time' => $begin_time,
			'end_time' => $end_time,
			'detail' => $detail,
			'xqg_order_id' => $order_id,
		]]);

		if ($model->validate()) {
			$res = $model->updateOrder();
			if ($res) {
				return ['code' => 10000, 'msg' => '推送成功', 'data' => [
					'reason' => 'success',
					'time' => '' . time(),
				]];
			}
		}else{
			$msg = '参数异常: ';
			$e = $model->getErrors();
			foreach($e as $v){
				$msg .= ' '.$v[0].'| ';
			}
			$model->setError($msg);
		}

		return ['code' => 20000, 'msg' => '参数异常', 'data' => [
			'reason' => $model->getError(),
			'time' => '' . time(),
		]];


	}
}
