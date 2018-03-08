<?php

namespace mobile\controllers;

use Yii;
/**
 * Site controller
 */
class ErrorController extends \yii\web\Controller {

    public function actionIndex() {
        $this->layout ='blank';
        return $this->render('index');
    }
    public function actionWeixin() {
        $content = f_post('content','');
        Yii::error($content, 'client');
    }
}
