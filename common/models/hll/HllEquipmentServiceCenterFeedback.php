<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_equipment_service_center_feedback".
 *
 * @property integer $id
 * @property integer $esc_id
 * @property integer $feedback_reason
 * @property string $feedback_time
 * @property integer $process_status
 * @property integer $process_admin_id
 * @property string $process_time
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEquipmentServiceCenterFeedback extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_equipment_service_center_feedback';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['esc_id', 'feedback_reason', 'feedback_time'], 'required'],
            [['id', 'esc_id', 'feedback_reason', 'process_status', 'process_admin_id','creater', 'updater'], 'integer'],
            [['feedback_time', 'process_time'], 'safe'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'esc_id' => 'Esc ID',
            'feedback_reason' => 'Feedback Reason',
            'feedback_time' => 'Feedback Time',
            'process_status' => 'Process Status',
            'process_admin_id' => 'Process Admin ID',
            'process_time' => 'Process Time',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
