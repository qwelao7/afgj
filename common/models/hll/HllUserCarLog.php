<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_user_car_log".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $car_id
 * @property integer $notification_id
 * @property integer $log_type
 * @property string $last_date
 * @property string $exec_shop
 * @property string $exec_fee
 * @property integer $last_km
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllUserCarLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_user_car_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'car_id', 'notification_id', 'last_km', 'creater', 'updater', 'valid', 'log_type'], 'integer'],
            [['car_id', 'last_date'], 'required'],
            [['last_date', 'created_at', 'updated_at', 'exec_shop', 'exec_fee'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'car_id' => 'Car ID',
            'notification_id' => 'Notification ID',
            'last_date' => 'Last Date',
            'last_km' => 'Last Km',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
