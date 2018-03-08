<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_event_sign_in_log".
 *
 * @property string $id
 * @property integer $user_id
 * @property integer $events_id
 * @property string $sign_in_time
 * @property integer $sign_in_user_num
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventSignInLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_event_sign_in_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'events_id', 'sign_in_user_num', 'creater', 'updater', 'valid'], 'integer'],
            [['sign_in_time', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'events_id' => 'Events ID',
            'sign_in_time' => 'Sign In Time',
            'sign_in_user_num' => 'Sign In User Num',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
