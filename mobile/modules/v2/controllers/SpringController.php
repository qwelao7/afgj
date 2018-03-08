<?php

namespace mobile\modules\v2\controllers;

use common\models\ar\system\QrCode;
use common\models\ecs\EcsGoods;
use common\models\ecs\EcsOrderGoods;
use common\models\ecs\EcsOrderInfo;
use common\models\ecs\EcsUserAddress;
use Yii;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use common\models\SpringActivity;
use common\models\HllSpringAward;
use common\models\hll\UserAddress;
use common\models\hll\Bbs;
use yii\base\Exception;
use yii\db\Query;
use yii\db;


/**
 * 春节活动
 * Class springcontroller
 * @package mobile\modules\v2\controllers
 */
class SpringController extends ApiController
{
    /**
     * 新年礼包进入页面
     * @return ApiResponse
     */
    public function actionPresentIndex(){
        $response = new ApiResponse();
        $code = f_get('code',0);
        $time = time();
        $dateline = strtotime("2018-01-31 23:59:59");
        if($time > $dateline){
            $response->data = new ApiData(103,'该礼盒劵已过期！');
            return $response;
        }
        $result = QrCode::find()->where(['valid'=>1,'qr_url'=>$code])->asArray()->one();
        if($result) {
            if($result['item_id'] > 0) {
                $order_info = (new Query())->select(['goods_id','user_id'])->from('ecs_order_goods as t1')
                    ->leftJoin('ecs_order_info as t2','t2.order_id = t1.order_id')
                    ->where(['t2.order_id'=>$result['item_id']])->one();

                if($order_info['user_id'] == Yii::$app->user->id){
                    $response->data = new ApiData(101,'该礼盒劵已兑换成功！');
                    $response->data->info = [ 'order_id'=>$result['item_id'],'item_type'=>$result['item_type']];
                }else{
                    $response->data = new ApiData(102,'该礼盒劵已兑换成功！');
                    $response->data->info = [ 'goods_id'=>$order_info['goods_id'],'item_type'=>$result['item_type']];
                }
            }else {
                $response->data = new ApiData();
                $response->data->info = $result['item_type'];
            }
        } else {
            $response->data = new ApiData(102,'系统无法查询到该礼盒劵！');
        }
        return $response;
    }

