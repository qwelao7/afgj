<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "customer".
 *
 * @property integer $id
 * @property string $real_name
 * @property string $mobilephone
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class Customer extends ActiveRecord
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
            [['real_name'], 'string', 'max' => 20],
            [['mobilephone'], 'string', 'max' => 20],
            [['mobilephone'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'real_name' => 'Real Name',
            'mobilephone' => 'Mobilephone',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
