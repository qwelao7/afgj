<?php

namespace mobile\components\filter;
use Yii;
use yii\filters\auth\AuthMethod;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;

class CookieAuth extends AuthMethod {

    /**
     * @inheritdoc
     */
    public function authenticate($user, $request, $response)
    {
        $class = Yii::$app->user->identityClass;
        if($GLOBALS['_SESSION']['user_id']) {
            $identity = $class::findIdentity($GLOBALS['_SESSION']['user_id']);
            if ($identity && Yii::$app->user->login($identity)) {
                return $identity;
            }
        }
        return null;
    }
    /**
     * @inheritdoc
     */
    public function handleFailure($response) {
        $response = new ApiResponse(405,'该账户尚未登录');
        $response->data = new ApiData();
        $response->data->info = ECTOUCH_LOGIN_URL;
        echo json_encode($response);
        exit;
    }
}