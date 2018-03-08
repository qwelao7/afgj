<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_event_gift_log".
 *
 * @property string $id
 * @property integer $event_id
 * @property integer $res_id
 * @property integer $gitf_id
 * @property string $gift_content
 * @property integer $user_id
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventGiftLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_event_gift_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['event_id','res_id', 'gitf_id', 'user_id', 'creater', 'updater', 'valid'], 'integer'],
            [['gitf_id'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['gift_content'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'event_id' => 'Event ID',
            'gitf_id' => 'Gitf ID',
            'gift_content' => 'Gift Content',
            'user_id' => 'User ID',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    public static function checkHasReceiveGift($user_id,$event_id=1)
	{
		$data = static ::find()->where(['valid'=>1, 'event_id'=>$event_id,'user_id'=>$user_id])->one();
		return $data?true:false;
	}
}
