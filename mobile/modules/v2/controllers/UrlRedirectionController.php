<?php
namespace mobile\modules\v2\controllers;

use common\models\ecs\EcsSessions;
use Yii;
use mobile\components\ApiController;
use common\models\hll\HllUserPoints;
/**
 * Created by PhpStorm.
 * User: qkk
 * Date: 2017/5/3
 * Time: 17:20
 */
class UrlRedirectionController extends ApiController{

	public function init()
	{
		$ecs_sessions = new EcsSessions();
		if($ecs_sessions->loadSession()){
			$this->userSession = $ecs_sessions;
			static::$sessionInfo = $GLOBALS['_SESSION'];
		} else {
			$url =  'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			header("Location:".ECTOUCH_LOGIN_URL.'&redirect='.$url);
			exit;
		}
	}

    public function actionDbRedirect()
    {
        $user_id = Yii::$app->user->id;
        $point = floor(HllUserPoints::getUserPoints($user_id,16396,4));
        $url = Yii::$app->request->get('dbredirect');
        if ($url){
            header("Location:".Yii::$app->duiba->getRedirectUrl($user_id,$point,$url));
            exit();

        }
        echo 'not access';
        exit();
    }

    public function actionIndexUrl(){

        $user_id = Yii::$app->user->id;
        $point = floor(HllUserPoints::getUserPoints($user_id,16396,4));

        header("Location:".Yii::$app->duiba->getAutoLoginUrl($user_id,$point));
        exit();

    }
}