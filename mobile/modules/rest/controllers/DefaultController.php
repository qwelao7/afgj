<?php

namespace mobile\modules\rest\controllers;

use mobile\components\ActiveController;

class DefaultController extends ActiveController {
    
    public function actionIndex($a=1) {
        return $this->renderRest('成功'.$a);
    }
}
