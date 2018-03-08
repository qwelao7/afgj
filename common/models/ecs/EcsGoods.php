<?php

namespace common\models\ecs;

use yii\base\Exception;
use Yii;
use yii\db\Query;

/**
 * This is the model class for table "ecs_goods".
 *
 * @property string $goods_id
 * @property integer $cat_id
 * @property string $goods_sn
 * @property string $goods_name
 * @property string $goods_name_style
 * @property string $click_count
 * @property integer $brand_id
 * @property string $provider_name
 * @property integer $goods_number
 * @property string $goods_weight
 * @property string $market_price
 * @property string $virtual_sales
 * @property string $shop_price
 * @property string $promote_price
 * @property string $promote_start_date
 * @property string $promote_end_date
 * @property integer $warn_number
 * @property string $keywords
 * @property string $goods_brief
 * @property string $goods_desc
 * @property string $goods_thumb
 * @property string $goods_img
 * @property string $original_img
 * @property integer $is_real
 * @property string $extension_code
 * @property integer $is_on_sale
 * @property integer $is_alone_sale
 * @property integer $is_shipping
 * @property string $integral
 * @property string $add_time
 * @property integer $sort_order
 * @property integer $is_delete
 * @property integer $is_best
 * @property integer $is_new
 * @property integer $is_hot
 * @property integer $is_promote
 * @property integer $bonus_type_id
 * @property string $last_update
 * @property integer $goods_type
 * @property string $seller_note
 * @property integer $give_integral
 * @property integer $rank_integral
 * @property integer $suppliers_id
 * @property integer $is_check
 */
