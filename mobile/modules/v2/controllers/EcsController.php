<?php

namespace mobile\modules\v2\controllers;


use Yii;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\helpers\ArrayHelper;
use yii\filters\HttpCache;

use yii\db\Query;

//ecs控制器
class EcsController extends ApiController {
    public $second_cache = 60;
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => HttpCache::className(),
                'only' => ['article'],
                'lastModified' => function () { // 设置 Last-Modified 头
                    return time() + $this->second_cache;
                },
                'cacheControlHeader' => 'Cache-Control: public, max-age=' . $this->second_cache,
            ],
        ]);
    }


    /**
     * ecs文章详情
     */
//    public function actionArticle() {
//        $response = new ApiResponse();
//
//        $data = Yii::$app->request->get();
//        if(!$data || empty($data['id'])) {
//            $response->data = new ApiData(101, '参数缺失');
//            return $response;
//        }
//
//        $val = (new Query())->select(['title', 'content','author'])->from('ecs_article')->where(['article_id'=>$data['id']])->one();
//        if(!$val) {
//            $response->data = new ApiData(102, '数据不存在');
//        }
//        $response->data = new ApiData();
//        $response->data->info = $val;
//
//        return $response;
//    }
}