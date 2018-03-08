<?php

namespace common\models\ar\order;

use Yii;
use yii\db\Query;
use common\models\ar\user\AccountAddress;


/**
 * This is the model class for table "service_order_address".
 *
 * @property string $id
 * @property integer $order_id
 * @property integer $address_id
 * @property string $contact_to
 * @property string $mobile
 * @property string $created_at
 * @property string $street
 * @property string $mansion
 * @property string $building_house_num
 */
class ServiceOrderAddress extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'service_order_address';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'address_id'], 'integer'],
            [['created_at'], 'safe'],
            [['order_id'], 'required'],
            [['contact_to', 'street', 'mansion'], 'string', 'max' => 100],
            [['mobile'], 'string', 'max' => 15],
            [['building_house_num'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => '订单',
            'address_id' => '服务地址',
            'contact_to' => '联系人',
            'mobile' => '手机',
            'created_at' => '创建时间',
            'street' => '街道',
            'mansion' => '小区',
            'building_house_num' => '门牌号'
        ];
    }

    /**
     * 获取订单地址信息
     * @param id 订单id
     */
    public static function addressDetail($id) {
        $data = (new Query())->select(['t1.mobile', 't1.contact_to', 't2.mansion', 't2.building_house_num'])
                ->from('service_order_address as t1')
                ->leftJoin('account_address as t2', 't2.id=t1.address_id')
                ->where('t1.order_id='.$id)
                ->one();
        return $data;
    }
}
