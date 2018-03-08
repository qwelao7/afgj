<?php

namespace common\models\ecs;

use Yii;
use yii\db\Query;

/**
 * 用户地址管理
 * This is the model class for table "ecs_user_address".
 *
 * @property integer $address_id
 * @property string $address_name
 * @property integer $user_id
 * @property string $consignee
 * @property string $email
 * @property integer $country
 * @property integer $province
 * @property integer $city
 * @property integer $district
 * @property string $address
 * @property string $zipcode
 * @property string $tel
 * @property string $mobile
 * @property string $sign_building
 * @property string $best_time
 */
class EcsUserAddress extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_user_address';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'country', 'province', 'city', 'district','is_default'], 'integer'],
            [['district'], 'required'],
            [['address_name'], 'string', 'max' => 50],
            [['consignee', 'email', 'zipcode', 'tel', 'mobile'], 'string', 'max' => 60],
            [['address', 'sign_building', 'best_time'], 'string', 'max' => 120]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'address_id' => 'Address ID',
            'address_name' => 'Address Name',
            'user_id' => 'User ID',
            'consignee' => 'Consignee',
            'email' => 'Email',
            'country' => 'Country',
            'province' => 'Province',
            'city' => 'City',
            'district' => 'District',
            'address' => 'Address',
            'zipcode' => 'Zipcode',
            'is_default' => 'Is Default',
            'tel' => 'Tel',
            'mobile' => 'Mobile',
            'sign_building' => 'Sign Building',
            'best_time' => 'Best Time',
        ];
    }

    /**
     * 生成地址详情信息
     * @param $data
     * @return string 地址详情
     */
    public static function generateAddressDesc($data)
    {
        $desc = $data['community_name'];

        if ($data['group_name']) {
            $desc .= $data['group_name'];
        }
        if ($data['building_num']) {
            $desc .= $data['building_num'].'栋';
        }
        if ($data['unit_num']) {
            $desc .= $data['unit_num'].'单元';
        }
        if ($data['house_num']) {
            $desc .= $data['house_num'].'室';
        }
        return $desc;
    }

    /**
     * 根据用户id获取收货地址
     * @param $user_id
     * @param $address_id
     * @return array
     */
    public static function getAddressByUserId($user_id,$address_id=0){
        $fields = ['consignee','mobile','is_default','province','city','district','address','address_id'];
        $address_list = EcsUserAddress::find()->select($fields)->where(['user_id'=>$user_id,'valid'=>1])
            ->orderBy(['is_default'=>SORT_DESC])->asArray()->all();

        if (!$address_list) {
            return [];
        } else {
            if($address_id == 0){
                $address_id = $address_list[0]['address_id'];
            }
            foreach($address_list as &$item){
                $item['province'] = static::getProvinceAndCity($item['province']);
                $item['city'] = static::getProvinceAndCity($item['city']);
                $item['district'] = static::getProvinceAndCity($item['district']);
                if($item['address_id'] == $address_id){
                    $item['is_select'] = 1;
                }else{
                    $item['is_select'] = 0;
                }
            }
        }
        $is_select = array_column($address_list,'is_select');
        array_multisort($is_select,SORT_DESC,$address_list);
        return $address_list;
    }

    /**
     * 根据用户id获取认证地址
     * @param $user_id
     * @return array
     */
    public static function getAuthAddressByUserId($user_id){
        $address = (new Query())->select(['id','address_desc'])->from('hll_user_address')
            ->where(['owner_auth'=>1,'valid'=>1,'account_id'=>$user_id])->all();
        if(!$address){
            return [];
        }
        $info['id'] = array_column($address, 'id');
        $info['name'] = array_column($address, 'address_desc');
         return $info;
    }

    public static function getAddressDesc($id){
        $address_decs = '';
        $address = (new Query())->select(['city','district','community_name'])
            ->from('hll_user_address_temp')
            ->where(['id'=>$id,'valid'=>1])->one();
        if(!$address){
            $address = (new Query())->select(['t2.city','t2.district','t2.name as community_name'])
                ->from('hll_user_address as t1')
                ->leftJoin('hll_community as t2','t2.id = t1.community_id')
                ->where(['t1.id'=>$id,'t1.valid'=>1,'t2.valid'=>1])->one();
        }
        if(!$address){
            return $address_decs;
        }
        $address_decs = static::getProvinceAndCity($address['city']).'市 '.
            static::getProvinceAndCity($address['district']).' '.$address['community_name'];
        return $address_decs;
    }

    /**
     * 获取省市区
     * @param $region_id
     * @return false|null|string
     */
    public static function getProvinceAndCity($region_id){
        $region_info = (new Query())->select(['region_name'])->from('ecs_region')->where(['region_id'=>$region_id])->scalar();
        return $region_info;
    }

    /**
     * 获取指定地址
     * @param $user_id
     * @param $address_id
     * @return array|bool
     */
    public static function getAddressByAddressId($user_id,$address_id){
        $fields = ['consignee','mobile','is_default','province','city','district','address','address_id'];
        $query = (new Query())->select($fields)->from('ecs_user_address')
            ->where(['user_id'=>$user_id,'valid'=>1]);
        if($address_id == 0){
            $query->andWhere(['is_default'=>1]);
        }else{
            $query->andWhere(['address_id'=>$address_id]);
        }

        $address = $query->one();
        if(!$address) {
            $address = (new Query())->select($fields)->from('ecs_user_address')
                ->where(['user_id' => $user_id, 'valid' => 1])
                ->orderBy(['address_id'=>SORT_ASC])->one();
            if (!$address) {
                $address = [];
            }
        }
        if($address){
            $address['province'] = static::getProvinceAndCity($address['province']);
            $address['city'] = static::getProvinceAndCity($address['city']);
            $address['district'] = static::getProvinceAndCity($address['district']);
        }
        return $address;
    }

    /**
     * 获取配送和支付方式
     * @param $address_id
     * @param $goods_id
     * @param $goods_money
     * @return mixed
     */
    public static function getShippingInfo($address_id,$goods_id,$goods_money,$goods_num){
        if($address_id == 0){
            $region_id = 1;
        }
        else{
            $region_id = (new Query())->select(['country','province','city','district'])
                ->from('ecs_user_address')->where(['address_id'=>$address_id])->one();
        }
        $shipping_id =static::getShippingByGoods($goods_id);
        $fields = ['t2.shipping_name','t2.shipping_id','t1.shipping_area_id'];
        $info['shipping'] = (new Query())->select($fields)->from('ecs_shipping_area as t1')
            ->leftJoin('ecs_shipping as t2','t2.shipping_id = t1.shipping_id')
            ->leftJoin('ecs_area_region as t3','t3.shipping_area_id = t1.shipping_area_id')
            ->where(['t2.enabled'=>1,'t3.region_id'=>$region_id])
            ->andFilterWhere(['t1.shipping_id'=>$shipping_id])->all();
        $info['payment'] = (new Query())->select(['pay_id','pay_name'])
            ->from('ecs_payment')->where(['enabled'=>1])->all();
        foreach($info['shipping'] as &$item){
            $item['shipping_fee'] = static::getShippingFee($item['shipping_area_id'],$goods_id,$goods_money,$goods_num);
        }

        return $info;
    }

    /**
     * 获取运费
     * @param $shipping_area_id
     * @param $goods_id
     * @param $goods_money
     * @return int
     */
    public static function getShippingFee($shipping_area_id,$goods_id,$goods_money,$goods_number){
        $goods = (new Query())->select(['goods_weight','is_shipping'])->from('ecs_goods')
            ->where(['goods_id'=>$goods_id])->one();
        if($goods['is_shipping'] == 1){
            return 0;
        }
        if(intval($goods['goods_weight']) == 0){
            $goods_weight = 1;
        }else{
            $goods_weight = ceil($goods['goods_weight']);
        }
        $shipping_area = (new Query())->select(['configure'])->from('ecs_shipping_area')
            ->where(['shipping_area_id'=>$shipping_area_id])->scalar();
        $shipping_area = unserialize($shipping_area);

        switch(count($shipping_area)){
            case 4:
                if($goods_money >= $shipping_area[1]['value'] && $shipping_area[1]['value'] != ''){
                    $shipping_fee = 0;
                }else{
                    $shipping_fee = (float)$shipping_area[0]['value'];
                }
                break;
            case 5:
                if($goods_money >= $shipping_area[3]['value'] && !empty($shipping_area[3]['value'])){
                    $shipping_fee = 0;
                }else{
                    $shipping_fee = (float)$shipping_area[1]['value'] + $shipping_area[2]['value'] * ($goods_weight  * $goods_number - 1);
                }
                break;
            default:
                $shipping_fee = 0;
                break;
        }
        return $shipping_fee;
    }

    /**
     * 筛选运输方式
     * @param $goods_id
     * @return array|false|null|string
     */
    public static function getShippingByGoods($goods_id){
        $shipping_id = (new Query())->select(['shipping_way'])->from('hll_goods_shipping')
            ->where(['goods_id'=>$goods_id,'valid'=>1])->scalar();
        if($shipping_id){
            $shipping_id = explode(',',$shipping_id);
        }else{
            $shipping_id = [];
        }
        return $shipping_id;
    }
}
