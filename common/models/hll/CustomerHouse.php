<?php

namespace common\models\hll;

use common\models\ecs\EcsUsers;
use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "customer_house".
 *
 * @property integer $id
 * @property integer $customer_id
 * @property integer $house_id
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class CustomerHouse extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'customer_house';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['customer_id', 'house_id'], 'required'],
            [['customer_id', 'house_id', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'customer_id' => 'Customer ID',
            'house_id' => 'House ID',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //public static function addHouse($userId,$addressId) {
    //    $customer = Customer::findOne(['id'=>$userId,'valid'=>1]);
    //    $addressExt = (new Query())->select(['t1.house_id','t2.consignee','t2.mobile'])->from('hll_user_address_ext as t1')
    //            ->leftJoin('ecs_user_address as t2','t2.address_id = t1.address_id')
    //            ->where(['t1.address_id'=>$addressId,'t1.owner_auth'=>1])->one();
    //    if(!$customer) {
    //        //create customer
    //        $user = EcsUsers::findOne($userId);
    //        $customer = new Customer();
    //        $customer->id = $userId;
    //        $customer->real_name = $addressExt['consignee'];
    //        $customer->mobilephone = $addressExt['mobile'];
    //        $customer->save();
    //    }
    //    $customerHouse = CustomerHouse::findOne(['customer_id'=>$userId,'house_id'=>$addressExt['house_id']]);
    //    if(!$customerHouse) {
    //        $customerHouse = new CustomerHouse();
    //        $customerHouse->customer_id = $userId;
    //        $customerHouse->house_id = $addressExt['house_id'];
    //        $customerHouse->save();
    //    }
    //}
}
