<?php

namespace common\models\ar\message;

use Yii;
use common\components\ActiveRecord;

/**
 * This is the model class for table "message_vote".
 *
 * @property string $id
 * @property string $title
 * @property string $deadline
 * @property string $content
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 * @property integer $is_show
 * @property string $thumbnail
 */
class MessageVote extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'message_vote';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['title', 'deadline'], 'required'],
            [['deadline', 'created_at', 'updated_at'], 'safe'],
            [['creater', 'updater', 'valid', 'is_show'], 'integer'],
            [['title'], 'string', 'max' => 50],
            [['thumbnail'], 'string', 'max' => 100],
            [['content'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '投票编号',
            'title' => '文章标题',
            'deadline' => '投票截止时间',
            'content' => '投票描述',
            'thumbnail' => '缩略图',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
            'is_show' => '投票结果是否展示 0-不展示 1-展示'
        ];
    }

    public static function getInfo($id, $fields=null, $format=true) {
        if(!$fields) {
            $fields = ['id', 'title', 'deadline', 'content', 'thumbnail'];
        }

        $query = MessageVote::find()->select($fields)->where(['id' => $id, 'valid' => 1])->one();

        if($format && $query) {
            $query['deadline'] = date('m-d H:i', strtotime($query['deadline']));
        }

        if($query['thumbnail'] == '' || empty($query['thumbnail'])) {
            $query['thumbnail'] = Yii::$app->params['defaultVoteImg'];
        }

        return $query;
    }
}
