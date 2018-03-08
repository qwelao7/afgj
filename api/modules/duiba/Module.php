<?php

namespace api\modules\duiba;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'api\modules\duiba\controllers';

    public function init()
    {
        parent::init();
		\Yii::$app->user->enableSession = false;

        // custom initialization code goes here
    }
}
