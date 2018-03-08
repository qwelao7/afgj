<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_item_unlock_requirement".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $requirement_type
 * @property string $requirement_content
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class ItemUnlockRequirement extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_item_unlock_requirement';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'requirement_type', 'creater', 'updater', 'valid'], 'integer'],
            [['requirement_content'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['requirement_content'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '借用物品解锁需求编号',
            'user_id' => '用户编号',
            'requirement_type' => '需求类型：1、物品，2、小区',
            'requirement_content' => '需求内容',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
}
