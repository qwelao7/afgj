<?php
namespace api\controllers;

use Yii;
use yii\web\Controller;

/**
 * Site controller
 */
class SiteController extends Controller
{
	public function actionError(){
		$exception = Yii::$app->errorHandler->exception;
		if ($exception !== null) {
			echo $exception;exit();
		}
	}
}
