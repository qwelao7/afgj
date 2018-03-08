<?php

namespace common\models\ecs;

use common\models\ecs\EcsComment;
use common\models\ar\system\EcsRegion;
use common\models\ecs\EcsUsers;
use Yii;
use yii\base\Exception;
use yii\db\Query;
use common\models\hll\HllBill;
use common\models\hll\HllUserPointsLog;
/**
 * This is the model class for table "ecs_order_info".
 *
 * @property string $order_id
 * @property string $order_sn
 * @property string $user_id
 * @property integer $order_status
 * @property integer $shipping_status
 * @property integer $pay_status
 * @property string $consignee
 * @property integer $country
 * @property integer $province
 * @property integer $city
 * @property integer $district
 * @property string $address
 * @property string $zipcode
 * @property string $tel
 * @property string $mobile
 * @property string $email
 * @property string $best_time
 * @property string $sign_building
 * @property string $postscript
 * @property integer $shipping_id
 * @property string $shipping_name
 * @property integer $pay_id
 * @property string $pay_name
 * @property string $how_oos
 * @property string $how_surplus
 * @property string $pack_name
 * @property string $card_name
 * @property string $card_message
 * @property string $inv_payee
 * @property string $inv_content
 * @property string $goods_amount
 * @property string $shipping_fee
 * @property string $insure_fee
 * @property string $pay_fee
 * @property string $pack_fee
 * @property string $card_fee
 * @property string $money_paid
 * @property string $surplus
 * @property string $integral
 * @property string $integral_money
 * @property string $bonus
 * @property string $order_amount
 * @property integer $from_ad
 * @property string $referer
 * @property string $add_time
 * @property string $confirm_time
 * @property string $pay_time
 * @property string $shipping_time
 * @property integer $pack_id
 * @property integer $card_id
 * @property string $bonus_id
 * @property string $invoice_no
 * @property string $extension_code
 * @property string $extension_id
 * @property string $to_buyer
 * @property string $pay_note
 * @property integer $agency_id
 * @property string $inv_type
 * @property string $tax
 * @property integer $is_separate
 * @property string $parent_id
 * @property string $discount
 */
class EcsOrderInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_order_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'order_status', 'shipping_status', 'pay_status', 'country', 'province', 'city', 'district', 'shipping_id', 'pay_id', 'integral', 'from_ad', 'add_time', 'confirm_time', 'pay_time', 'shipping_time', 'pack_id', 'card_id', 'bonus_id', 'extension_id', 'agency_id', 'is_separate', 'parent_id'], 'integer'],
            [['goods_amount', 'shipping_fee', 'insure_fee', 'pay_fee', 'pack_fee', 'card_fee', 'money_paid', 'surplus', 'integral_money', 'bonus', 'order_amount', 'tax', 'discount'], 'number'],
            [['agency_id', 'tax'], 'safe'],
            [['order_sn'], 'string', 'max' => 20],
            [['consignee', 'zipcode', 'tel', 'mobile', 'email', 'inv_type'], 'string', 'max' => 60],
            [['address', 'postscript', 'card_message', 'referer', 'invoice_no', 'to_buyer', 'pay_note'], 'string', 'max' => 255],
            [['best_time', 'sign_building', 'shipping_name', 'pay_name', 'how_oos', 'how_surplus', 'pack_name', 'card_name', 'inv_payee', 'inv_content'], 'string', 'max' => 120],
            [['extension_code'], 'string', 'max' => 30],
            [['order_sn'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'order_sn' => 'Order Sn',
            'user_id' => 'User ID',
            'order_status' => 'Order Status',
            'shipping_status' => 'Shipping Status',
            'pay_status' => 'Pay Status',
            'consignee' => 'Consignee',
            'country' => 'Country',
            'province' => 'Province',
            'city' => 'City',
            'district' => 'District',
            'address' => 'Address',
            'zipcode' => 'Zipcode',
            'tel' => 'Tel',
            'mobile' => 'Mobile',
            'email' => 'Email',
            'best_time' => 'Best Time',
            'sign_building' => 'Sign Building',
            'postscript' => 'Postscript',
            'shipping_id' => 'Shipping ID',
            'shipping_name' => 'Shipping Name',
            'pay_id' => 'Pay ID',
            'pay_name' => 'Pay Name',
            'how_oos' => 'How Oos',
            'how_surplus' => 'How Surplus',
            'pack_name' => 'Pack Name',
            'card_name' => 'Card Name',
            'card_message' => 'Card Message',
            'inv_payee' => 'Inv Payee',
            'inv_content' => 'Inv Content',
            'goods_amount' => 'Goods Amount',
            'shipping_fee' => 'Shipping Fee',
            'insure_fee' => 'Insure Fee',
            'pay_fee' => 'Pay Fee',
            'pack_fee' => 'Pack Fee',
            'card_fee' => 'Card Fee',
            'money_paid' => 'Money Paid',
            'surplus' => 'Surplus',
            'integral' => 'Integral',
            'integral_money' => 'Integral Money',
            'bonus' => 'Bonus',
            'order_amount' => 'Order Amount',
            'from_ad' => 'From Ad',
            'referer' => 'Referer',
            'add_time' => 'Add Time',
            'confirm_time' => 'Confirm Time',
            'pay_time' => 'Pay Time',
            'shipping_time' => 'Shipping Time',
            'pack_id' => 'Pack ID',
            'card_id' => 'Card ID',
            'bonus_id' => 'Bonus ID',
            'invoice_no' => 'Invoice No',
            'extension_code' => 'Extension Code',
            'extension_id' => 'Extension ID',
            'to_buyer' => 'To Buyer',
            'pay_note' => 'Pay Note',
            'agency_id' => 'Agency ID',
            'inv_type' => 'Inv Type',
            'tax' => 'Tax',
            'is_separate' => 'Is Separate',
            'parent_id' => 'Parent ID',
            'discount' => 'Discount',
        ];
    }

    //获取订单数据
    public static function getOrdersByUser($id,$type){
        $fields = ['t1.order_id','t1.order_status','t1.shipping_status','t1.pay_status','t1.order_amount','t1.money_paid',
            't2.goods_name','t2.goods_number','t2.goods_price','t3.goods_brief','t3.goods_thumb',"(t4.name) as brand_name"];
        $sql = (new Query())->select($fields)->from('ecs_order_info as t1')
            ->leftJoin('ecs_order_goods as t2','t2.order_id = t1.order_id')
            ->leftJoin('ecs_goods as t3','t3.goods_id = t2.goods_id')
            ->leftJoin('hll_business as t4','t4.id = t3.business_id')
            ->where(['t1.user_id'=>$id]);
        if($type == 1){
            $sql->orderBy(['t1.add_time'=>SORT_DESC]);
            return $sql;
        }
        else{
            if($type == 2){
                $sql->andWhere(['t1.order_status'=>0,'t1.shipping_status'=>0,'t1.pay_status'=>0]);
            }
            if($type == 3){
                $sql->andWhere(['t1.order_status'=>[0,1],'t1.shipping_status'=>[0,1],'t1.pay_status'=>2]);
            }
            if($type == 4){
                $sql->andWhere(['t1.order_status'=>1,'t1.shipping_status'=>2,'t1.pay_status'=>2]);
            }
            $sql->orderBy(['t1.add_time'=>SORT_DESC]);
            return $sql;
        }
    }

    /**
     * @param $id
     * 获取订单详情
     * @return array|bool
     */
    public static function getOrderDetail($id, $fields = null){
        if ($fields == null) {
            $fields = ['t1.order_id', 't1.order_status','t1.shipping_status','t1.order_sn','t1.to_buyer','t1.goods_amount',
                't1.shipping_name','t1.pay_status', 't1.consignee', 't1.mobile', 't1.address','t1.order_amount','t1.money_paid',
                't1.add_time','t1.sign_building','t1.shipping_fee','t1.integral_money','t1.discount','t1.bonus','t1.invoice_no',
                't2.rec_id','t2.goods_name', 't2.goods_number', 't2.goods_price', 't2.goods_attr',
                't3.goods_brief','t3.shop_price', 't3.goods_thumb','t4.hot_mobile',"(t4.name) as brand_name"];
        }
        $info = (new Query())->select($fields)->from('ecs_order_info as t1')
            ->leftJoin('ecs_order_goods as t2', 't2.order_id = t1.order_id')
            ->leftJoin('ecs_goods as t3', 't3.goods_id = t2.goods_id')
            ->leftJoin('hll_business as t4','t4.id = t3.business_id')
            ->where(['t1.order_id' => $id])->one();
        $address = EcsOrderInfo::getOrderAddress($id);
        if($info['shipping_name'] == ''){
            $info['shipping_name'] = '自取';
        }
        $info['add_time'] = date('Y-m-d H:i:s', $info['add_time']);
        $info['address'] = trim($address.' '.$info['sign_building'].' '.$info['address']);
        $info['discount'] = $info['shop_price'] * (100 - $info['discount'] * 100) * $info['goods_number'] / 100;
        $info['discount'] = number_format($info['discount'], 2);
        $info['log_id'] = (new Query())->select(['log_id'])->from('ecs_pay_log')->where(['order_id'=>$id,'order_type'=>0,'is_paid'=>0])->scalar();
        return $info;
    }

    //获取订单地址
    public static function getOrderAddress($id){
        $address_num = EcsOrderInfo::find()->select(['province','city','district'])
            ->where(['order_id'=>$id])->asArray()->one();
        if($address_num['province'] == 0 || $address_num['city'] == 0 || $address_num['district'] == 0){
            return '自取';
        }else{
            $province = EcsUserAddress::getProvinceAndCity($address_num['province']);
            $city = EcsUserAddress::getProvinceAndCity($address_num['city']);
            $district = EcsUserAddress::getProvinceAndCity($address_num['district']);
            $address = $province.'省'.$city.'市'.$district;
            return $address;
        }
    }

    //客户订单操作
    //type 2:收货 3:评价 4:取消
    public static function orderOperation($id,$type,$rank=null,$content=null){
        $data = EcsOrderInfo::findOne($id);
        if(!$data){
            return false;
        }
        if($type == 2){
            $tran = Yii::$app->db->beginTransaction();
            try{
                $data->shipping_status = 2;
                $business_id = (new Query())->select(['t2.business_id'])->from('ecs_order_goods as t1')
                    ->leftJoin('ecs_goods as t2','t2.goods_id = t1.goods_id')
                    ->where(['order_id'=>$data->order_id])->scalar();
                if(intval($business_id != 0)){
                    $db = Yii::$app->db;
                    $sql = 'update hll_seller_money set frozen_money = frozen_money - '.$data->money_paid.', seller_money = seller_money + '.$data->money_paid.' where valid = 1 and seller_id = '.$business_id;
                    $db->createCommand($sql)->execute();
                    $query = 'select frozen_money, seller_money from hll_seller_money where valid = 1 and seller_id = '.$business_id;
                    $seller = $db->createCommand($query)->queryOne();
                    $frozen_log = [
                        'seller_id'=>$business_id,
                        'money'=>$data->money_paid,
                        'money_after'=>$seller['frozen_money'],
                        'money_way'=>2,
                        'money_type'=>1,
                        'money_reason'=>'订单确认 转为可用金钱',
                        'related_id'=>$data->order_sn
                    ];
                    $db->createCommand()->insert('hll_seller_money_log',$frozen_log)->execute();
                    $seller_log = [
                        'seller_id'=>$business_id,
                        'money'=>$data->money_paid,
                        'money_after'=>$seller['seller_money'],
                        'money_way'=>1,
                        'money_type'=>2,
                        'money_reason'=>'冻结余额转入',
                        'related_id'=>$data->order_sn
                    ];
                    $db->createCommand()->insert('hll_seller_money_log',$seller_log)->execute();
                }
                $tran->commit();
            }catch (Exception $e){
                $tran->rollBack();
                return false;
            }
        }else if($type == 3){
            $data->order_status = 5;
            $good = EcsOrderGoods::find()->select(['goods_id'])->where(['order_id'=>$data->order_id])->one();
            $user = EcsUsers::getUser($data->user_id);
            $model = new EcsComment();
            $model->user_id = $data->user_id;
            $model->order_id = $data->order_id;
            $model->id_value = $good->goods_id;
            $model->email = $user['email'];
            $model->user_name = $user['user_name'];
            $model->comment_rank = $rank;
            $model->content = $content;
            $model->ip_address = f_real_ip();
            $model->add_time = time();
            if($model->save() && $data->save()){
                return $data->order_id;
            }else{
                return false;
            }
        }else if($type == 4){
            $data->order_status = 2;
        }
        if($data->save()){
            $order_action =new EcsOrderAction();
            $order_action->order_id = $id;
            $order_action->action_user = '买家';
            $order_action->order_status = $data->order_status;
            $order_action->pay_status = $data->pay_status;
            $order_action->shipping_status = $data->shipping_status;
            $order_action->log_time = time();
            if($order_action->save()){
                return $data->order_id;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 添加订单详情
     * @param $data
     * @param $user_info
     * @return string
     * @throws Exception
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public static function addOrder($data,$user_info){
        $shipping_name = (new Query())->select(['shipping_name'])->from('ecs_shipping')->where(['shipping_id'=>$data['shipping_id'],'enabled'=>1])->scalar();
        $payment_name = (new Query())->select(['pay_name'])->from('ecs_payment')->where(['pay_id'=>$data['payment_id'],'enabled'=>1])->scalar();
        $address_info = EcsUserAddress::findOne($data['address_id']);
        $user = EcsUsers::getUser($user_info['user_id'], ['t2.openid']);
        $order_info = [
            'order_sn' => date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'user_id' => $user_info['user_id'],
            'consignee' => $address_info->consignee,
            'country' => $address_info->country,
            'province' => $address_info->province,
            'city' => $address_info->city,
            'district' => $address_info->district,
            'address' => $address_info->address,
            'mobile' => $address_info->mobile,
            'sign_building' => $address_info->sign_building,
            'shipping_id' => $data['shipping_id'],
            'shipping_name' => $shipping_name,
            'pay_id' => $data['payment_id'],
            'pay_name' => $payment_name,
            'goods_amount' => $data['shop_price'] * $data['goods_num'],
            'bonus' => $data['bonus'],
            'order_amount' => $data['order_amount'],
            'shipping_fee' => $data['shipping_fee'],
            'integral' => intval($data['integral_money'] * 100),
            'integral_money' => $data['integral_money'],
            'add_time' => time(),
            'bonus_id' => $data['bonus_id'],
            'parent_id' => $user_info['parent_id'],
            'discount' => $user_info['discount'],
            'seller_id' => $data['business_id'],
        ];
        $trans = Yii::$app->db->beginTransaction();
        try{
            $order = new EcsOrderInfo();
            if($order->load($order_info,'') && $order->save()){
                $result = EcsOrderGoods::addOrderGoods($user_info['user_id'],$order->order_id);
                $pay_log = new EcsPayLog();
                $pay_log->order_id = $order->order_id;
                $pay_log->openid = $user['openid'];
                $pay_log->order_amount = $data['order_amount'];
                $pay_log->order_type = 0;
                $pay_log->is_paid = 0;
                $pay_log->add_time = time();
                $bill = new HllBill();
                $bill->title = $result;
                $bill->user_id = $user_info['user_id'];
                $bill->bill_sn = $order->order_id;
                $bill->bill_category = 1;
                $bill->pay_id = 1;
                $bill->pay_name = '微信支付';
                $bill->bonus = $data['bonus'];
                $bill->bonus_id = $data['bonus_id'];
                $bill->point = intval($data['integral_money'] * 100);
                $bill->point_money = $data['integral_money'];
                $bill->bill_amount = $data['shop_price'] * $data['goods_num'];
                $bill->discount = $user_info['discount'];
                $bill->freight = $data['shipping_fee'];
                $bill->money_paid = $data['order_amount'];
                if($result && $pay_log->save() && $bill->save()){
                    $type = intval($data['order_amount']) == 0 ? 2 : 1;
                    if($data['integral_money'] > 0){
                        HllUserPointsLog::shippingExpend($order->order_id,$user_info['user_id'],$data['goods_id'],$bill->point,$type);
                    }
                    $trans->commit();
                    return $order->order_id;
                }else{
                    throw new Exception('保存订单商品失败', 103);
                }
            }else{
                throw new Exception($order->getFirstErrors(), 102);
            }
        }catch (Exception $e){
            $trans->rollBack();
            throw new Exception($e->getMessage(),101);
        }
    }
}
