<?php

namespace common\models\ar\message;

use Yii;
use common\models\ar\system\QrCode;
use common\components\ActiveRecord;

/**
 * This is the model class for table "message_praise".
 *
 * @property string $id
 * @property integer $message_id
 * @property string $creater
 * @property string $created_at
 */
class MessagePraise extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'message_praise';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['message_id'], 'required'],
            [['message_id', 'creater'], 'integer'],
            [['created_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'message_id' => '消息编号',
            'creater' => '创建者id',
            'created_at' => '创建时间',
        ];
    }

    /* 判断用户是否点赞 */
    public static function isPraise($messageId) {
        return messagePraise::find()->where(['message_id'=>$messageId, 'creater'=>Yii::$app->user->id])
                                    ->count();
    }

    public static function hasPraise($messageId,$userId) {
        return messagePraise::find()->where(['message_id'=>$messageId, 'creater'=>$userId])->count();
    }
}
