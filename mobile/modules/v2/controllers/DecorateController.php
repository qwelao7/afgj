<?php
namespace mobile\modules\v2\controllers;


use common\models\hll\CustomerHouse;
use common\models\hll\DecorateMaintain;
use common\models\hll\DecorateMaterial;
use common\models\hll\DecorateProject;
use common\models\hll\DecorateUser;
use common\models\hll\HllWfCase;
use common\models\hll\HllWfLog;
use common\models\hll\UserAddress;
use Yii;
use yii\base\Exception;
use Yii\db\Query;
use mobile\components\ApiController;
use yii\helpers\ArrayHelper;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\data\ActiveDataProvider;
use yii\filters\HttpCache;
use common\models\hll\UserAddressExt;

use common\models\ecs\EcsUsers;

/**
 * 装修管理接口
 *
 * Class DecorateController
 * @package mobile\modules\v2\controllers
 */
class DecorateController extends ApiController
{
    public $second_cache = 2;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => HttpCache::className(),
                'only' => ['index', 'send'],
                'lastModified' => function () { // 设置 Last-Modified 头
                    return time() + $this->second_cache;
                },
                'cacheControlHeader' => 'Cache-Control: public, max-age=' . $this->second_cache,
            ],
        ]);
    }

    /**
     * 我的装修列表
     * @return ApiResponse
     * @author zend.wang
     * @date  2016-09-05 13:00
     */
    public function actionIndex()
    {
        $response = new ApiResponse();

        $userId = Yii::$app->user->id;
        $val = (new Query())->select(['t1.decorate_id'])->from("decorate_user as t1")->leftJoin("decorate_project as t2", "t2.id = t1.decorate_id")
            ->where(['t1.user_id' => $userId, 't1.valid' => 1, 't2.valid' => 1, 't2.is_prototyperoom' => 0])->all();
        $val = array_column($val, 'decorate_id');
        $query = DecorateProject::getDecorateProjectQueryById($val);

        $exc = (new Query())->select(['t1.id', 't1.title', 't1.thumbnailpic', 't1.is_prototyperoom', 't1.budget', 't2.address_desc', "(t2.id) as address_id"])->distinct(true)
            ->from("decorate_project as t1")
            ->leftJoin("hll_user_address as t2", 't2.id = t1.address_id')
            ->where(['t1.is_prototyperoom' => 1, 't1.valid' => 1, 't2.valid' => 1])
            ->orderBy('t1.id DESC')->all();

        $query = array_merge($query, $exc);

        if ($query) {
            $response->data = new ApiData();
            $response->data->info['list'] = $query;
        } else {
            $response->data = new ApiData(101, '无相关数据');
        }

        return $response;
    }

    /**
     * 创建装修管理,及装修管理页面
     * @return ApiResponse
     * @author zend.wang
     * @date  2016-09-05 13:00
     */
    public function actionCreate()
    {
        $response = new ApiResponse();

        $userId = Yii::$app->user->id;
        $webSite = 'http://pub.huilaila.net/';

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post();
            //create project
            $model = new DecorateProject();
            if ($data['thumbnailpic'] != '') {
                $arr = explode($webSite, $data['thumbnailpic']);
                $data['thumbnailpic'] = $arr[1];
            }
            if ($model->load($data, '')) {
                if ($model->validate()) {
                    if ($model->save()) {
                        //decorate_user
                        $decorateUser = new DecorateUser();
                        $decorateUser->decorate_id = $model->id;
                        $decorateUser->user_id = $userId;
                        $decorateUser->user_role = 1;
                        $decorateUser->save();
                        $response->data = new ApiData(0, '创建成功');
                    } else {
                        $response->data = new ApiData(101, '保存失败');
                    }
                } else {
                    $response->data = new ApiData(102, implode(',', $model->getFirstErrors()));
                }
            } else {
                $response->data = new ApiData(103, '数据装载失败');
            }

            return $response;
        }

        //auth user house
        $house = UserAddress::getHouse($userId);
        if (!$house) {
            $response->data = new ApiData(108, '暂无房产信息');
            return $response;
        } else {
            $response->data = new ApiData();
            $response->data->info['house'] = $house;
            //decorate company
            $response->data->info['brand'] = DecorateProject::getDecorateCompany([7, 8]);
        }
        return $response;
    }

    /**
     * 装修档案
     * @param $id decorate id
     * @author zend.wang
     * @date  2016-09-06 13:00
     */
    public function actionArchives($id)
    {
        $response = new ApiResponse();

        $pics = DecorateProject::find()->select('pics')->where(['id' => $id, 'valid' => 1])->scalar();
        if (!$pics) {
            $response->data = new ApiData(101, '参数异常');
        }
        $response->data = new ApiData(0);
        $response->data->info['contact'] = DecorateUser::getContactsByDecorateId($id);
        $response->data->info['pics'] = $pics;
        return $response;
    }

    /**
     * 装修日志
     * @param $id decorate_peroject id
     * @author zend.wang
     * @date  2016-09-06 13:00
     */
    public function actionLogs($id)
    {
        $response = new ApiResponse();

        $query = DecorateProject::getDecorateLogsQueryByAddressId($id);
        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);
        //对数据进行分页处理
        if ($dataProvider && $dataProvider->count > 0) {
            $list = $dataProvider->getModels();
            foreach ($list as &$item) {
                if ($item['account_id'] != 0) {
                    $item['account'] = EcsUsers::getUser($item['account_id'], ['t1.user_name', 't2.nickname', 't2.headimgurl']);
                } else if ($item['admin_id'] != 0) {
                    $item['account'] = EcsUsers::getAdmin($item['admin_id'], ['t1.user_name', 't1.headimgurl']);
                    $item['account']['nickname'] = '';
                }
                if ($item['attachment_content'] != '') {
                    $item['attachment_content'] = explode(",", $item['attachment_content']);
                }
            }
            $info['list'] = $list;
            $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
            $info['pagination']['pageSize'] = $dataProvider->getPagination()->pageSize;
            $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
            $response->data = new ApiData();
            $response->data->info = $info;
        } else {
            $response->data = new ApiData(102, '无相关数据');
        }
        return $response;
    }

    /**
     * 装修材料列表
     * @param $id decorate id
     * @author zend.wang
     * @date  2016-09-06 13:00
     */
    public function actionMaterial($id)
    {
        $response = new ApiResponse();

        $project = DecorateProject::findOne($id);
        if (!$project) {
            $response->data = new ApiData(101, '参数异常');
        }
        $list = DecorateMaterial::getListByDecorateId($id);
        if ($list) {
            $response->data = new ApiData();
            $response->data->info = $list;
        } else {
            $response->data = new ApiData(102, '无相关数据');
        }
        return $response;
    }

    /**
     * 装修项目基本信息
     * @param $id
     * @return ApiResponse
     */
    public function actionDecorateDetail($id)
    {
        $response = new ApiResponse();

        if (empty($id) || !$id) {
            $response->data = new ApiData(101, '参数错误');
            return $response;
        }

        $result = DecorateProject::getDecorateProjectQueryById($id);

        if ($result['company_id'] == 0) {
            $result['brand_name'] = '其他';
        } else {
            $result['brand_name'] = (new Query())->select(['brand_name'])->from('ecs_brand')->where(['brand_id' => $result['company_id']])->scalar();
        }

        if ($result) {
            $response->data = new ApiData();
            $response->data->info = $result;
        } else {
            $response->data = new ApiData(102, '暂无数据');
        }

        return $response;
    }

    public function actionDecorateEdit()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->post();
        $webSite = 'http://pub.huilaila.net/';

        if (empty($data['id']) || empty($data['title']) || empty($data['budget'])) {
            $response->data = new ApiData(101, '参数错误');
            return $response;
        }

        $model = DecorateProject::findOne($data['id']);

        if (!$model || empty($model)) {
            $response->data = new ApiData(102, '暂无数据');
        }

        if ($model->load($data, '')) {
            if ($model->validate() == true && $model->save()) {
                $response->data = new ApiData();
            } else {
                $response->data = new ApiData(104, '保存失败');
            }
        } else {
            $response->data = new ApiData(103, '加载失败');
        }

        return $response;
    }

    /**
     * 是否是样板房
     */
    public function actionIsPrototyperoom() {
        $response = new ApiResponse();

        $id = f_get('id', 0);

        if ($id == 0) {
            $response->data = new ApiData(100, '参数错误');
        }

        $result = DecorateProject::find()->select(['is_prototyperoom'])->where(['id' => $id, 'valid' => 1])->one();

        if (!$result) {
            $result['is_prototyperoom'] = 0;
        }

        $response->data = new ApiData();
        $response->data->info = $result;

        return $response;
    }
}
