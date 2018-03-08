<?php

namespace mobile\modules\v2\controllers;

use common\models\ar\user\AccountAuth;
use common\models\ecs\EcsUserAddress;
use common\models\ecs\EcsUsers;
use common\models\hll\Community;
use EasyWeChat\Core\Exception;
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
use common\models\hll\UserAddressTemp;
use common\models\hll\UserAddress;
/**
 * 房产控制器
 * @package api\modules\v2\controllers
 */
class HouseController extends ApiController
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
     * 获取用户地址列表
     * @params $userId
     */
    public function actionIndex()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->get();
        if (empty($data['userId'])) $data['userId'] = yii::$app->user->id;

        $address = (new Query())->select(['t1.id','t2.city','t2.district','t1.address_desc', 't1.is_default', 't1.owner_auth'])
            ->from('hll_user_address as t1')
            ->leftJoin('hll_community as t2', 't2.id = t1.community_id')
            ->where(['t1.account_id' => $data['userId'], 't2.valid' => 1,'t1.valid' => 1])->orderBy(['t1.is_default' => SORT_ASC, 't1.owner_auth' => SORT_DESC])->all();

        $address_temp = (new Query())->select(['id','city','district','is_default', 'community_name', 'building_num', 'group_name', 'unit_num', 'house_num'])
            ->from('hll_user_address_temp')->where(['account_id' => $data['userId'], 'valid' => 1])->orderBy(['is_default' => SORT_ASC])->all();

        if (!$data) {
            $response->data = new ApiData(101, '房产信息为空');
            return $response;
        }

        foreach($address as &$item) {
            $item['city_name'] = (new Query())->select(['region_name'])->from('ecs_region')->where(['region_id'=>$item['city']])->scalar();
            $item['district_name'] = (new Query())->select(['region_name'])->from('ecs_region')->where(['region_id'=>$item['district']])->scalar();
        }

        foreach($address_temp as &$item) {
            $item['city_name'] = (new Query())->select(['region_name'])->from('ecs_region')->where(['region_id'=>$item['city']])->scalar();
            $item['district_name'] = (new Query())->select(['region_name'])->from('ecs_region')->where(['region_id'=>$item['district']])->scalar();
        }

        $info['address'] = $address;
        $info['address_temp'] = $address_temp;
        $response->data = new ApiData();
        $response->data->info = $info;

        return $response;
    }
    
    public function actionAddressItems($userId = null)
    {
        if (!$userId) $userId = Yii::$app->user->id;
        $data = (new Query())->select(['t1.address_id', 't1.address', 't1.sign_building', 't2.is_default', 't2.owner_auth'])->from('ecs_user_address as t1')
            ->leftJoin('hll_user_address as t2', 't2.address_id = t1.address_id')
            ->where(['t1.user_id' => $userId, 't2.valid' => 1])->orderBy(['t2.is_default' => SORT_ASC, 't2.owner_auth' => SORT_DESC])->all();

        return $this->renderRest($data);
    }

    /**
     * 添加房产
     * @return ApiResponse
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionCreate()
    {

        $response = new ApiResponse();

        //地址是否重复
        $data = Yii::$app->request->post('frmHouse');
        $data['community_name'] = Community::find()->select(['name'])->where(['id' => $data['community_id'], 'valid' => 1])->scalar();

        $result = (new Query())->from('hll_user_address')
                ->where(['account_id'=>Yii::$app->user->id,'community_id'=>$data['community_id'],
                'group_name'=>$data['group_name'],'building_num'=>$data['building_num'],
                'unit_num'=>$data['unit_num'],'house_num'=>$data['house_num'],'valid'=>1])->count();

        if($result > 0){
            $response->data = new ApiData(119, '数据重复');
            return $response;
        }
        $userAddressObj = new UserAddress();//房产地址详情楼栋号、单元、门牌号
        if ($userAddressObj->load($data, '')) {
            $userAddressObj->account_id = Yii::$app->user->id;
            $userAddressObj->address_desc = UserAddress::generateAddressDesc($data);
            if ($userAddressObj->save()) {
                $userAddressObj->is_default == 'yes' && UserAddress::setDefault($userAddressObj->id, Yii::$app->user->id);
                $response->data = new ApiData(0, '创建成功');
                $response->data->info = $userAddressObj->id;
            } else {
                $response->data = new ApiData(110, '保存失败');
            }
        } else {
            $response->data = new ApiData(112, '数据装载失败');
        }

        return $response;
    }

    /**
     * 手动添加房产
     * @return ApiResponse
     * @author kaikai.qin
     * @date  2016-10-10 15:20
     */
    public function actionCreateByUser()
    {

        $response = new ApiResponse();
        //地址是否重复
        $data = Yii::$app->request->post('frmHouse');
        $result = (new Query())->from('hll_user_address_temp')
                ->where(['account_id'=>Yii::$app->user->id,'community_name'=>$data['community_name'],
                'group_name'=>$data['group_name'],'building_num'=>$data['building_num'],
                'unit_num'=>$data['unit_num'],'house_num'=>$data['house_num'],'valid'=>1])->count();

        if($result > 0){
            $response->data = new ApiData(119, '数据重复');
            return $response;
        }

        $userAddressObj = new UserAddressTemp();//房产地址详情楼栋号、单元、门牌号

        $district_id = Yii::$app->request->post('district_id');
        if ($userAddressObj->load($data, '')) {
            $region = Community::getRegionByDistrict($district_id);
            $userAddressObj->account_id = Yii::$app->user->id;
            $userAddressObj->city = $region[0];
            $userAddressObj->province = $region[1];
            $userAddressObj->district = $district_id;
            $userAddressObj->address_desc = UserAddress::generateAddressDesc($data);
            if ($userAddressObj->save()) {
                $userAddressObj->is_default == 'yes' && UserAddressTemp::setDefault($userAddressObj->id, Yii::$app->user->id);
                $response->data = new ApiData(0, '创建成功');
                $response->data->info = $userAddressObj->id;
            } else {
                $response->data = new ApiData(110, '保存失败');
            }
        } else {
            $response->data = new ApiData(112, '数据装载失败');
        }

        return $response;
    }

    /**
     * 删除地址
     * @param $id 房产编号
     * @return ApiResponse
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionDelete()
    {
        $response = new ApiResponse();
        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');

        if($type == 1){
            $userAddressObj = UserAddress::find()->where(['id'=>$id])->one();//房产地址详情楼栋号、单元、门牌号
        }else{
            $userAddressObj = UserAddressTemp::find()->where(['id'=>$id])->one();//房产地址详情楼栋号、单元、门牌号
        }

        if ($userAddressObj) {
            $userAddressObj['valid'] = 0;
            if ($userAddressObj->save()) {
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
     * 更新房产地址
     * @param $id 房产编号
     * @param $type 房产类型
     * @return ApiResponse
     * @author zend.wang
     * @date  2016-10-09 13:00
     */
    public function actionUpdate()
    {
        $response = new ApiResponse();
        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');

        if($type == 1){
            $userAddressObj = UserAddress::find()->where(['id'=>$id])->one();//房产详情
        }else{
            $userAddressObj = UserAddressTemp::find()->where(['id'=>$id])->one();//房产详情
        }
        if ($userAddressObj->load(Yii::$app->request->post(),'frmHouse')) {
            if($type == 1) {
                $userAddressObj->is_default == 'yes' && UserAddress::setDefault($id, Yii::$app->user->id);
            }else {
                $userAddressObj->is_default == 'yes' && UserAddressTemp::setDefault($id, Yii::$app->user->id);
            }

            if ($userAddressObj->save()) {
                $response->data = new ApiData(0, '更新成功');
            } else {
                $response->data = new ApiData(110, '更新失败');
            }
        } else {
            $response->data = new ApiData(112, '数据装载失败');
        }
        return $response;

    }

    /**
     * @param $id int 房产编号
     * @param $type int 房产类型
     */
    public function actionUpdateDesc($id,$type){
        $response = new ApiResponse();

        if (empty($id)) {
            $response->data = new ApiData(101, 'id参数缺失');
            return $response;
        }
        if($type == 1){
            $info = (new Query())->select(['t1.id', 't1.consignee', 't2.province', 't2.city',
                't2.district', 't1.mobile', 't1.community_id', 't1.is_default',
                't1.group_name','t1.building_num','t1.unit_num','t1.house_num','t2.name'])
                ->from('hll_user_address as t1')
                ->leftJoin('hll_community as t2', 't2.id = t1.community_id')
                ->where(['t1.id' => intval($id), 't1.valid' => 1])->one();
        }else{
            $info = (new Query())->select(['id', 'consignee', 'province', 'city',
                'district', 'mobile', 'community_name', 'is_default',
                'group_name','building_num','unit_num','house_num'])
                ->from('hll_user_address_temp')
                ->where(['id' => intval($id), 'valid' => 1])->one();
        }

        if (!$info) {
            $response->data = new ApiData(102, '房产信息为空');
            return $response;
        }

        $regionNames = EcsUsers::getUserRegionDetailName($info['province'], $info['city'], $info['district']);
        if ($regionNames) {
            $info['district'] = $regionNames[2];
        }
        $response->data = new ApiData();
        $response->data->info = $info;

        return $response;
    }

    /**
     * 设置默认地址
     * @param int $id
     * @return ApiResponse
     */
    public function actionDefault($id,$type)
    {
        $response = new ApiResponse();
        if($type == 1){
            $model = UserAddress::findOne(['address_id'=>$id]);
        }else{
            $model = UserAddressTemp::findOne(['address_id'=>$id]);
        }
        if ($model) {
            $flag = UserAddress::setDefault($id, Yii::$app->user->id);
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
     * 房产详情 new
     */
    public function actionDetail($id,$type)
    {
        $response = new ApiResponse();

        if (empty($id)) {
            $response->data = new ApiData(101, 'id参数缺失');
            return $response;
        }
        if($type == 1){
            $info = (new Query())->select(['t1.id', 't1.consignee', 't2.province', 't2.city', 't2.district','t1.mobile',
                't1.community_id','t1.owner_auth', 't1.is_default','t1.address_desc'])
                ->from('hll_user_address as t1')
                ->leftJoin('hll_community as t2','t2.id = t1.community_id')
                ->where(['t1.id' => intval($id),'t1.valid'=> 1])->one();
        }else{
            $info = (new Query())->select(['id', 'consignee', 'province', 'city',
                'district', 'mobile', 'community_name', 'is_default',
                'group_name','building_num','unit_num','house_num'])
                ->from('hll_user_address_temp')->where(['id' => intval($id), 'valid' => 1])->one();
        }

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

    /**
     * 检查小区的过滤字段
     */
    public function actionFilter() {
        $response = new ApiResponse();

        $id = Yii::$app->request->get('id');
        if(!$id || !isset($id)) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $arr = [true, true, true];
        $query = Community::findOne($id);
        if(!$query) {
            $response->data = new ApiData(101, '数据不存在');
        }else {
            $visibility = $query->visible;
            if($visibility != null) {
                $visibility = str_split($visibility, 1);
                $arr = array_map(function($v){
                    return $v = ($v == 1)?true:false;
                }, $visibility);
            }

            $response->data = new ApiData();
            $response->data->info = $arr;
        }

        return $response;
    }

    /**
     * 添加收货地址
     * @return ApiResponse
     */
    public function actionCreateShippingAddress(){
        $response = new ApiResponse();

        $data = f_post('data','');
        if(!$data){
            $response->data = new ApiData('数据错误',101);
        }else{
            $data['user_id'] = Yii::$app->user->id;
            $region = (new Query())->select(['region_id','parent_id'])
                ->from('ecs_region')->where(['region_name'=>$data['desc']])->one();
            
            if(!$region){
                $response->data = new ApiData('数据错误',102);
            }else{
                $data['district'] = $region['region_id'];
                $data['city'] = $region['parent_id'];
                $data['province'] = (new Query())->select(['parent_id'])
                    ->from('ecs_region')->where(['region_id'=>$region['parent_id']])->scalar();
                if($data['is_default'] == 1){
                    EcsUserAddress::updateAll(['is_default' => 0], ['user_id' => $data['user_id']]);
                }
                $model = new EcsUserAddress();
                if($model->load($data,'') && $model->save()){
                    $response->data = new ApiData();
                    $response->data->info['id'] = $model->address_id;
                }else{
                    $response->data = new ApiData('添加失败',103);
                }
            }
        }
        return $response;
    }

    /**
     * 收货地址列表
     * @return ApiResponse
     */
    public function actionShippingAddressList($address_id = 0){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;

        $list = EcsUserAddress::getAddressByUserId($user_id,$address_id);
        $response->data = new ApiData();
        $response->data->info = $list;
        return $response;
    }

    /**
     * 收货地址详情
     * @param $id
     * @return ApiResponse
     */
    public function actionShippingAddressDetail($id){
        $response = new ApiResponse();

        $fields = ['consignee','mobile','is_default','province','city','district','address'];
        $address = EcsUserAddress::find()->select($fields)->where(['address_id'=>$id,'valid'=>1])->asArray()->one();
        if(!$address){
            $address = [];
        }else{
            $address['district'] = EcsUserAddress::getProvinceAndCity($address['province']).' '.EcsUserAddress::getProvinceAndCity($address['city']).' '.
                EcsUserAddress::getProvinceAndCity($address['district']);
        }
        $response->data = new ApiData();
        $response->data->info = $address;
        return $response;
    }

    /**
     * 编辑收货地址
     * @return ApiResponse
     */
    public function actionUpdateShippingAddress(){
        $response = new ApiResponse();

        $data = f_post('data','');
        try{
            if(!$data){
                throw new Exception(101,'数据错误');
            }

            $model = EcsUserAddress::findOne($data['id']);
            if(!$model){
                throw new Exception(102,'数据错误');
            }
            $region = (new Query())->select(['region_id','parent_id'])
                ->from('ecs_region')->where(['region_name'=>$data['desc']])->one();
            if(!$region){
                throw new Exception(103,'数据错误');
            }

            $data['user_id'] = Yii::$app->user->id;
            $data['district'] = $region['region_id'];
            $data['city'] = $region['parent_id'];
            $data['province'] = (new Query())->select(['parent_id'])
                ->from('ecs_region')->where(['region_id'=>$region['parent_id']])->scalar();
            if($data['is_default'] == 1){
                EcsUserAddress::updateAll(['is_default' => 0], ['user_id' => $data['user_id']]);
            }

            if($model->load($data,'') && $model->save()){
                $response->data = new ApiData();
                $response->data->info = $model->address_id;
            }else{
                throw new Exception(104,'添加失败');
            }
        }catch (Exception $e){
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    /**
     * 扫码成为房产房主
     * @param $addressId -> 房产id
     */
    public function actionQrCodeFang ($addressId) {
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;

        if (empty($addressId)) {
            $response->data = new ApiData(200, '参数错误');
            return $response;
        }

        $address = UserAddress::find()->where(['id' => $addressId, 'valid' => 1])->one();

        if (empty($address)) {
            $response->data = new ApiData(300, '数据错误');
            return $response;
        } else {
            $query = UserAddress::find()->where(['address_desc' => $address->address_desc, 'account_id' => $userId, 'valid' => 1, 'owner_auth' => 1])->one();

            if (empty($query)) {
                $newAddress = new UserAddress();

                $newAddress['account_id'] = $userId;
                $newAddress['community_id'] = $address['community_id'];
                $newAddress['consignee'] = $address['consignee'];
                $newAddress['mobile'] = $address['mobile'];
                $newAddress['group_name'] = $address['group_name'];
                $newAddress['building_num'] = $address['building_num'];
                $newAddress['unit_num'] = $address['unit_num'];
                $newAddress['house_num'] = $address['house_num'];
                $newAddress['address_desc'] = $address['address_desc'];
                $newAddress['owner_auth'] = 1;
                
                if ($newAddress->save()) {
                    $response->data = new ApiData();
                } else {
                    $response->data = new ApiData(302, '保存失败');
                }
            } else {
                $response->data = new ApiData(301, '您已拥有该房产!');
            }

            return  $response;
        }
    }

    /**
     * 房产分享界面
     * @param addressId -> address_id
     */
    public function actionShareIndex ($addressId) {
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;

        if (empty($addressId)) {
            $response->data = new ApiData(200, '参数错误');

            return $response;
        } else {
            $query = UserAddress::find()->select(['address_desc'])->where(['id' => $addressId, 'account_id' => $userId, 'valid' => 1])->one();

            if (!empty($query)) {
                $list = (new Query())->select(['t1.id', 't1.account_id', 't2.headimgurl', 't2.nickname','t1.id'])
                    ->from('hll_user_address as t1')
                    ->leftJoin('ecs_wechat_user as t2', 't2.ect_uid = t1.account_id')
                    ->where(['t1.owner_auth' => 1, 't1.address_desc' => $query['address_desc'], 't1.valid' => 1])
                    ->andWhere(['<>', 't1.account_id', $userId])->orderBy(['t1.created_at' => SORT_ASC])->all();

                if (empty($list)) {
                    $list = [];
                }
                
                $info['user'] = EcsUsers::getUser($userId, ['t2.nickname']);
                $info['address'] = $query;
                $info['members'] = $list;

                $response->data = new ApiData();
                $response->data->info = $info;
                
            } else {
                $response->data = new ApiData(301, '请先认证房产!');
            }

            return $response;
        }

    }
    
    /**
     * 确认加入房产页面
     * @param addressId -> address_id
     */
    public function actionShareFangConfirm ($addressId) {
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        
        if (empty($addressId)) {
            $response->data = new ApiData(200, '参数错误');
            return $response;
        } else {
            $address = UserAddress::find()->select(['address_desc'])->where(['id' => $addressId, 'valid' => 1])->one();
            
            if (!empty($address)) {
                $query = UserAddress::find()->where(['address_desc' => $address->address_desc, 'account_id' => $userId, 'valid' => 1, 'owner_auth' => 1])->count();
                
                if ($query == 0) {
                    $hasPass = false;
                } else {
                    $hasPass = true;
                }
                
                $response->data = new ApiData();
                $response->data->info['address_desc'] = $address->address_desc;
                $response->data->info['hasPass'] = $hasPass;
            } else {
                $response->data = new ApiData(201, '数据错误');
            }
            
            return $response;
        }
    }
}

