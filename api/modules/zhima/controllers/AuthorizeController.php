<?php
/**
 *
 * @author: XuYi
 * @date: 2016-10-19
 * @version: $Id$
 */

namespace api\modules\zhima\controllers;


use common\components\zhima\ZhiMa;
use common\models\hll\HllLibraryCard;
use common\models\hll\HllZhima;
use yii\rest\Controller;
use yii;


class AuthorizeController extends Controller
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

	public function actionTest()
	{
		return ['code' => 10000, 'msg' => '接口正常'];
	}

	public function actionIndex()
	{
		try{
			$sign = Yii::$app->request->get('sign', '');
			$param = Yii::$app->request->get('params', '');
			$zhima = Yii::$app->zhima;
			if (!($zhima instanceof ZhiMa)) {
				echo 'sdk initialize error';
				return $this->redirect(Yii::$app->zhima->errorUrl.'?error=1');
			}
			$res = $zhima->decryptAndVerifySign($sign, $param);

			file_put_contents(__DIR__ . '/../../../runtime/logs/zhima.log', 'Time:' . date('Y-m-d H:i:s') . ' File:' . __FILE__ . "\n" . "Data:", FILE_APPEND);
			file_put_contents(__DIR__ . '/../../../runtime/logs/zhima.log', $res . "\n\n\n", FILE_APPEND);

			if (!$res) {
				echo 'data  error';
				return $this->redirect(Yii::$app->zhima->errorUrl.'?error=2');
			}
			parse_str($res, $data);
			$error_code = $data['error_code'];
			if ($error_code != 'SUCCESS') {
				echo 'authorize failed';
				return $this->redirect(Yii::$app->zhima->errorUrl.'?error=3');
			}
			$open_id = $data['open_id'];
			$state = $data['state'];
			$index = strpos($state, ':');
			$user_id = substr($state, 0, $index);
			$to_url = substr($state, (int)$index + 1);

			$redirectUrl = static::generateUrl($to_url);

			if(!$open_id || !$user_id){
				echo 'callback data error';
				return $this->redirect(Yii::$app->zhima->errorUrl.'?error=4');
			}
			$model = HllZhima::find()->where(['open_id'=>$open_id,'user_id'=>$user_id,'valid'=>'1'])->one();
			if (!$model){
				//保存基础信息
				$model = new HllZhima();
				$model->open_id = $open_id;
				$model->user_id = $user_id;
				$model->save(false);
			}

			if ($model->zm_score){
				echo 'authorized user';
				return $this->redirect(Yii::$app->zhima->errorUrl.'?error=5');
			}
			$score = $zhima->getUserScore($open_id);
			if (!$score){
				echo 'get user score fail';
				return $this->redirect(Yii::$app->zhima->errorUrl.'?error=6');
			}

			//保存信用分
			$model->zm_score = $score;
			$model->save(false);
			//借书卡+3
			$library_card = HllLibraryCard::findOne(['user_id'=>$user_id,'valid'=>1]);
			if(!$library_card){
				$library_card = new HllLibraryCard();
				$library_card->user_id = $user_id;
				$library_card->borrow_limit = 4;
			}else{
				$library_card->borrow_limit +=3;
			}
			if($library_card->save()){
				//跳转
				$this->redirect($redirectUrl);
			}else{
				throw new \Exception('library_card save error',101);
			}
		}catch (\Exception $e){
			echo $e->getMessage();
		}
	}

	private function generateUrl ($to_url) {
		if (strstr($to_url, 'b')) {
			$prefix = substr($to_url, 0, 1);
			$nextfix = substr($to_url, 2);

			$redirectUrl = Yii::$app->params['afgjDomain'].Yii::$app->params['to_url_'.$prefix].'?event_id='.$nextfix;
		} else {
			$prefix = substr($to_url, 0, 1);

			$redirectUrl = Yii::$app->params['afgjDomain'].Yii::$app->params['to_url_'.$prefix];
		}

		return $redirectUrl;
	}

}
