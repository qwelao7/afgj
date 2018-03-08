<?php

namespace common\models\ar\user;

use Yii;

/**
 * This is the model class for table "customer".
 *
 * @property string $id
 * @property string $real_name
 * @property string $mobilephone
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class Customer extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'customer';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['real_name', 'mobilephone'], 'required'],
            [['creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['real_name'], 'string', 'max' => 10],
            [['mobilephone'], 'string', 'max' => 20],
            [['mobilephone'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '客户编号',
            'real_name' => '姓名',
            'mobilephone' => '手机号码',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    /**
     * 关联customer_house
     */
    public function getCustomerHouse(){
        return $this->hasMany(CustomerHouse::className(), ['customer_id'=>'id'])->select('id,customer_id,house_id,valid');
    }
}
