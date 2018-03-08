<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_item_unlock_log".
 *
 * @property integer $id
 * @property integer $unlock_id
 * @property integer $user_id
 * @property string $unlock_time
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 * @property integer $community_id
 */
class ItemUnlockLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_item_unlock_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['unlock_id', 'user_id', 'creater', 'updater', 'valid', 'community_id'], 'integer'],
            [['unlock_time'], 'required'],
            [['unlock_time', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '借用物品解锁日志编号',
            'unlock_id' => '解锁编号',
            'user_id' => '解锁人编号',
            'unlock_time' => '解锁时间',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
            'community_id' => '小区id'
        ];
    }
}