class EcsGoods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_goods';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cat_id', 'click_count', 'brand_id', 'goods_number', 'promote_start_date', 'promote_end_date', 'warn_number', 'is_real', 'is_on_sale', 'is_alone_sale', 'is_shipping', 'integral', 'add_time', 'sort_order', 'is_delete', 'is_best', 'is_new', 'is_hot', 'is_promote', 'bonus_type_id', 'last_update', 'goods_type', 'give_integral', 'rank_integral', 'suppliers_id', 'is_check'], 'integer'],
            [['goods_weight', 'market_price', 'shop_price', 'promote_price'], 'number'],
            [['virtual_sales', 'goods_desc'], 'safe'],
            [['goods_desc'], 'string'],
            [['goods_sn', 'goods_name_style'], 'string', 'max' => 60],
            [['goods_name'], 'string', 'max' => 120],
            [['provider_name'], 'string', 'max' => 100],
            [['virtual_sales'], 'string', 'max' => 10],
            [['keywords', 'goods_brief', 'goods_thumb', 'goods_img', 'original_img', 'seller_note'], 'string', 'max' => 255],
            [['extension_code'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'goods_id' => 'Goods ID',
            'cat_id' => 'Cat ID',
            'goods_sn' => 'Goods Sn',
            'goods_name' => 'Goods Name',
            'goods_name_style' => 'Goods Name Style',
            'click_count' => 'Click Count',
            'brand_id' => 'Brand ID',
            'provider_name' => 'Provider Name',
            'goods_number' => 'Goods Number',
            'goods_weight' => 'Goods Weight',
            'market_price' => 'Market Price',
            'virtual_sales' => 'Virtual Sales',
            'shop_price' => 'Shop Price',
            'promote_price' => 'Promote Price',
            'promote_start_date' => 'Promote Start Date',
            'promote_end_date' => 'Promote End Date',
            'warn_number' => 'Warn Number',
            'keywords' => 'Keywords',
            'goods_brief' => 'Goods Brief',
            'goods_desc' => 'Goods Desc',
            'goods_thumb' => 'Goods Thumb',
            'goods_img' => 'Goods Img',
            'original_img' => 'Original Img',
            'is_real' => 'Is Real',
            'extension_code' => 'Extension Code',
            'is_on_sale' => 'Is On Sale',
            'is_alone_sale' => 'Is Alone Sale',
            'is_shipping' => 'Is Shipping',
            'integral' => 'Integral',
            'add_time' => 'Add Time',
            'sort_order' => 'Sort Order',
            'is_delete' => 'Is Delete',
            'is_best' => 'Is Best',
            'is_new' => 'Is New',
            'is_hot' => 'Is Hot',
            'is_promote' => 'Is Promote',
            'bonus_type_id' => 'Bonus Type ID',
            'last_update' => 'Last Update',
            'goods_type' => 'Goods Type',
            'seller_note' => 'Seller Note',
            'give_integral' => 'Give Integral',
            'rank_integral' => 'Rank Integral',
            'suppliers_id' => 'Suppliers ID',
            'is_check' => 'Is Check',
        ];
    }
    //根据商家id获取商品列表
    public static function getGoodsListByBusinessId($id){
        $fields = ['goods_id','goods_name','goods_number','market_price','shop_price','is_real','goods_thumb','goods_sn'];
        $goods_list = (new Query())->select($fields)->from('ecs_goods')->where(['business_id'=>$id,'is_delete'=>0])->all();
        $data = [];
        foreach($goods_list as $item){
            $shop_price = $item['shop_price'];
            if(substr($item['goods_thumb'],0,4) == 'data'){
                $item['goods_thumb'] = 'http://mall.afguanjia.com/'.$item['goods_thumb'];
            }else{
                $item['goods_thumb'] = 'http://pub.huilaila.net/'.$item['goods_thumb'];
            }
            $goods_attr = (new Query())->select(['goods_attr_id','attr_value','attr_price','attr_number'])
                ->from('ecs_goods_attr')->where(['goods_id'=>$item['goods_id']])->all();
            if($goods_attr){
                foreach($goods_attr as $val){
                    $item['goods_number'] = $val['attr_number'];
                    $item['goods_attr_id'] = $val['goods_attr_id'];
                    $item['attr_value'] = $val['attr_value'];
                    $item['shop_price'] = $shop_price + $val['attr_price'];
                    $sale_num = (new Query())->select(["sum(t1.goods_number) as number"])->from('ecs_order_goods as t1')
                        ->leftJoin('ecs_order_info as t2','t2.order_id = t1.order_id')
                        ->where(['t1.goods_attr_id'=>$item['goods_attr_id'],'t2.order_status'=>[1,5,7],'t2.pay_status'=>2])
                        ->scalar();
                    $item['sale_num'] = $sale_num == null ? 0 : $sale_num;
                    array_push($data,$item);
                }
            }else{
                $item['goods_attr_id'] = 0;
                $item['attr_value'] = '';
                $sale_num = (new Query())->select(["sum(t1.goods_number) as number"])->from('ecs_order_goods as t1')
                    ->leftJoin('ecs_order_info as t2','t2.order_id = t1.order_id')
                    ->where(['t1.goods_id'=>$item['goods_id'],'t2.order_status'=>[0,1,5,7],'t2.pay_status'=>2])
                    ->scalar();
                $item['sale_num'] = $sale_num == null ? 0 : $sale_num;
                array_push($data,$item);
            }
        }
        return $data;
    }

    //线下消费，收银台页面，添加商品信息
    public static function addOrderGoods($data){
        $user = EcsUsers::getUser($data['user_id'], ['t2.nickname']);
        try{
            $order_info = [
                'order_sn' => date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
                'user_id' => $data['user_id'],
                'consignee' => $user['nickname'],
                'goods_amount' => $data['money'],
                'integral' => $data['money'] * 100,
                'integral_money' => $data['money'],
                'add_time' => time(),
                'pay_time' => time(),
                'pay_status' => 2,
                'order_status' => 5,
                'shipping_status' => 2,
                'discount' => $data['discount'],
                'seller_id' => $data['business_id']
            ];
            $order = new EcsOrderInfo();
            if($order->load($order_info,'') && $order->save()){
                foreach($data['list'] as $item){
                    $goods_info = (new Query())->select(['goods_id','goods_sn','goods_name','is_real',
                        'market_price','extension_code','goods_number',"(shop_price) as goods_price"])
                        ->from('ecs_goods')->where(['goods_id'=>$item['goods_id']])->one();
                    $goods_info['goods_attr'] = '';
                    if(intval($item['goods_attr_id']) != 0){
                        $goods_attr = (new Query())->select(['attr_value','attr_price','attr_number'])
                            ->from('ecs_goods_attr')->where(['goods_attr_id'=>$item['goods_attr_id']])->one();
                        $goods_info['goods_price'] += $goods_attr['attr_price'];
                        $goods_info['goods_attr'] = $goods_attr['attr_value'];
                        $goods_info['goods_number'] = $goods_attr['attr_number'];
                        $goods_info['goods_attr_id'] = $item['goods_attr_id'];
                    }
                    $goods_info['order_id'] = $order->order_id;
                    if(intval($goods_info['goods_number']) >= intval($item['current_num'])){
                        $goods_num = $goods_info['goods_number'];
                        $goods_info['goods_number'] = $item['current_num'];
                        $goods = new EcsOrderGoods();
                        if($goods->load($goods_info,'') && $goods->save()){
                            if(intval($item['goods_attr_id']) != 0){
                                Yii::$app->db ->createCommand()->update('ecs_goods_attr',['attr_number'=>$goods_num - $item['current_num']],"goods_attr_id ={$item['goods_attr_id']}") ->execute();
                            }else{
                                $goods_info = EcsGoods::findOne($item['goods_id']);
                                $goods_info->goods_number -= $item['current_num'];
                                $goods_info->save(false);
                            }
                            continue;
                        }else{
                            throw new Exception('添加商品失败！',101);
                        }
                    }else{
                        throw new Exception('商品数额不足，',102);
                    }
                }
            }else{
                throw new Exception('添加订单详情失败！',103);
            }
            return true;
        }catch (Exception $e){
            Yii::warning('收银台页面，添加商品信息:'.$e->getMessage());
            throw new Exception($e->getMessage(),$e->getCode());
        }
    }
}
