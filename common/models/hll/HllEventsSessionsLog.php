<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_events_sessions_log".
 *
 * @property string $id
 * @property string $events_id
 * @property string $sessions_id
 * @property string $user_id
 * @property string $created_at
 * @property integer $valid
 */
class HllEventsSessionsLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_events_sessions_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['events_id', 'sessions_id'], 'required'],
            [['events_id', 'sessions_id', 'user_id', 'valid'], 'integer'],
            [['created_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'events_id' => 'Events ID',
            'sessions_id' => 'Sessions ID',
            'user_id' => 'User ID',
            'created_at' => 'Created At',
            'valid' => 'Valid',
        ];
    }
}
