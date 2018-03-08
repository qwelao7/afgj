<?php

namespace mobile\modules\v2\controllers;

use common\models\ar\user\AccountAuth;
use common\models\ecs\EcsUsers;
use Yii;
use mobile\components\ApiController;
use common\models\ar\user\AccountAddress;
use common\models\ar\user\Account;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use common\models\ar\system\Area;
use yii\helpers\ArrayHelper;
use yii\filters\HttpCache;

use yii\db\Query;
use common\models\hll\UserAddressExt;

/**
 * 用户地址控制器
 * @package api\modules\v1\controllers
 */
class AddressController extends ApiController
{

//    public $second_cache = 60;
//
//    public function behaviors()
//    {
//        return ArrayHelper::merge(parent::behaviors(), [
//            [
//                'class' => HttpCache::className(),
//                'only' => ['index'],
//                'lastModified' => function () { // 设置 Last-Modified 头
//                    return time() + $this->second_cache;
//                },
//                'cacheControlHeader' => 'Cache-Control: public, max-age=' . $this->second_cache,
//            ],
//        ]);
//    }

    /**
     *
     * @SWG\Get(path="/address/index",
     *     tags={"address"},
     *     summary="获取用户地址信息",
     *     description="获取当前登录用户地址信息",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "access-token",
     *        description = "访问令牌",
     *        required = false,
     *        type = "string"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="成功操作",
     *         examples={{"name":"zhangsan"},{"name":"zhangsan"}},
     *         @SWG\Schema(ref="#/definitions/ApiResponse")
     *     ),
     *   @SWG\Response(response=401, description="Unauthorized"),
     *   @SWG\Response(response=400, description="Invalid ID supplied"),
     *   @SWG\Response(response=404, description="Order not found"),
     *
     *   security={{
     *     "api_key":{}
     *   }}
     * )
     *
     */
    /**
     * 获取用户地址列表
     * @params $userId
     */
    public function actionIndex()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->get();
        if (empty($data['userId'])) $data['userId'] = yii::$app->user->id;

        $data = (new Query())->select(['t1.address_id', 't1.address', 't1.sign_building', 't2.is_default', 't2.owner_auth'])->from('ecs_user_address as t1')
            ->leftJoin('hll_user_address_ext as t2', 't2.address_id = t1.address_id')
            ->where(['t1.user_id' => $data['userId'], 't2.valid' => 1])->orderBy(['t2.is_default' => SORT_ASC, 't2.owner_auth' => SORT_DESC])->all();
        if (!$data) {
            $response->data = new ApiData(101, '房产信息为空');
            return $response;
        }

        $response->data = new ApiData();
        $response->data->info = $data;

