<?php

namespace common\models\ecs;

use Yii;
use yii\base\Exception;
use yii\db\Query;
use common\models\hll\HllUserPoints;
/**
 * This is the model class for table "ecs_order_goods".
 *
 * @property string $rec_id
 * @property string $order_id
 * @property string $goods_id
 * @property string $goods_name
 * @property string $goods_sn
 * @property string $product_id
 * @property integer $goods_number
 * @property string $market_price
 * @property string $goods_price
 * @property string $goods_attr
 * @property integer $send_number
 * @property integer $is_real
 * @property string $extension_code
 * @property string $parent_id
 * @property integer $is_gift
 * @property string $goods_attr_id
 */
class EcsOrderGoods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_order_goods';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'goods_id', 'product_id', 'goods_number', 'send_number', 'is_real', 'parent_id', 'is_gift'], 'integer'],
            [['market_price', 'goods_price'], 'number'],
            [['goods_attr'], 'safe'],
            [['goods_attr'], 'string'],
            [['goods_name'], 'string', 'max' => 120],
            [['goods_sn'], 'string', 'max' => 60],
            [['extension_code'], 'string', 'max' => 30],
            [['goods_attr_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rec_id' => 'Rec ID',
            'order_id' => 'Order ID',
            'goods_id' => 'Goods ID',
            'goods_name' => 'Goods Name',
            'goods_sn' => 'Goods Sn',
            'product_id' => 'Product ID',
            'goods_number' => 'Goods Number',
            'market_price' => 'Market Price',
            'goods_price' => 'Goods Price',
            'goods_attr' => 'Goods Attr',
            'send_number' => 'Send Number',
            'is_real' => 'Is Real',
            'extension_code' => 'Extension Code',
            'parent_id' => 'Parent ID',
            'is_gift' => 'Is Gift',
            'goods_attr_id' => 'Goods Attr ID',
        ];
    }

    /**
     * 获取订单商品
     * @param $user_id
     * @return array|bool
     */
    public static function getOrderGoods($user_id){
        $fields = ['t1.goods_id','t1.goods_name','t1.goods_price','t1.goods_number', 't1.is_real','t1.goods_attr',
        't2.business_id', 't2.goods_brief','t2.goods_thumb',"(t3.name) as brand_name",'t2.integral','t2.shop_price',
            "(t3.logo) as brand_logo","(t1.goods_price * t1.goods_number) as order_money"];
        $order_goods = (new Query())->select($fields)->from('ecs_cart as t1')
            ->leftJoin('ecs_goods as t2','t2.goods_id = t1.goods_id')
            ->leftJoin('hll_business as t3','t3.id = t2.business_id')
            ->where(['t1.user_id'=>$user_id,'t2.is_delete'=>0])->one();
        if(!$order_goods){
            return [];
        }
        if($order_goods['business_id'] == 0){
            $order_goods['point'] = 0;
            $order_goods['integral'] = 0;
        }else{
            $community = HllUserPoints::getCommunityByBusiness($order_goods['business_id']);
            $user_points = HllUserPoints::getUserPoints($user_id, $community, 1);
            $user_points = $user_points > $order_goods['integral'] ? $order_goods['integral'] : $user_points;
            $user_points = $user_points > $order_goods['order_money']*100 ? $order_goods['order_money']*100 : $user_points;
            $order_goods['point'] = $user_points;
            $order_goods['integral'] = $user_points / 100;
        }
        return $order_goods;
    }

    /**
     * 添加订单商品
     * @param $user_id
     * @throws \Exception
     */
    public static function addOrderGoods($user_id,$order_id){
        $fields = ['t1.goods_id','t1.goods_sn','t1.goods_name','t1.goods_number',
            't1.market_price','t1.goods_price', 't1.is_real','t1.parent_id','t1.is_gift',
            't1.extension_code',"t1.goods_attr", 't1.goods_attr_id'];
        $goods_info = (new Query())->select($fields)->from('ecs_cart as t1')
            ->leftJoin('ecs_goods_attr as t2','t2.goods_id = t1.goods_id')
            ->where(['user_id'=>$user_id])->one();
        $shop_price = (new Query())->select(['shop_price'])->from('ecs_goods')->where(['goods_id'=>$goods_info['goods_id'],'is_delete'=>0])->scalar();
        $goods_info['order_id'] = $order_id;
        $goods_info['goods_price'] = $shop_price;
        $order_goods = new EcsOrderGoods();
        if($order_goods->load($goods_info,'') && $order_goods->save()){
            $cart = EcsCart::findOne(['user_id'=>$user_id]);
            $cart->delete();
            return $order_goods->goods_name;
        }else{
            return false;
        }
    }
}
