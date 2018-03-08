<?php

namespace api\modules\third\controllers;

use api\modules\third\models\UAccount;
use yii\rest\Controller;
use yii;


class UserController extends Controller
{
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

	public function actionIndex()
	{
		//生成用户测试
//		return UAccount::register([
//			'tname' => 'xgg',
//			'contact_name' => 'xas',
//			'contact_phone' => '12312321312',
//		]);
		return ['code' => 10000, 'msg' => '接口正常'];
	}

	public function actionLogin()
	{
		$appid = Yii::$app->request->post('appid');
		$appsecret = Yii::$app->request->post('appsecret');
		$user = UAccount::findOne(['appid' => $appid, 'valid' => 1]);
		if ($user instanceof UAccount) {
			if ($appsecret == $user->appsecret) {
				$token = $user->createToken();
				if ($token) {
					return ['code' => 10000, 'msg' => '登录成功', 'datas' =>
						[
							'time' => time(),
							'token' => $token
						]
					];
				}
				return ['code' => 20000, 'msg' => '登录失败'];
			}
		}
		return ['code' => 20000, 'msg' => '用户不存在,请注册'];
	}

}