    /**
     * 填写收货地址
     * @return ApiResponse
     */
    public function actionAddAddress(){
        $response = new ApiResponse();

        $data = f_post('data','');
        $desc = $data['desc'];
        //$type = intval($data['type']);
        $user_id = Yii::$app->user->id;
        $user_info = parent::$sessionInfo;

        if(empty($desc) || empty($data['consignee']) || empty($data['mobile']) || empty($data['address'])){
            $response->data = new ApiData(101,'参数不能为空！');
            return $response;
        }

        $qr = QrCode::findOne(['qr_url'=>$data['qr_code']]);
        if(!$qr || $qr->item_id > 0 || !in_array($qr->item_type, [5,6],true)) {
            $response->data = new ApiData(102,'系统无法查询到该礼盒劵！');
            return $response;
        }

        $region = (new Query())->select(['region_id','parent_id'])
            ->from('ecs_region')->where(['region_name'=>$desc])->one();

        if($region){
            $data['city'] = $region['parent_id'];
            $data['district'] = $region['region_id'];
            $data['country'] = 1;
        } else {
            $response->data = new ApiData(103,'选择配送区域信息有误！');
            return $response;
        }
        $data['province'] = (new Query())->select(['parent_id'])
            ->from('ecs_region')->where(['region_id'=>$region['parent_id']])->scalar();
        $user_address = EcsUserAddress::find()->where(['consignee'=>$data['consignee'],'mobile'=>$data['mobile'],'user_id'=>$user_id,
            'city'=>$data['city'],'district'=>$data['district'],'province'=>$data['province'],'address'=>$data['address']])->one();
        if(!$user_address){
            $user_address = new EcsUserAddress();
            $data['user_id'] = $user_id;
            $user_address->load($data,'') && $user_address->save(false);
        }

        $goods_id = f_params('spring_present_goods')[$qr->item_type];
        $goods = EcsGoods::findOne($goods_id);
        $order_info = [
            'order_sn' => date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'user_id' => $user_id,
            'consignee' => $user_address->consignee,
            'country' => $user_address->country,
            'province' => $user_address->province,
            'city' => $user_address->city,
            'district' => $user_address->district,
            'address' => $user_address->address,
            'mobile' => $user_address->mobile,
            'shipping_id' => 9,
            'order_status' => 1,
            'shipping_status' => 0,
            'pay_status' => 2,
            'pay_id' => 0,
            'shipping_name' => '免运费',
            'to_buyer' => $qr->qr_pic_path,
            'pay_note' => $data['message'],
            'goods_amount' => $goods->shop_price,
            'order_amount' => $goods->shop_price,
            'add_time' => time(),
            'discount' => $user_info['discount'],
            'seller_id' => $goods->business_id,
        ];

        $tran = Yii::$app->db->beginTransaction();
        try{
            $order = new EcsOrderInfo();
            if($order->load($order_info,'') && $order->save(false)){
                $order_goods = new EcsOrderGoods();
                $goods_info = [
                    'order_id' => $order->order_id,
                    'goods_id' => $goods_id,
                    'goods_name' => $goods->goods_name,
                    'goods_sn' => $goods->goods_sn,
                    'goods_number' => 1,
                    'market_price' => $goods->market_price,
                    'goods_price' => $goods->shop_price,
                    'is_real' => $goods->is_real,
                    'extension_code' => $goods->extension_code,
                ];
                if($order_goods->load($goods_info,'') && $order_goods->save(false)){
                    $qr->item_id = $order->order_id;
                    if($qr->save()){
                        $tran->commit();
                        $response->data = new ApiData();
                        $response->data->info = $order->order_id;
                        return $response;
                    }
                }
            }
            throw new Exception("兑换不成功，请稍后再试！",104);
        }catch (Exception $e){
            Yii::warning($e->getCode()."--and--".$e->getMessage());
            $tran->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
            return $response;
        }
    }
//    public function actionIndex() {
//        $response = new ApiResponse();
//
//        $userId = Yii::$app->user->id;
//
//        /** 活动完成人数统计 **/
//        $data = SpringActivity::getUsersTask();
//
//        /** 任务完成情况 **/
//        $cur = SpringActivity::getUserTaskStatus($userId);
//
//        /** 中奖统计 **/
//        $list = SpringActivity::getAwardResult();
//
//        /** 当前用户是否拥有房产 **/
//        $fang = UserAddress::hasHouse();
//        if ($fang) {
//            $fang['forum'] = Bbs::getBbsIdByCommunity($fang['community_id']);
//        }
//
//        if ($data || $cur) {
//            $response->data = new ApiData();
//            $response->data->info['activity'] = $data;
//            $response->data->info['cur'] = $cur;
//            $response->data->info['list'] = $list;
//            $response->data->info['fang'] = $fang;
//        } else {
//            $response->data = new ApiData(100, '数据出错');
//        }
//
//        return $response;
//    }
//    /**
//     * 绑定礼品形象信息
//     * @return ApiResponse
//     */
//    public function actionBindQrcode(){
//        $response = new ApiResponse();
//        $code = f_post('code','');
//        $type = f_post('type','');
//        $name = '财';
//
//        $result = QrCode::find()->where(['valid'=>1,'item_type'=>$type,'qr_url'=>$code])->one();
//        if($result){
//            if(empty($result->qr_pic_path)){
//                $result->qr_pic_path = $name;
//                $result->save(false);
//                $response->data = new ApiData();
//            }else{
//                $response->data = new ApiData(101,'二维码已绑定！');
//            }
//        }else{
//            $response->data = new ApiData(101,'数据有误！');
//        }
//        return $response;
//    }
}

?>
