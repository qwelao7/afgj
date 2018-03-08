<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_event_kid_contact_card".
 *
 * @property string $id
 * @property integer $account_id
 * @property string $kidname
 * @property integer $sex
 * @property string $kindergarten
 * @property string $class
 * @property string $mobilephone
 * @property string $qq
 * @property string $wechat
 * @property string $blessing
 * @property string $pics
 * @property integer $qr_code
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventKidContactCard extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_event_kid_contact_card';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'sex', 'creater', 'updater', 'qr_code','valid'], 'integer'],
            [['kidname'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['kidname', 'class'], 'string', 'max' => 10],
            [['kindergarten', 'mobilephone', 'qq', 'wechat'], 'string', 'max' => 20],
            [['blessing'], 'string', 'max' => 40],
            [['pics'], 'string', 'max' => 500],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'kidname' => 'Kidname',
            'sex' => 'Sex',
            'kindergarten' => 'Kindergarten',
            'class' => 'Class',
            'mobilephone' => 'Mobilephone',
            'qq' => 'Qq',
            'wechat' => 'Wechat',
            'blessing' => 'Blessing',
            'pics' => 'Pics',
            'qr_code' => 'qr_code',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
