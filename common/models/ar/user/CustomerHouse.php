<?php

namespace common\models\ar\user;

use Yii;
use common\models\ar\fang\FangHouse;

/**
 * This is the model class for table "customer_house".
 *
 * @property string $id
 * @property integer $customer_id
 * @property integer $house_id
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class CustomerHouse extends \yii\db\ActiveRecord
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
            'id' => '客户房产编号',
            'customer_id' => '客户编号',
            'house_id' => '房产编号',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    /**
     * 关联FangHouse
     * @return ActiveQuery
     */
    public function getFangHouse(){
        return $this->hasOne(FangHouse::className(), ['id'=>'house_id']);
    }
}
