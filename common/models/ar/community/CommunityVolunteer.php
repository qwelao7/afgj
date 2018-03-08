<?php

namespace common\models\ar\community;

use Yii;

/**
 * This is the model class for table "community_volunteer".
 *
 * @property string $id
 * @property integer $loupan_id
 * @property integer $account_id
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class CommunityVolunteer extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'community_volunteer';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['loupan_id', 'account_id', 'creater', 'updater', 'valid'], 'integer'],
            [['account_id'], 'required'],
            [['created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'loupan_id' => '楼盘编号',
            'account_id' => '用户编号',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
}
