<?php

namespace api\modules\third;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'api\modules\third\controllers';

    public function init()
    {
        parent::init();
		\Yii::$app->user->enableSession = false;

        // custom initialization code goes here
    }
}
