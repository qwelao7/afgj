<?php

namespace common\models\ar\message;

use Yii;
use common\components\ActiveRecord;

/**
 * This is the model class for table "message_vote_result".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $mv_id
 * @property integer $mvqi_id
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class MessageVoteResult extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'message_vote_result';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'mvqi_id', 'mv_id'], 'required'],
            [['account_id', 'mvqi_id', 'creater', 'updater', 'valid', 'mv_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '投票结果编号',
            'account_id' => '投票用户编号',
            'mv_id' => '投票编号',
            'mvqi_id' => '投票选项编号',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    public static function getVoteNum($voteId) {
        return MessageVoteResult::find()->where(['mv_id' => $voteId, 'valid' => 1])->select('creater')->distinct()->count();
    }
}
