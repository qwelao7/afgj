<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_community_pub_info_feedback".
 *
 * @property integer $id
 * @property integer $cpi_id
 * @property integer $feedback_reason
 * @property string $feedback_time
 * @property integer $process_status
 * @property integer $process_admin_id
 * @property string $process_time
 */
class HllCommunityPubInfoFeedback extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_community_pub_info_feedback';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cpi_id', 'feedback_reason'], 'required'],
            [['cpi_id', 'feedback_reason', 'process_status', 'process_admin_id'], 'integer'],
            [['feedback_time', 'process_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cpi_id' => 'Cpi ID',
            'feedback_reason' => 'Feedback Reason',
            'feedback_time' => 'Feedback Time',
            'process_status' => 'Process Status',
            'process_admin_id' => 'Process Admin ID',
            'process_time' => 'Process Time',
        ];
    }
}
