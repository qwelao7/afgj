<?php

namespace mobile\components;

use Yii;
use common\models\ecs\EcsSessions;
use yii\helpers\ArrayHelper;
use mobile\components\filter\CookieAuth;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
/**
 * Description of Controller
 *
 * @author Administrator
 */
class Controller extends \yii\web\Controller {
    
    public $enableCsrfValidation = false;
    
    public $layout = '//main';
    var $userSession;
    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(),[
            'authenticator' => [
                'class' => CookieAuth::className(),
            ],

        ]);
    }
    public function init()
    {
        parent::init();
        $ecs_sessions = new EcsSessions();
        if($ecs_sessions->loadSession()){
            $this->userSession = $ecs_sessions;
        } else {
            $response = new ApiResponse(405,'该账户尚未登录');
            $response->data = new ApiData();
            $response->data->info = ECTOUCH_LOGIN_URL;
            echo json_encode($response);
            exit;
        }
    }
}
