<?php

namespace common\models\ar\community;

use Yii;

/**
 * This is the model class for table "community_help_reply".
 *
 * @property string $id
 * @property integer $help_id
 * @property string $content
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class CommunityHelpReply extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'community_help_reply';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['help_id', 'content'], 'required'],
            [['help_id', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['content'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'help_id' => '求助编号',
            'content' => '回复内容',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
}
