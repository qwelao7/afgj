<?php

namespace mobile\modules\v2\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\caching\DbDependency;
use mobile\components\ApiController;
use common\models\ar\fang\FangLoupan;
use common\models\ar\fang\FangHouseType;
use common\models\ar\message\Message;
use yii\filters\Cors;
use yii\filters\HttpCache;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;

/**
 * 楼盘接口控制器
 * @package api\modules\v1\controllers
 */
class BuildingsController extends \yii\rest\Controller
{
    public $second_cache = 60;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => 'yii\filters\PageCache',
                'only' => ['index'],
                'duration' => 5,
                'dependency' => [
                    'class' => 'yii\caching\DbDependency',
                    'sql' => 'SELECT MAX(updated_at) FROM fang_loupan',
                ],
                'variations' => ['page' => f_get('page'), 'type' => f_get('type')]
            ],
            [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => f_params('origins'),//定义允许来源的数组
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],//允许动作的数组
                ]
            ],
            [
                'class' => HttpCache::className(),
                'only' => ['view'],
                'lastModified' => function () { // 设置 Last-Modified 头
                    return time() + $this->second_cache;
                },
                'cacheControlHeader' => 'Cache-Control: public, max-age=' . $this->second_cache,
            ],
        ]);
    }

    public function actionIndex($type = 'simple')
    {
        $response = new ApiResponse();
        $fields = ['id', 'name'];
        $exFields = [];
        if ($type == 'all') {
            $exFields = ['avg_price', 'address', 'tag', 'thumbnail', 'bannerpic', 'loupan_intro_brief'];
        }

        $query = FangLoupan::find()->select($fields)
            ->addSelect($exFields)
            ->orderBy(['sort' => SORT_ASC])
            ->where(['<', 'status', '4']);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        if ($dataProvider && $dataProvider->count > 0) {
            $response->data = new ApiData();

            $list = $dataProvider->getModels();
            foreach ($list as &$item) {
                $banner = json_decode($item['bannerpic']);
                $item['bannerpic'] = current($banner);
            }

            $response->data->info['list'] = $list;
            foreach ($list as &$item) {

                if ($item['tag']) {
                    $tags = explode(',', $item['tag']);
                    $tagNames = [];
                    foreach ($tags as $tag) {
                        $tagNames[] = FangLoupan::$tagText[$tag];
                    }
                    $item['tag'] = $tagNames;
                } else {
                    $item['tag'] = [];
                }
            }

            $pagination['total'] = $dataProvider->getTotalCount();//总数
            $pagination['pageCount'] = $dataProvider->getPagination()->getPageCount();
            $response->data->info['pagination'] = $pagination;
        } else {
            $response->data = new ApiData(1, '无相关数据');
        }
        return $response;
    }

    public function actionView($id, $fields = null)
    {
        $response = new ApiResponse();

        if (!$id || !intval($id)) {
            $response->data = new ApiData(108, '参数异常');
            return $response;
        }
        $key = "buildings_detail_{$id}";
        if (!$fields) {
            $fields = ['id', 'bannerpic', 'name', 'avg_price', 'address', 'delivery_date', 'decorate_level', 'property_type', 'hot_line', 'wx_qr_code', 'loupan_intro'];
        } else {
            $fields = explode(',', $fields);
        }

        $cache = Yii::$app->cache;
        $data = $cache->get($key);
        if ($data === false) {
            $data = FangLoupan::find()->select($fields)->where(['id' => $id])->asArray()->one();
            if (!$data) {
                $response->data = new ApiData(1, '无相关数据');
                return $response;
            }
            if (isset($data['decorate_level'])) $data['decorate_level'] = FangLoupan::$decorateLevel[$data['decorate_level']]['name'];
            if (isset($data['property_type'])) $data['property_type'] = FangLoupan::getPropertyTypeName($data['property_type']);
            $data['houseType'] = FangHouseType::find()
                ->select(['name', 'pic', 'fangxin', 'area', 'lowest_total_price'])
                ->where(['loupan_id' => $id])->orderBy(['id' => SORT_DESC])
                ->asArray()->all();
            $dependency = new DbDependency(['sql' => "SELECT MAX(updated_at) FROM fang_loupan where id={$id}"]);
            Yii::$app->cache->set($key, $data, 5, $dependency);
        }
        $response->data = new ApiData();
        $response->data->info = $data;
        return $response;
    }

    public function actionBanner($id)
    {
        $response = new ApiResponse();

        if (!$id || !intval($id)) {
            $response->data = new ApiData(108, '参数异常');
            return $response;
        }
        $key = "buildings_banner_{$id}";
        $cache = Yii::$app->cache;
        $data = $cache->get($key);
        if ($data === false) {
            $fields = ['pics'];
            $data = FangLoupan::find()->select($fields)->where(['id' => $id])->asArray()->one();
            if (!$data) {
                $response->data = new ApiData(1, '无相关数据');
                return $response;
            }
            $data['pics'] = \yii\helpers\Json::decode($data['pics']);
            $dependency = new DbDependency(['sql' => "SELECT MAX(updated_at) FROM fang_loupan where id={$id}"]);
            Yii::$app->cache->set($key, $data, 5, $dependency);
        }
        $response->data = new ApiData();
        $response->data->info = $data;
        return $response;
    }

    public function actionTags($id, $fields = null)
    {
        $response = new ApiResponse();

        if (!$id || !intval($id)) {
            $response->data = new ApiData(108, '参数异常');
            return $response;
        }
        if (!$fields) {
            $fields = ['id', 'bannerpic', 'name', 'avg_price', 'address', 'delivery_date', 'decorate_level', 'property_type', 'hot_line', 'wx_qr_code', 'loupan_intro'];
        } else {
            $fields = explode(',', $fields);
        }
        $data = FangLoupan::find()->select($fields)->where(['id' => $id])->asArray()->one();
        if (!$data) {
            $response->data = new ApiData(1, '无相关数据');
            return $response;
        }
        $response->data = new ApiData();
        $response->data->info = $data;
        return $response;
    }
}

