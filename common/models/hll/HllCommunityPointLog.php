<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_community_point_log".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $receive_user
 * @property integer $type_id
 * @property string $desc
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllCommunityPointLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_community_point_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'receive_user', 'creater', 'updater', 'valid','type_id'], 'integer'],
            [['receive_user'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['desc'], 'string', 'max' => 40],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'receive_user' => 'Receive User',
            'address_desc' => 'Address Desc',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
