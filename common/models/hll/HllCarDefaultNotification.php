<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_car_default_notification".
 *
 * @property integer $id
 * @property integer $brand_id
 * @property integer $series_id
 * @property string $notification_name
 * @property integer $next_month
 * @property integer $next_km
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllCarDefaultNotification extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_car_default_notification';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['brand_id', 'series_id', 'notification_name'], 'required'],
            [['brand_id', 'series_id', 'next_month', 'next_km', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['notification_name'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'brand_id' => 'Brand ID',
            'series_id' => 'Series ID',
            'notification_name' => 'Notification Name',
            'next_month' => 'Next Month',
            'next_km' => 'Next Km',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
