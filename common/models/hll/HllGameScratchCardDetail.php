<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_game_scratch_card_detail".
 *
 * @property string $id
 * @property integer $scid
 * @property string $game_date
 * @property string $point
 * @property integer $user_id
 * @property string $taken_time
 * @property integer $send_status
 * @property integer $return_status
 * @property integer $return_point
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllGameScratchCardDetail extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_game_scratch_card_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['scid', 'game_date', 'point', 'taken_time'], 'required'],
            [['scid', 'user_id', 'send_status', 'return_status', 'return_point', 'creater', 'updater', 'valid'], 'integer'],
            [['game_date', 'taken_time', 'created_at', 'updated_at'], 'safe'],
            [['point'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'scid' => 'Scid',
            'game_date' => 'Game Date',
            'point' => 'Point',
            'user_id' => 'User ID',
            'taken_time' => 'Taken Time',
            'send_status' => 'Send Status',
            'return_status' => 'Return Status',
            'return_point' => 'Return Point',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