        return $response;
    }

    public function actionAddressItems($userId = null)
    {
        if (!$userId) $userId = Yii::$app->user->id;
        $data = (new Query())->select(['t1.address_id', 't1.address', 't1.sign_building', 't2.is_default', 't2.owner_auth'])->from('ecs_user_address as t1')
            ->leftJoin('hll_user_address_ext as t2', 't2.address_id = t1.address_id')
            ->where(['t1.user_id' => $userId, 't2.valid' => 1])->orderBy(['t2.is_default' => SORT_ASC, 't2.owner_auth' => SORT_DESC])->all();

        return $this->renderRest($data);
    }

    /**
     *
     * @SWG\Post(path="/address/create",
     *     tags={"address"},
     *     summary="获取用户信息",
     *     description="获取用户信息,默认获取当前登录用户,也可以获取指定用户信息",
     *     consumes={"application/x-www-form-urlencoded","application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "access-token",
     *        description = "访问令牌",
     *        required = false,
     *        type = "string"
     *     ),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         description="Pet object that needs to be added to the store",
     *         required=false,
     *         @SWG\Schema(ref="#/definitions/User"),
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="成功操作",
     *         examples={{"name":"zhangsan"},{"name":"zhangsan"}},
     *         @SWG\Schema(ref="#/definitions/ApiResponse")
     *     ),
     *   @SWG\Response(response=401, description="Unauthorized"),
     *   @SWG\Response(response=400, description="Invalid ID supplied"),
     *   @SWG\Response(response=404, description="Order not found"),
     *
     *   security={{
     *     "api_key":{}
     *   }}
     * )
     *
     */
    /**
     * 创建房产
     */
    public function actionCreate()
    {

        $response = new ApiResponse();

        $model = new AccountAddress();

        if ($model->load(Yii::$app->request->post(), '')) {

            if ($model->validate()) {
                if ($model->save()) {
                    $response->data = new ApiData(0, '创建成功');
                } else {
                    $response->data = new ApiData(110, '保存失败');
                }
            } else {
                $response->data = new ApiData(111, implode(',', $model->getFirstErrors()));
            }
        } else {
            $response->data = new ApiData(112, '数据装载失败');
        }

        return $response;
    }

    /**
     * 更新地址
     * @param $id  address_id
     */
    public function actionUpdate($id)
    {
        $response = new ApiResponse();
        $model = AccountAddress::findOne($id);

        if ($model->load(Yii::$app->request->post(), '')) {
            $model->is_default == 'yes' && AccountAddress::setDefault($id, Yii::$app->user->id);
            if ($model->validate()) {
                if ($model->save()) {
                    $response->data = new ApiData(0, '更新成功');
                } else {
                    $response->data = new ApiData(110, '更新失败');
                }
            } else {
                $response->data = new ApiData(111, implode(',', $model->getFirstErrors()));
            }
        } else {
            $response->data = new ApiData(112, '数据装载失败');
        }
        return $response;

    }

    /**
     * 设置默认地址
     * @param type $id
     * @return type
     */
    public function actionDefault($id)
    {
        $response = new ApiResponse();
        $model = AccountAddress::findOne($id);
        if ($model) {
            $flag = AccountAddress::setDefault($id, Yii::$app->user->id);
            if ($flag) {
                $response->data = new ApiData(0, '设置默认地址成功');
            } else {
                $response->data = new ApiData(113, '设置默认地址失败');
            }
        } else {
            $response->data = new ApiData(115, '找不到对应数据');
        }
        return $response;
    }

    /**
     * 删除地址
     */
    public function actionDelete()
    {

        $response = new ApiResponse();

        $data = Yii::$app->request->post();

        if (!$data || empty($data['id'])) {
            $response->data = new ApiData(101, 'id参数缺失');
            return $response;
        }

        $tpl = UserAddressExt::findOne(['address_id'=>$data['id']]);
        if ($tpl) {
            if ($tpl['is_default'] == 'yes') {
                $response->data = new ApiData(117, '默认地址无法删除');
            }
            $tpl['valid'] = 0;
            if ($tpl->save()) {
                $response->data = new ApiData(0, '操作成功');
            } else {
                $response->data = new ApiData(119, '操作失败');
            }
        } else {
            $response->data = new ApiData(102, '找不到相关数据');
        }

        return $response;
    }

    /**
     * 判断用户是否有虚拟房产
     */
    public function actionVirtualFicititous($loupanId = 14)
    {
        $response = new ApiResponse();
        $count = AccountAddress::find()->where(['loupan_id' => $loupanId, 'account_id' => Yii::$app->user->id])->count();
        if ($count) {
            $response->data = new ApiData(0, '用户有虚拟房产');
        } else {
            $response->data = new ApiData(1, '用户无虚拟房产');
        }
        return $response;
    }

    /**
     * 某个房产的房产信息
     */
    public function actionInfo()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->get();
        if (!$data || empty($data['addressId'])) {
            $response->data = new ApiData(101, 'addressId不能空');
            return $response;
        }

        $info = (new Query())->select(['t1.address_id', 't1.consignee', 't1.province', 't1.city', 't1.district', 't1.address', 't1.mobile', 'sign_building', 't2.house_id', 't2.owner_auth', 't2.is_default'])
            ->from('ecs_user_address as t1')->leftJoin('hll_user_address_ext as t2', 't2.address_id = t1.address_id')
            ->where(['t1.address_id' => $data['addressId'], 't2.valid' => 1])->one();
        if (!$info) {
            $response->data = new ApiData(102, '房产信息为空');
            return $response;
        }

        $regionNames = EcsUsers::getUserRegionDetailName($info['province'], $info['city'], $info['district']);
        if ($regionNames) {
            $info['province'] = $regionNames[0];
            $info['city'] = $regionNames[1];
            $info['district'] = $regionNames[2];
        }
        $response->data = new ApiData();
        $response->data->info = $info;

        return $response;
    }
}

