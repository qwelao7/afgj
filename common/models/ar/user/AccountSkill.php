<?php

namespace common\models\ar\user;

use Yii;

/**
 * This is the model class for table "account_skill".
 *
 * @property string $id
 * @property integer $account_id
 * @property string $skill
 * @property string $created_at
 * @property string $updated_at
 */
class AccountSkill extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'account_skill';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id'], 'required'],
            [['account_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['skill'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'account_id' => '用户编号',
            'skill' => '技能文本',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
}
