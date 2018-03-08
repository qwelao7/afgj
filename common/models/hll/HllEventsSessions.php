<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_events_sessions".
 *
 * @property string $id
 * @property string $events_id
 * @property string $title
 * @property string $content
 * @property integer $sessions_num
 * @property integer $joined_num
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventsSessions extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_events_sessions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['events_id', 'title', 'content'], 'required'],
            [['events_id', 'sessions_num', 'joined_num', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 100],
            [['content'], 'string', 'max' => 500],
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
            'title' => 'Title',
            'content' => 'Content',
            'sessions_num' => 'Sessions Num',
            'joined_num' => 'Joined Num',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
