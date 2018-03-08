<?php

namespace common\models\ar\award;

use Yii;

/**
 * This is the model class for table "award".
 *
 * @property string $id
 * @property integer $award_item_id
 * @property integer $award_num
 * @property string $created_at
 * @property string $updated_at
 * @property integer $valid
 */
class Award extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'award';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['award_item_id', 'award_num'], 'required'],
            [['award_item_id', 'award_num', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '奖励编号',
            'award_item_id' => '奖品编号',
            'award_num' => '奖励数量',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'valid' => '存在状态：0删除，1存在',
        ];
    }
}
