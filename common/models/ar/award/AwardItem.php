<?php

namespace common\models\ar\award;

use Yii;

/**
 * This is the model class for table "award_item".
 *
 * @property string $id
 * @property integer $ai_name
 * @property integer $ai_num
 * @property string $created_at
 * @property string $updated_at
 * @property integer $valid
 */
class AwardItem extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'award_item';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['ai_name', 'ai_num'], 'required'],
            [['ai_num', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['ai_name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '奖励编号',
            'ai_name' => '奖品名称',
            'ai_num' => '奖品数量',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'valid' => '存在状态：0删除，1存在',
        ];
    }
}
