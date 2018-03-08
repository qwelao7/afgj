<?php

namespace mobile\components;

use common\models\ecs\EcsSessions;
use Yii;
use yii\helpers\ArrayHelper;
use yii\filters\Cors;
use mobile\components\filter\CookieAuth;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\data\ActiveDataProvider;
/**
 * Class BaseController
 * @package api\components
 */
class ApiController extends  \yii\rest\Controller {

    var $userSession;
    public static $sessionInfo;

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(),[
            'authenticator' => [
                'class' => CookieAuth::className(),
            ],
            [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => f_params('origins'),//定义允许来源的数组
                    'Access-Control-Request-Method' => ['GET','POST','PUT','DELETE', 'HEAD', 'OPTIONS'],//允许动作的数组
                ]
            ],
        ]);
    }

    public function init()
    {
        parent::init();
        $ecs_sessions = new EcsSessions();
        if($ecs_sessions->loadSession()){
            $this->userSession = $ecs_sessions;
            static::$sessionInfo = $GLOBALS['_SESSION'];
        } else {
            $response = new ApiResponse(405,'该账户尚未登录');
            $response->data = new ApiData();
            $response->data->info = ECTOUCH_LOGIN_URL;
            echo json_encode($response);
            exit;
        }
    }
    
    //对数据进行分页处理
    public function getDataPage($sql,$page,$pageSize = 20){
        $dataProvider = new ActiveDataProvider([
            'query' => $sql,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        if ($dataProvider && $dataProvider->count > 0) {
            if ($page > $dataProvider->getPagination()->getPageCount()) {
                $info['list'] = [];
                $info['pagination']['total'] = $dataProvider->getTotalCount();
                $info['pagination']['pageSize'] = $dataProvider->getPagination()->pageSize;
                $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
            }

            $data = $dataProvider->getModels();
            $info['list'] = $data;
            $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
            $info['pagination']['pageSize'] = $dataProvider->getPagination()->pageSize;
            $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
        }else {
            $info['list'] = [];
            $info['pagination']['total'] = 0;
            $info['pagination']['pageSize'] = 0;
            $info['pagination']['pageCount'] = 1;
        }
        return $info;
    }

}