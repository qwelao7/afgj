<?php

namespace common\models\ar\fang;

use Yii;

/**
 * This is the model class for table "fang_community_activity".
 *
 * @property string $id
 * @property string $loupan_id
 * @property string $thumbnail
 * @property string $name
 * @property string $signup_end
 * @property string $begin
 * @property string $address
 * @property string $person_num
 * @property string $fee
 * @property string $content
 * @property string $pics
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property string $creater
 * @property integer $valid
 */
class FangCommunityActivity extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'fang_community_activity';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['loupan_id', 'updater', 'creater', 'valid'], 'integer'],
            [['signup_end', 'begin', 'created_at', 'updated_at'], 'safe'],
            [['thumbnail', 'address'], 'string', 'max' => 50],
            [['content'], 'string'],
            [['name'], 'string', 'max' => 25],
            [['address'], 'string', 'max' => 50],
            [['person_num', 'fee'], 'string', 'max' => 15],
            [['pics'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'loupan_id' => '楼盘id',
            'name' => '活动名称',
            'signup_end' => '报名截止时间',
            'begin' => '起始时间',
            'thumbnail' => '缩略图',
            'address' => '活动地址',
            'person_num' => '人数',
            'fee' => '费用',
            'content' => '活动内容',
            'pics' => '图片，json',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'creater' => '创建者id',
            'valid' => '0已经删除，1有效',
        ];
    }

    public static function getInfo($id, $fields=null, $format=true) {
        if(!$fields) {
            $fields = ['id', 'thumbnail', 'name', 'signup_end'];
        }

        $query = FangCommunityActivity::find()->where(['id' => $id, 'valid'=>1])->select($fields)->one();

        if($format && $query) {
            $query['signup_end'] = date('m-d H:i', strtotime($query['signup_end']));
        }

        if($query['thumbnail'] == '' || empty($query['thumbnail'])) {
            $query['thumbnail'] = Yii::$app->params['defaultActImg'];
        }

        return $query;
    }
}
