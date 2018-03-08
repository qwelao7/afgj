<?php

namespace common\models\ecs;

use Yii;

/**
 * This is the model class for table "ecs_comment".
 *
 * @property string $comment_id
 * @property integer $comment_type
 * @property string $id_value
 * @property string $email
 * @property string $user_name
 * @property string $content
 * @property integer $comment_rank
 * @property string $add_time
 * @property string $ip_address
 * @property integer $status
 * @property string $parent_id
 * @property string $user_id
 * @property integer $order_id
 */
class EcsComment extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_comment';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['comment_type', 'id_value', 'comment_rank', 'add_time', 'status', 'parent_id', 'user_id', 'order_id'], 'integer'],
            [['content', 'order_id'], 'required'],
            [['content'], 'string'],
            [['email', 'user_name'], 'string', 'max' => 60],
            [['ip_address'], 'string', 'max' => 15],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'comment_id' => 'Comment ID',
            'comment_type' => 'Comment Type',
            'id_value' => 'Id Value',
            'email' => 'Email',
            'user_name' => 'User Name',
            'content' => 'Content',
            'comment_rank' => 'Comment Rank',
            'add_time' => 'Add Time',
            'ip_address' => 'Ip Address',
            'status' => 'Status',
            'parent_id' => 'Parent ID',
            'user_id' => 'User ID',
            'order_id' => 'Order ID',
        ];
    }
}
