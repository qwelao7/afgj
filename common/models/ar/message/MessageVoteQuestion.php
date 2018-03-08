<?php

namespace common\models\ar\message;

use Yii;
use common\components\ActiveRecord;
use yii\db\Exception;

/**
 * This is the model class for table "message_vote_question".
 *
 * @property string $id
 * @property integer $mv_id
 * @property string $title
 * @property integer $votetype
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class MessageVoteQuestion extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'message_vote_question';
    }

    public static $types = [
        '1' => '单选',
        '2' => '多选'
    ];
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['mv_id', 'title', 'votetype'], 'required'],
            [['mv_id', 'votetype', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '投票问题编号',
            'mv_id' => '投票编号',
            'title' => '问题标题',
            'votetype' => '投票方式：1单选，2多选',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    //保存投票问题
    public static function saveVoteQuestion($data,$model_id){
        $transaction = Yii::$app->db->beginTransaction();
        try{
            foreach($data as $item){
                $vote_question = new MessageVoteQuestion();
                $vote_question->mv_id = $model_id;
                $vote_question->title = $item['question_name'];
                $vote_question->votetype = $item['vote_type'];
                if($vote_question->save()){
                    MessageVoteQuestionItem::saveQuestionOption($item['img'],$item['option'],$vote_question->id);
                } else {
                    throw new Exception("vote question exception");
                }
            }
            $transaction->commit();
        }catch (\yii\db\Exception $e){
            $transaction->rollback();
            throw $e;
        }
        return true;
    }
}
