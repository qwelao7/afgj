<?php

namespace common\models\ar\user;

use Yii;

/**
 * This is the model class for table "account_favor".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $item_type
 * @property integer $item_id
 * @property string $created_at
 * @property string $updated_at
 */
class AccountFavor extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'account_favor';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'item_type', 'item_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'account_id' => '用户编号',
            'item_type' => '收藏类型：1楼盘',
            'item_id' => '收藏对象编号',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
}
