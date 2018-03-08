<?php

namespace common\models\ar\third;

use Yii;

/**
 * This is the model class for table "hll_third_login".
 *
 * @property string $id
 * @property string $tname
 * @property string $contact_name
 * @property string $contact_phone
 * @property string $appid
 * @property string $appsecret
 * @property string $apptoken
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllThirdLogin extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_third_login';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['appid', 'appsecret'], 'required'],
            [['creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['tname', 'contact_name', 'contact_phone', 'appid', 'appsecret', 'apptoken'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tname' => 'Tname',
            'contact_name' => 'Contact Name',
            'contact_phone' => 'Contact Phone',
            'appid' => 'Appid',
            'appsecret' => 'Appsecret',
            'apptoken' => 'Apptoken',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
