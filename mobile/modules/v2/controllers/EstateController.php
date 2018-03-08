<?php

namespace mobile\modules\v2\controllers;

use common\models\hll\UserAddress;
use Yii;
use yii\db\Query;
use common\models\ar\user\AccountAuth;
use common\models\ar\fang\FangLoupan;
use common\models\ar\user\AccountAddress;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\helpers\ArrayHelper;
use mobile\modules\v2\models\User;
use yii\data\ActiveDataProvider;
use yii\filters\HttpCache;

use common\models\ecs\EcsUsers;
use common\models\ecs\EcsWechatUser;
use common\components\WxTmplMsg;
/**
 * 房产认证控制器
 * @package mobile\modules\v2\controllers
 */
class EstateController extends ApiController {

//    public $second_cache = 60;
//    public function behaviors()
//    {
//        return ArrayHelper::merge(parent::behaviors(), [
//            [
//                'class' => HttpCache::className(),
//                'only' => [],
//                'lastModified' => function () { // 设置 Last-Modified 头
//                    return time() + $this->second_cache;
//                },
//                'cacheControlHeader' => 'Cache-Control: public, max-age='.$this->second_cache,
//            ],
//        ]);
//    }

    /**
     *   向系统后台认证(身份证、房产证)
     */
    public function actionSysAuth() {
        $response = new ApiResponse();


        $data = Yii::$app->request->post();

        if(!$data || empty($data['addressId']) || empty($data['imgs'])) {
            $response->data = new ApiData(101, '参数缺失');
            return $response;
        }

        $userId = Yii::$app->user->id;
        
        $model = new AccountAuth();
        $val['account_id'] = $userId;
        $val['auth_type'] = 3;
        $val['failnum'] = 0;
        if($data['type'] == 1){
            $val['address_id'] = $data['addressId'];
            $val['address_temp_id'] = 0;
            $isExist = AccountAuth::findOne(['account_id'=>$val['account_id'],
                'address_id'=>$val['address_id'],'auth_type'=>3,'auth_status'=>1]);
        }else{
            $val['address_temp_id'] = $data['addressId'];
            $val['address_id'] = 0;
            $isExist = AccountAuth::findOne(['account_id'=>$val['account_id'],
                'address_temp_id'=>$val['address_id'],'auth_type'=>3,'auth_status'=>1]);
        }
        $val['authdata'] = $data['imgs'];

        if($isExist) {
            $response->data = new ApiData(111,'数据已提交');
            return $response;
        }
        if ($model->load($val,'')) {
            if($model->validate() ) {
                if($model->save()) {
                    $response->data = new ApiData(0,'创建成功');
                    WxTmplMsg::taskHandleNotification(897);
                } else {
                    $response->data = new ApiData(110,'保存失败');
                }
            } else {
                $response->data = new ApiData(111,implode(',',$model->getFirstErrors()));
            }
        } else {
            $response->data = new ApiData(112,'数据装载失败');
        }

        return $response;

    }

    /**
     * 向房主认证
     */
    public function actionOwnerAuth() {
        $response = new ApiResponse();
        $data = Yii::$app->request->post();

        if(!$data || empty($data['desc']) || empty($data['addressId'])) {
            $response->data = new ApiData(101, '参数缺失');
            return $response;
        }

        $val['account_id'] = Yii::$app->user->id;
        $val['auth_type'] = 2;
        $val['failnum'] = 0;
        $val['address_id'] = $data['addressId'];
        $val['authdata'] = $data['desc'];

        $model = new AccountAuth();
        if($model->load($val, '')) {
            if($model->validate()) {
                if($model->save()) {
                    $response->data = new Apidata(0, '创建成功');
                }else {
                    $response->data = new ApiData(110, '保存失败');
                }
            }else {
                $response->data = new ApiData(111,implode(',',$model->getFirstErrors()));
            }
        }else {
            $response->data = new ApiData(112, '数据装载失败');
        }

        return $response;
    }

