<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_events_apply_refund".
 *
 * @property integer $id
 * @property integer $apply_id
 * @property integer $user_id
 * @property integer $status
 * @property string $check_reason
 * @property integer $check_user
 * @property integer $point
 * @property string $fee
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventsApplyRefund extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_events_apply_refund';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'apply_id', 'user_id', 'status', 'check_user', 'point', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['check_reason'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'apply_id' => 'Apply ID',
            'user_id' => 'User ID',
            'status' => 'Status',
            'check_reason' => 'Check Reason',
            'check_user' => 'Check User',
            'point' => 'Point',
            'fee' => 'Fee',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
