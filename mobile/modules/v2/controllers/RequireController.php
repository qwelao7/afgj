<?php
namespace mobile\modules\v2\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\filters\Cors;
use yii\filters\HttpCache;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use mobile\modules\v2\models\User;
use common\models\need\DecorateRequirement;
use common\components\WxTmplMsg;

/**
 * 投票相关操作接口
 * 无需登录
 * Class WechatController
 * @package api\modules\v1\controllers
 */
class RequireController extends \yii\rest\Controller  {
    public $second_cache = 60;
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => f_params('origins'),//定义允许来源的数组
                    'Access-Control-Request-Method' => ['GET','POST','PUT','DELETE', 'HEAD', 'OPTIONS'],//允许动作的数组
                ],
            ],
            [
                'class' => HttpCache::className(),
                'only' => ['view', 'result'],
                'lastModified' => function () { // 设置 Last-Modified 头
                    return time() + $this->second_cache;
                },
                'cacheControlHeader' => 'Cache-Control: public, max-age='.$this->second_cache,
            ],
        ]);
    }

    public function actionIndex() {
        //home装饰用户id
        $user = 0;
        $response = new ApiResponse();

        $data = Yii::$app->request->post('data');
        if(!$data) {
            $response->data = new ApiData(101, '参数缺失');
            return $response;
        }

        $model = new DecorateRequirement();

        if($model->load($data, '') && $model->validate()) {
            if($model->save()) {
                $id = $model->id;
                $response->data = new ApiData();
                WxTmplMsg::decorateOrderNotification($id, $user);
            }else {
                $response->data = new ApiData(100, '创建失败');
            }
        }else {
            $response->data = new ApiData(100, '创建失败');
        }

        return $response;

    }
}
