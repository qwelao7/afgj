<?php

namespace mobile\modules\v2\controllers;

use common\components\WxpayV2;
use common\models\ecs\EcsCart;
use common\models\ecs\EcsComment;
use common\models\ecs\EcsGoods;
use common\models\ecs\EcsOrderGoods;
use common\models\ecs\EcsOrderInfo;
use common\models\ecs\EcsOrderReturn;
use common\models\ecs\EcsPayLog;
use common\models\ecs\EcsReturnAction;
use common\models\ecs\EcsUserAddress;
use common\models\ecs\EcsUserBonus;
use common\models\hll\HllBill;
use common\models\hll\HllLxhealthyTemp;
use common\models\hll\HllUserPoints;
use common\models\hll\HllUserPointsLog;
use Yii;
use mobile\components\ApiController;
use yii\base\Exception;
use yii\db\Query;
use yii\filters\HttpCache;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\data\ActiveDataProvider;
use common\models\ecs\EcsUsers;

/**
 * 订单接口控制器
 * @package api\modules\v1\controllers
 */
class OrderController extends ApiController
{

    public function actionIndex()
    {
        return 'success';
    }

    public function actionLogin()
    {
        return 'login';
    }

    /**
     * 获取订单列表
     * type 1:全部 2:待付款 3:待收货 4:待评价
     * */
    public function actionList()
    {
        $response = new ApiResponse();
        $page = Yii::$app->request->get('page');
        $type = Yii::$app->request->get('type');
        $userId = Yii::$app->user->id;
        if (empty($page) || empty($type)) {
            $response->data = new ApiData('101', '缺少关键数据！');
            return $response;
        }
        $sql = EcsOrderInfo::getOrdersByUser($userId, $type);
        //对数据进行分页处理
        $info = $this->getDataPage($sql, $page);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 获取订单详情
     * type 1: 未付款 2:未发货 3:已发货 4:未评价 5:已完成 6:已取消 7:售后服务中
     * @param $id
     * @return ApiResponse
     */
    public function actionDetail($id)
    {
        $response = new ApiResponse();
        if (empty($id)) {
            $response->data = new ApiData('101', '缺少关键数据！');
            return $response;
        }
        $data = EcsOrderInfo::getOrderDetail($id);
        $response->data = new ApiData();
        $response->data->info = $data;
        return $response;
    }

    /**
     * type 2:收货 3:评价 4:取消
     * @param $id
     * @param $type
     * @return ApiResponse
     */
    public function actionOperation()
    {
        $response = new ApiResponse();
        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');
        if (empty($id) || empty($type)) {
            $response->data = new ApiData('101', '缺少关键数据！');
            return $response;
        }
        if ($type == 3) {
            $content = Yii::$app->request->post('content');
            $rank = Yii::$app->request->post('rank');
            $data = EcsOrderInfo::orderOperation($id, $type, $rank, $content);
        } else {
            $data = EcsOrderInfo::orderOperation($id, $type);
        }
        if ($data) {
            $response->data = new ApiData();
            $response->data->info = $data;
        } else {
            $response->data = new ApiData('110', '操作失败！');
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

        $order_goods = (new Query())->select(['t1.goods_id','t1.goods_attr_id',"t1.goods_number as order_num","t2.goods_number as goods_num"])
            ->from('ecs_order_goods as t1')
            ->leftJoin('ecs_goods as t2','t2.goods_id = t1.goods_id')
            ->where(['t1.order_id'=>$orderId,'t2.is_delete'=>0])->one();

        $order_info = EcsOrderInfo::findOne(['order_id'=>$orderId,'pay_status'=>0]);

        if (!$order_info || !$order_goods) {
            $response->data = new ApiData(111, '数据错误');
            return $response;
        }

//        if($order_info->integral > 0){
//            HllUserPointsLog::shippingExpend($orderId,$user_id,$order_goods['goods_id'],$order_info->integral);
//        }

        if($order_info->bonus_id != 0){
            $bonus = EcsUserBonus::findOne(['bonus_id'=>$order_info->bonus_id,'order_id'=>0]);
            if(!$bonus){
                $response->data = new ApiData(113, '红包数据错误');
                return $response;
            }
        }
        if($order_info->order_amount == 0){
            $trans = Yii::$app->db->beginTransaction();
            try{
                if($order_info->bonus_id != 0 ){
                    $bonus->order_id = $order_info->order_id;
                    if(!$bonus->save()){
                        throw new Exception($bonus->getFirstErrors(),'114');
                    }
                }
                if($order_goods['goods_attr_id']){
                    $db = Yii::$app->db;
                    $sql = 'update ecs_goods_attr set attr_number = attr_number -'.$order_goods['order_num'].' where goods_attr_id ='.$order_goods['goods_attr_id'];
                    $db->createCommand($sql)->execute();
                }
                $pay_log = EcsPayLog::findOne(['order_id' => $orderId]);
                $pay_log->is_paid = 1;
                $pay_log->add_time = time();
                $order_info->pay_status = 2;
                $order_info->order_status = 0;
                $bill = HllBill::findOne(['bill_sn'=>$orderId,'bill_category'=>1]);
                $bill->pay_status = 2;
                $bill->pay_time = date("Y-m-d H:i:s");
                $goods = EcsGoods::findOne($order_goods['goods_id']);
                $goods->goods_number -= $order_goods['order_num'];
                $goods->save();
                if($pay_log->save() && $order_info->save() && $bill->save()){
                    $trans->commit();
                    $response->data = new ApiData(200, '支付成功');
                }else{
                    throw new Exception($pay_log->getFirstErrors(),'115');
                }
            }catch (Exception $e){
                $trans->rollBack();
                $response->data = new ApiData($e->getCode(),$e->getMessage());
            }
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
     * 售后服务操作记录
     * @param $orderId
     * @return ApiResponse
     */
    public function actionAfterSaleRecords($orderId)
    {
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;

        if (!$orderId) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $retId = EcsOrderReturn::find()->select(['ret_id'])->where(['order_id' => $orderId, 'user_id' => $userId])->scalar();

        $data = EcsReturnAction::returnLogsByOrder($orderId);

        if (!$data) {
            $response->data = new ApiData(101, '数据错误');
        } else {
            $response->data = new ApiData();
            $result['list'] = $data;
            $result['cur'] = $userId;
            $result['ret_id'] = $retId;
            $response->data->info = $result;
        }

        return $response;
    }

    public function actionAddRecord()
    {
        $data = Yii::$app->request->post('data');
        $response = new ApiResponse();

        if (!$data || empty($data)) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $model = new EcsReturnAction();
        if ($model->load($data, '')) {
            $model->action_user_type = 2;
            $model->action_user = '买家';
            $model->log_time = (string)time();
            if ($model->validate() && $model->save()) {
                $headimgurl = EcsUsers::getUser($data['action_user_id'], ['t2.headimgurl']);
                $headimgurl = array_values($headimgurl);
                $result['time'] = date('m-d H:i', time());
                $result['headimgurl'] = $headimgurl;

                $response->data = new ApiData();
                $response->data->info = $result;
            } else {
                var_dump($model->getErrors());
                $response->data = new ApiData(102, '保存失败');
            }
        } else {
            $response->data = new ApiData(101, '数据错误');
        }

        return $response;
    }

    /**
     * 获取红包列表
     * @return ApiResponse
     */
    public function actionBonusList(){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $order_money = f_get('order_money',0);
        $goods_id = f_get('goods_id',0);
        $order_money =  is_numeric($order_money) ? $order_money : 0;
        $list = EcsUserBonus::getBonusByUserId($user_id,intval($goods_id),intval($order_money));
        $response->data = new ApiData();
        $response->data->info['list'] = $list;
        return $response;
    }

    /**
     * 获取收货地址
     * @return ApiResponse
     */
    public function actionAddressList(){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $address_id = f_get('address_id',0);
        $list = EcsUserAddress::getAddressByUserId($user_id,$address_id);
        if (sizeof($list) < 1) {
            $response->data = new ApiData(100, '暂无房产');
        } else {
            $response->data = new ApiData();
            $response->data->info['address'] = $list;
        }
        return $response;
    }

    /**
     * 获取支付和配送方式
     * @return ApiResponse
     */
    public function actionPaymentShipping(){
        $response = new ApiResponse();
        $address_id = f_get('address_id',0);
        $goods_id = f_get('goods_id',0);
        $goods_money = f_get('goods_money',0);
        $goods_num = f_get('goods_num',1);
        $info = EcsUserAddress::getShippingInfo($address_id,$goods_id,$goods_money,$goods_num);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 结算页面
     * @return ApiResponse
     */
    public function actionPlaceOrder(){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $address_id = (int)f_get('address_id',0);
        $bonus_id = (int)f_get('bonus_id',0);
        $shipping_id = f_get('shipping_id','');
        $payment_id = f_get('payment_id','');

        //商品信息
        $info['goods'] = EcsOrderGoods::getOrderGoods($user_id);
        if(!$info['goods']){
            $response->data = new ApiData(101,'无相关数据');
            return $response;
        }

        //支付信息
        $info['payment'] = (new Query())->select(['pay_id','pay_name'])
            ->from('ecs_payment')->where(['enabled'=>1])
            ->andFilterWhere(['pay_id'=>$payment_id])
            ->orderBy('pay_id ASC')->one();

        //快递信息
        if(!$shipping_id){
            $shipping_id = EcsUserAddress::getShippingByGoods($info['goods']['goods_id']);
        }
        $info['shipping'] = (new Query())->select(['shipping_id','shipping_name'])
            ->from('ecs_shipping')
            ->where(['enabled'=>1])
            ->andFilterWhere(['shipping_id'=>$shipping_id])
            ->orderBy('shipping_id ASC')
            ->one();

        //地址信息
        $info['address'] = EcsUserAddress::getAddressByAddressId($user_id,$address_id);
        if(!$address_id && $info['address']){
            $address_id = (int)$info['address']['address_id'];
        }

        if($address_id>0) {

            $region_id = (new Query())->select(['country','province','city','district'])
                ->from('ecs_user_address')->where(['address_id'=>$address_id])->one();

            $shipping_area_id = (new Query())->select(['t1.shipping_area_id'])
                ->from('ecs_shipping_area as t1')
                ->leftJoin('ecs_shipping as t2','t2.shipping_id = t1.shipping_id')
                ->leftJoin('ecs_area_region as t3','t3.shipping_area_id = t1.shipping_area_id')
                ->where(['t2.enabled'=>1,'t1.shipping_id'=>$info['shipping']['shipping_id'],'t3.region_id'=>$region_id])
                ->orderBy('t3.region_id DESC')
                ->scalar();

            if($shipping_area_id) {
                $info['shipping']['shipping_fee'] = EcsUserAddress::getShippingFee($shipping_area_id,$info['goods']['goods_id'],$info['goods']['order_money'],$info['goods']['goods_number']);
            }else {
                $response->data = new ApiData(101,'很抱歉，当前区域不支持配送');
                return $response;
            }
        } else { //没有配送地址时，运费显示为0
            $info['shipping']['shipping_fee']=0;
        }
        //红包信息
        if($bonus_id){
            $info['bonus'] = EcsUserBonus::getBonusByUserId($user_id,intval($info['goods']['goods_id']),intval($info['goods']['order_money']),intval($bonus_id),['t1.bonus_id','t2.type_money']);
        }else{
            $info['bonus'] = EcsUserBonus::getBonusNumUserId($user_id,intval($info['goods']['goods_id']),intval($info['goods']['order_money']));
        }

        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 提交订单
     * @return ApiResponse
     * params address_id, shipping_id, payment_id, goods_amount, order_amount, shipping_fee, integral_money, bonus
     * bonus_id
     */
    public function actionSubmitOrder(){
        $response = new ApiResponse();
        $data = f_post('data',0);

        $user_info = parent::$sessionInfo;

        try{
            //校验商品数目
            $goods_info = (new Query())->select(['goods_number','goods_attr_id','goods_id'])->from('ecs_cart')
                ->where(['user_id'=>$user_info['user_id']])->one();
            if($goods_info['goods_attr_id']){
                $goods_number = (new Query())->select(['attr_number'])->from('ecs_goods_attr')
                    ->where(['goods_attr_id'=>$goods_info['goods_attr_id']])->scalar();
            }else{
                $goods_number = EcsGoods::find()->select(['goods_number'])
                    ->where(['goods_id'=>$goods_info['goods_id']])->scalar();
            }
            if($goods_info['goods_number'] > $goods_number){
                throw new Exception('商品数目不足',101);
            }
            $data['goods_id'] = $goods_info['goods_id'];
            $result = EcsOrderInfo::addOrder($data,$user_info);
            $response->data = new ApiData();
            $response->data->info = $result;
        }catch (Exception $e){
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    /**
     * 清空购物车
     * @return ApiResponse
     * @throws \Exception
     * @throws \Throwable
     */
    public function actionClearCart(){
        $response = new ApiResponse();
        $user_cart = EcsCart::findOne(['user_id'=>Yii::$app->user->id]);
        if ($user_cart) {
            $user_cart->delete();
        }
        $response->data = new ApiData();
        return $response;
    }

    /**
     * 账单列表
     * @return ApiResponse
     */
    public function actionPaymentList(){
        $response = new ApiResponse();
        $page = f_get('page',1);
        $user_id = Yii::$app->user->id;
        $query = HllBill::getBillListByUser($user_id);
        $info = $this->getDataPage($query,$page);
        if($info['list']){
            foreach($info['list'] as &$item){
                if(strtolower(substr($item['goods_thumb'], 0, 4)) == 'data'){
                    $item['goods_thumb'] = 'http://mall.afguanjia.com/'.$item['goods_thumb'];
                }else if(strtolower(substr($item['goods_thumb'], 0, 4)) == 'http'){
                    continue;
                }else{
                    $item['goods_thumb'] = 'http://pub.huilaila.net/'.$item['goods_thumb'];
                }
            }
        }
        $info['total'] = EcsPayLog::getPayTotal($user_id);
        $info['now_time'] = date("Ym",time());
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }
}

