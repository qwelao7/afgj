<?php

namespace common\models\hll;

use Yii;

/**
 * This is the model class for table "hll_event_check_log".
 *
 * @property string $id
 * @property integer $user_id
 * @property integer $events_id
 * @property integer $apply_id
 * @property integer $check_status
 * @property string $fail_reason
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventCheckLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_event_check_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'events_id', 'check_status', 'creater', 'updater', 'valid','apply_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['fail_reason'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'events_id' => 'Events ID',
            'apply_id' => 'Apply ID',
            'check_status' => 'Check Status',
            'fail_reason' => 'Fail Reason',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
