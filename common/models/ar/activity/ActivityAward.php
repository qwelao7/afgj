<?php

namespace common\models\ar\activity;

use Yii;

/**
 * This is the model class for table "activity_award".
 *
 * @property string $id
 * @property integer $activity_id
 * @property integer $award_id
 * @property string $start_time
 * @property string $end_time
 * @property string $created_at
 * @property string $updated_at
 * @property integer $valid
 */
class ActivityAward extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'activity_award';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['activity_id', 'award_id', 'start_time', 'end_time'], 'required'],
            [['activity_id', 'award_id', 'valid'], 'integer'],
            [['start_time', 'end_time', 'created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '活动奖励编号',
            'activity_id' => '活动编号',
            'award_id' => '奖励编号',
            'start_time' => '生效时间',
            'end_time' => '失效时间',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'valid' => '存在状态：0删除，1存在',
        ];
    }
}
