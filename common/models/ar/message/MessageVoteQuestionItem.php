<?php

namespace common\models\ar\message;

use Yii;
use common\components\ActiveRecord;
use yii\db\Exception;

/**
 * This is the model class for table "message_vote_question_item".
 *
 * @property string $id
 * @property integer $mvq_id
 * @property string $content
 * @property string $picpath
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class MessageVoteQuestionItem extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'message_vote_question_item';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['mvq_id', 'content'], 'required'],
            [['mvq_id', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['content', 'picpath'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '投票问题选项编号',
            'mvq_id' => '投票问题编号',
            'content' => '选项文本',
            'picpath' => '选项图片路径',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    //保存问题选项
    public static function saveQuestionOption($img,$option,$question_id){
        $len = sizeof($option);
        $data=[];

        for($i=0;$i < $len;$i++){
            $data[$i][] = $question_id;
            $data[$i][] = $option[$i];
            $data[$i][] = $img[$i];
        }

        $db = Yii::$app->db;

        try{
            $db->createCommand()->batchInsert('{{message_vote_question_item}}', ['mvq_id','content','picpath'],$data)->execute();
        }catch (\Exception $e){
            throw $e;
        }

    }
}
