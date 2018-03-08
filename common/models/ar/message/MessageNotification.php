<?php

namespace common\models\ar\message;

use Yii;

/**
 * This is the model class for table "message_notification".
 *
 * @property integer $id
 * @property integer $account_id
 * @property integer $account_type
 * @property string $content
 * @property integer $send_way
 * @property string $to_url
 * @property string $send_time
 * @property integer $send_result
 * @property string $fail_reason
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class MessageNotification extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'message_notification';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'account_type', 'send_way', 'send_result', 'creater', 'updater', 'valid'], 'integer'],
            [['content', 'send_way', 'send_time'], 'required'],
            [['send_time', 'created_at', 'updated_at'], 'safe'],
            [['content'], 'string', 'max' => 1000],
            [['to_url', 'fail_reason'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '通知消息编号',
            'account_id' => '用户编号',
            'account_type' => '1普通用户，2管理员',
            'content' => '文本内容',
            'send_way' => '发送方式：1短信，2微信，3email',
            'to_url' => '跳转地址',
            'send_time' => '发送时间',
            'send_result' => '发送结果：1待发送，2发送成功，3发送失败',
            'fail_reason' => '失败原因',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
    /**
     * 解锁活动，非活动小区尝试参与活动记录
     * @param $userId 发送用户ID
     * @param $content 发送内容
     * @author zend.wang
     * @date  2016-11-16 13:00
     */
    public static function saveUnlockMessageNotification($userId,$content='') {
        $msgNotification = new MessageNotification();
        $msgNotification->account_id = $userId;
        $msgNotification->account_type = 1;
        $msgNotification->content = $content;
        $msgNotification->send_way = 2;
        $msgNotification->to_url = '';
        $msgNotification->send_time = f_date(time());
        $msgNotification->send_result=1;
        $msgNotification->fail_reason = '等活动开始，一并通知';
        $msgNotification->save(false);
    }
}