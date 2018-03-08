<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_event_phone".
 *
 * @property string $id
 * @property integer $event_id
 * @property string $phone
 * @property integer $phone_type
 * @property integer $user_id
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventPhone extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_event_phone';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['event_id', 'phone_type', 'user_id', 'creater', 'updater', 'valid'], 'integer'],
            [['phone'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['phone'], 'string', 'max' => 10],
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
            'phone' => 'Phone',
            'phone_type' => 'Phone Type',
            'user_id' => 'User ID',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    public static function checkPhoneUsed($phone)
	{
		$data = static ::find()->where(['valid'=>1, 'phone'=>$phone])->one();
		if ($data){
			if ($data['phone_type']==2){
				return [
					'res_id' => $data['id'],
					'is_used' => false,
				];
			}else if(($data['user_id']==0)){
                return [
                    'res_id' => $data['id'],
                    'is_used' => false,
                ];
			}else{
                return [
                    'res_id' => $data['id'],
                    'is_used' => true,
                ];
            }
		}else{
            return [
                'res_id' => 0,
                'is_used' => true,
            ];
        }
	}
}