    /**
     * 已认证房主处理待认证请求
     * @params id -> accoun_auth.id
     */
    public function actionChoose() {
        $response = new ApiResponse();

        $data = Yii::$app->request->post();
        if(!$data || empty($data['id']) || empty($data['type'])) {
            $response->data = new ApiData(101, '参数缺失');
            return $response;
        }

        $model = AccountAuth::find()->where(['id'=>$data['id']])->one();

        if($data['type'] == 1) {
            //通过房主审核
            $result= UserAddress::authHouse(['account_id'=>$model->account_id,'address_id'=>$model->address_id]);
            if($result){
                $model->delete();
                $response->data = new ApiData(102, '确认认证');
            } else {
                $response->data = new ApiData(104, '审核失败');
            }
        }else {
            $model->failnum += 1;
            $model->failcause = $data['cause'];
            if($model->save()) {
                $response->data = new ApiData(102, '拒绝认证');
            }else {
                $response->data = new ApiData(103, '保存失败');
            }
        }

        return $response;
    }

    /** 认证详情 **/
    public function actionIndex() {
        $response = new ApiResponse();

        $data = Yii::$app->request->get();
        if(!$data || empty($data['id'])) {
            $response->data = new ApiData(101,'addressId不能为空');
            return $response;
        }

        $result = (new Query())->select(['t1.owner_auth'])
            ->from('hll_user_address as t1')
            ->where(['t1.account_id'=>Yii::$app->user->id, 't1.id'=>$data['id'], 't1.valid'=>1])
            ->scalar();

        if($data['type'] == 1){
            $val = (new Query())->select(['t1.failcause', 't1.auth_status'])
                ->from('account_auth as t1')
                ->where(['t1.account_id'=>Yii::$app->user->id, 't1.address_id'=>$data['id']])
                ->orderBy('id DESC')
                ->one();
        }else{
            $val = (new Query())->select(['t1.failcause', 't1.auth_status'])
                ->from('account_auth as t1')
                ->where(['t1.account_id'=>Yii::$app->user->id, 't1.address_temp_id'=>$data['id']])
                ->orderBy('id DESC')
                ->one();
        }

        if($result == '0'){
            if(!$val) {
                $response->data = new ApiData(102, '未认证');
                return $response;
            } else {
                if(!empty($val['failcause']) && $val['auth_status'] == 2) {
                    $response->data = new ApiData(103, '认证被拒绝');
                    $response->data->info = $val['failcause'];
                } else if($val['auth_status'] == 1){
                    $response->data = new ApiData(104, '认证进行中');
                }else {
                    $response->data = new ApiData(111, '数据不存在');
                }
                return $response;
            }
        }else if($result == '1' || $val['auth_status'] == 3) {
            $response->data = new ApiData();
            return $response;
        }else {
            $response->data = new ApiData(111, '数据不存在');
            return $response;
        }
    }

    /**
     * 认证途径
     * @params type (0-房产认证  1-管理员认证)
     * @params id (address_id)
     */
    public function actionWays() {
        $response = new ApiResponse();

        $data = Yii::$app->request->get();
        if(!$data || !isset($data['type']) || empty($data['id'])) {
            $response->data = new ApiData(101,'参数缺失');
            return $response;
        }
        $val = [];
        //$houseId = [];

        if($data['type'] == 0) {
            //$houseId = (new Query())->select(['t1.house_id'])->from('hll_user_address_ext as t1')->where(['t1.address_id'=>$data['id'], 't1.valid'=>1])->one();

            $val = (new Query())->select(['t2.user_id'])
                ->from('hll_user_address as t1')->leftJoin('ecs_user_address as t2', 't2.address_id = t1.address_id')
                ->where(['t1.address_id'=>$data['id'], 't1.valid'=>1])->all();
            if($val) {
                foreach ($val as &$item) {
                    $item['desc'] = EcsUsers::getUser($item['user_id']);
                }
            }
        }else if($data['type'] == 1) {

        }
        $user = EcsUsers::getUser(Yii::$app->user->id);

        $response->data = new ApiData();
        //$response->data->info['houseId'] = $houseId;
        $response->data->info['owners'] = $val;
        $response->data->info['cur'] = $user;

        return $response;
    }

}
