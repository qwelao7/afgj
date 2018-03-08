<?php

namespace api\modules\zhima;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'api\modules\zhima\controllers';

    public function init()
    {
        parent::init();
		\Yii::$app->user->enableSession = false;

        // custom initialization code goes here
    }
}
