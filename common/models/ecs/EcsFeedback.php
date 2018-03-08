<?php

namespace common\models\ecs;

use Yii;

/**
 * This is the model class for table "ecs_feedback".
 *
 * @property string $msg_id
 * @property string $parent_id
 * @property string $user_id
 * @property string $user_name
 * @property string $user_email
 * @property string $msg_title
 * @property integer $msg_type
 * @property integer $msg_status
 * @property string $msg_content
 * @property string $msg_time
 * @property string $message_img
 * @property string $order_id
 * @property integer $msg_area
 */
class EcsFeedback extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_feedback';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'user_id', 'msg_type', 'msg_status', 'msg_time', 'order_id', 'msg_area'], 'integer'],
            [['msg_content'], 'required'],
            [['msg_content'], 'string'],
            [['user_name', 'user_email'], 'string', 'max' => 60],
            [['msg_title'], 'string', 'max' => 200],
            [['message_img'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'msg_id' => 'Msg ID',
            'parent_id' => 'Parent ID',
            'user_id' => 'User ID',
            'user_name' => 'User Name',
            'user_email' => 'User Email',
            'msg_title' => 'Msg Title',
            'msg_type' => 'Msg Type',
            'msg_status' => 'Msg Status',
            'msg_content' => 'Msg Content',
            'msg_time' => 'Msg Time',
            'message_img' => 'Message Img',
            'order_id' => 'Order ID',
            'msg_area' => 'Msg Area',
        ];
    }
}
