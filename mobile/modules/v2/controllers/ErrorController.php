<?php

namespace mobile\modules\v2\controllers;

use Yii;
use yii\web\Controller;

/**
 * Site controller
 */
class ErrorController extends \yii\rest\Controller
{

    public function actionIndex() {
        $this->layout ='blank';
        return $this->render('index');
    }

    public function actionWeixin() {
        $content = f_post('content','');
        Yii::error($content, 'client');
    }
}
