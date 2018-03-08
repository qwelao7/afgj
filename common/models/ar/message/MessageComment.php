<?php

namespace common\models\ar\message;

use Yii;
use common\components\ActiveRecord;

/**
 * This is the model class for table "message_comment".
 *
 * @property string $id
 * @property integer $message_id
 * @property string $content
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class MessageComment extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'message_comment';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['message_id', 'content'], 'required'],
            [['message_id', 'creater', 'updater', 'valid','is_admin'], 'integer'],
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
            'message_id' => '消息编号',
            'content' => '评论内容',
            'is_admin' => '是否管家评论',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
}
