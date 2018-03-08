<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_events_community".
 *
 * @property string $id
 * @property string $events_id
 * @property string $community_id
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventsCommunity extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_events_community';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['events_id'], 'required'],
            [['events_id', 'community_id', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'events_id' => 'Events ID',
            'community_id' => 'Community ID',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
