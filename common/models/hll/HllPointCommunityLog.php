<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
use yii\db\Query;

/**
 * This is the model class for table "hll_point_community_log".
 *
 * @property string $id
 * @property string $unique_id
 * @property integer $community_id
 * @property integer $user_id
 * @property integer $business_id
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
class HllPointCommunityLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_point_community_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'user_id', 'business_id', 'point', 'left_points', 'type', 'period', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['unique_id'], 'string', 'max' => 32],
            [['change_reason'], 'string', 'max' => 100],
            ['period','default','value'=>intval(f_date(time(),4))]
        ];
    }
    CONST GIVE_POINT_TYPE=1;//发放
    CONST EXPIRED_POINT_TYPE=2;//过期
    CONST BACK_POINT_TYPE=3;//回收

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'unique_id' => 'Unique ID',
            'community_id' => 'Community ID',
            'user_id' => 'User ID',
            'business_id' => 'Business ID',
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
