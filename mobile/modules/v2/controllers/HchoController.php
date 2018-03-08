<?php
namespace mobile\modules\v2\controllers;

use common\models\ecs\EcsCart;
use common\models\ecs\EcsUserAddress;
use common\models\ecs\EcsUsers;
use common\models\hll\HllEventHcho;
use common\models\hll\HllEventHchoDetail;
use common\models\hll\HllEvents;
use common\models\hll\HllEventsApply;
use mobile\components\ApiController;
use Yii;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\base\Exception;
use common\components\WxpayV2;
use common\models\ecs\EcsGoods;
use common\models\ecs\EcsOrderInfo;
use common\models\ecs\EcsPayLog;
use common\models\ecs\EcsUserBonus;
use common\models\hll\HllBill;
use common\models\hll\HllUserPointsLog;
use yii\db\Query;

class HchoController extends ApiController{
    /**
     * Created by PhpStorm.
     * User: nancy
     * Date: 2017/4/19
     * Time: 14:46
     */

    /**
     * 甲醛检测列表
     * @return ApiResponse
     */
    public function actionIndex(){
        $response = new ApiResponse();

        $page = f_get('page',1);

        $query = HllEventHchoDetail::getHchoDetailList();
        $info = $this->getDataPage($query,$page);
        if($info['list']){
            foreach ($info['list'] as &$item) {
                $item['pics'] = (!empty($item['pics'])) ? explode(',',$item['pics']) : [];
                $item['detail'] = HllEventHchoDetail::getHchoDetail($item['id']);
                $item['user'] = EcsUsers::getUser($item['account_id'],['t2.nickname','t2.headimgurl']);
                $item['address'] = EcsUserAddress::getAddressDesc($item['address']);
            }
        }
        $info['statistics'] = HllEventHchoDetail::getHchoStatistics();
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 获取用户认证房产
     * @return ApiResponse
     */
    public function actionAuthAddress(){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $info = EcsUserAddress::getAuthAddressByUserId($user_id);

        if(empty($info)){
            $response->data = new ApiData(101,'暂无房产');
            return $response;
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 添加反馈结果
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionFeedback(){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $data = f_post('point','0');
        $address_id = f_post('address_id',0);
        $content = f_post('content',0);
        $pic = f_post('pic',0);
        if($data == '0' || $address_id == 0){
            $response->data = new ApiData(101,'缺少数据!');
            return $response;
        }
        $trans = Yii::$app->db->beginTransaction();
        try{
            $hcho = new HllEventHcho();
            $hcho->account_id = $user_id;
            $hcho->address_id = $address_id;
            $hcho->content = $content;
            $hcho->pics = $pic;
            if($hcho->save()){
                $result = HllEventHchoDetail::setHchoDetail($hcho->id,$data,$user_id);
                if($result > 0){
                    $statistics = HllEventHchoDetail::getCheckPoint($hcho->id);
                    $hcho->check_point_num = $statistics['whole_num'];
                    $hcho->check_ok_num = $statistics['ok_num'];
                    if($hcho->save()){
                        $trans->commit();
                        $response->data = new ApiData(0,'添加成功');
                        $response->data->info = $hcho->id;
                    }else{
                        throw new Exception($hcho->getErrors(),103);
                    }
                }else{
                    throw new Exception('检测数据添加失败',102);
                }
            }else{
                throw new Exception($hcho->getErrors(),103);
            }
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }

        return $response;
    }

    /**
     * 获取检测详情
     * @param $id
     * @return ApiResponse
     */
    public function actionHchoDetail($id){
        $response = new ApiResponse();

        $userId = Yii::$app->user->id;
        $info = HllEventHcho::find()->select(['address_id as address','content','pics','created_at','id','account_id'])
            ->where(['id'=>$id,'valid'=>1])->asArray()->one();
        if(!$info){
            $response->data = new ApiData(101,'数据错误');
            return $response;
        }
        $info['pics'] = (!empty($info['pics'])) ? explode(',',$info['pics']) : [];
        $info['detail'] = HllEventHchoDetail::getHchoDetail($info['id']);
        $info['user'] = EcsUsers::getUser($info['account_id'],['t2.nickname','t2.headimgurl']);
        $info['address'] = EcsUserAddress::getAddressDesc($info['address']);
        $info['mine'] = EcsUsers::getUser($userId,['t2.headimgurl']);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 收货地址
     * @return ApiResponse
     */
    public function actionUserAddress(){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $address_id = f_get('address_id',0);
        $address = EcsUserAddress::getAddressByAddressId($user_id,$address_id);
        $response->data = new ApiData();
        $response->data->info = $address;
        return $response;
    }

    /**
     * 保存报名信息
     * @return ApiResponse
     */
    public function actionSaveApply(){
        $response = new ApiResponse();
        $address_id = f_get('address_id',0);
        $events_id = f_get('events_id',0);
        $user_id = Yii::$app->user->id;

        $str = null;
        $strPol = "0123456789";
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < 5; $i++) {
            $str .= $strPol[rand(0, $max)];
        }

        if($address_id == 0 || $events_id == 0){
            $response->data = new ApiData(101,'数据错误');
        }else{
            $user = EcsUsers::getUser($user_id,['nickname']);
            $remark['name'] = $user['nickname'];
            $address = EcsUserAddress::getAddressByAddressId($user_id,$address_id);
            $remark['mobile'] = $address['mobile'];
            $remark['address'] = $address['province'].' '.$address['city'].' '.$address['district'].' '.$address['address'];
            $remark = json_encode($remark,JSON_UNESCAPED_UNICODE);
            $apply = new HllEventsApply();
            $apply->events_id = $events_id;
            $apply->user_id = $user_id;
            $apply->remark = $remark;
            $apply->num = 1;
            $apply->pay_status = 2;
            $apply->sign_in_code = $str;
            $apply->valid = 0;
            if($apply->save()){
                $response->data = new ApiData();
                $response->data->info = $apply->id;
            }else{
                $response->data = new ApiData(102,'数据保存失败');
            }
        }
        return $response;
    }

    /**
     * 微信支付参数
     * @return ApiResponse
     */
    public function actionWxPayParams($orderId)
    {
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;

        if (!$orderId) {
            $response->data = new ApiData(110, '参数错误');
            return $response;
        }

        $order_goods = (new Query())->select(['t1.goods_id',"t1.goods_number as order_num","t2.goods_number as goods_num"])
            ->from('ecs_order_goods as t1')
            ->leftJoin('ecs_goods as t2','t2.goods_id = t1.goods_id')
            ->where(['t1.order_id'=>$orderId,'t2.is_delete'=>0])->one();

        $order_info = EcsOrderInfo::findOne(['order_id'=>$orderId,'pay_status'=>0]);

        if (!$order_info || !$order_goods) {
            $response->data = new ApiData(111, '数据错误');
            return $response;
        }

        if($order_goods['order_num'] > $order_goods['goods_num']){
            $response->data = new ApiData(112, '商品数目错误');
            return $response;
        }

        $order['order_sn'] = $order_info->order_sn;
        $order['order_amount'] = $order_info->order_amount;
        $order['log_id'] = (new Query())->select('log_id')->from('ecs_pay_log')->where(['order_id' => $orderId])->scalar();

        $user = EcsUsers::getUser($user_id, ['t2.openid']);
        $user && $GLOBALS['_SESSION']['openId'] = $user['openid'];

        $wxPay = new WxpayV2();
        $data = $wxPay->get_code($order);

        $response->data = new ApiData();
        $response->data->info = $data;
        return $response;
    }

    /**
     * 订单展示
     * @return ApiResponse
     */
    public function actionShowOrder(){
        $response = new ApiResponse();

        $goods = EcsGoods::find()->select(['goods_name','shop_price', 'goods_number','goods_id'])
            ->where(['goods_name'=>'日本理研FP-31甲醛分析检测仪浓度测试片'])->asArray()->one();
        $response->data = new ApiData();
        $response->data->info = $goods;
        return $response;
    }

    /**
     * 提交订单
     * @return ApiResponse
     * params address_id,goods_amount, order_amount, shipping_fee
     */
    public function actionSubmitOrder(){
        $response = new ApiResponse();
        $data = f_post('data',0);

        $data['payment_id'] = 1;
        $data['shipping_id'] = 1;
        $data['bonus'] = 0;
        $data['integral_money'] = 0;
        $data['bonus_id'] = 0;
        $user_info = [
            'user_id' => Yii::$app->user->id,
            'discount' => 1,
            'parent_id' =>0
        ];

        try{
            $user_cart = EcsCart::findOne(['user_id'=>$user_info['user_id']]);
            if ($user_cart) {
                $user_cart->delete();
            }
            $goods = EcsGoods::find()->select(['goods_id','goods_sn','market_price',
                "(shop_price) as goods_price",'is_real','extension_code','goods_name','is_shipping'])
                ->where(['goods_id'=>$data['goods_id']])->asArray()->one();
            $goods['user_id'] = $user_info['user_id'];
            $goods['goods_number'] = $data['goods_num'];
            $goods['goods_attr'] = '';
            $cart = new EcsCart();
            if($cart->load($goods,'') && $cart->save()){
                $result = EcsOrderInfo::addOrder($data,$user_info);
                $response->data = new ApiData();
                $response->data->info = $result;
            }else{
                throw new Exception('数据错误',101);
            }
        }catch (Exception $e){
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    /**
     * 支付成功后修改报名状态
     * @param $apply_id
     * @return ApiResponse
     */
    public function actionApplySuccess($apply_id){
        $response = new ApiResponse();

        $apply = HllEventsApply::findOne($apply_id);
        $apply->valid = 1;
        $event = HllEvents::findOne($apply->events_id);
        $event->joined_num += 1;

        if($apply->save() && $event->save()){
            $response->data = new ApiData();
        }else{
            $response->data = new ApiData(101,'数据保存错误');
        }
        return $response;
    }

    /**
     * 甲醛分享得红包
     * @return ApiResponse
     */
    public function actionHchoShare(){
        $response = new ApiResponse();

        $bonus = new EcsUserBonus();
        $bonus->bonus_type_id = 8;
        $bonus->user_id = Yii::$app->user->id;
        if($bonus->save()){
            $response->data = new ApiData();
        }else{
            $response->data = new ApiData(101,'数据保存错误');
        }
        return $response;
    }
}