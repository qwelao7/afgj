<?php

namespace common\models\ar\message;

use Yii;
use common\components\ActiveRecord;

/**
 * This is the model class for table "message_article".
 *
 * @property string $id
 * @property string $title
 * @property string $thumbnail
 * @property string $content
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class MessageArticle extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'message_article';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['title', 'content'], 'required',],
            [['content'], 'string'],
            [['creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 50],
            [['thumbnail'], 'string','max' => 100],
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'title' => '文章标题',
            'thumbnail' => '文章缩略图',
            'content' => '文章内容',
            'creater' => '发布人',
            'created_at' => '发布时间',
        ];
    }
    
    public static function getInfo($id, $fields=null, $format=true) {
        if(!$fields) {
            $fields = ['id', 'thumbnail', 'title', 'created_at'];
        }

        $query = MessageArticle::find()->where(['id' => $id, 'valid'=>1])->select($fields)->one();

        if($format && $query) {
            $query['created_at'] = date('m-d H:i', strtotime($query['created_at']));
        }

        return $query;
    }
}
