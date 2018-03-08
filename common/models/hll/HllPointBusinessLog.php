<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_point_business_log".
 *
 * @property string $id
 * @property string $unique_id
 * @property integer $business_id
 * @property integer $community_id
 * @property integer $user_id
 * @property integer $point
 * @property integer $left_points
 * @property integer $type
 * @property string $change_reason
 * @property string $period
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllPointBusinessLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_point_business_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['business_id', 'community_id', 'user_id', 'point', 'left_points', 'type', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at','unique_id'], 'safe'],
            [['unique_id'], 'string', 'max' => 32],
            [['change_reason'], 'string', 'max' => 100],
            ['period','default','value'=>intval(f_date(time(),4))]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'unique_id' => 'Unique ID',
            'business_id' => 'Business ID',
            'community_id' => 'Community ID',
            'user_id' => 'User ID',
            'point' => 'Point',
            'left_points' => 'Left Points',
            'type' => 'Type',
            'change_reason' => 'Change Reason',
            'period' => 'Period',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
