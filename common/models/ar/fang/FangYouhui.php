<?php

namespace common\models\ar\fang;

use Yii;

/**
 * This is the model class for table "fang_youhui".
 *
 * @property string $id
 * @property string $loupan_id
 * @property string $title
 * @property string $content
 * @property string $begin
 * @property string $end
 * @property string $sort
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class FangYouhui extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'fang_youhui';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['loupan_id','title','content','begin','end'], 'required'],
            [['loupan_id', 'sort', 'creater', 'updater', 'valid'], 'integer'],
            [['content'], 'string'],
            [['begin', 'end', 'created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 30]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'loupan_id' => '楼盘',
            'title' => '标题',
            'content' => '内容',
            'begin' => '起始时间',
            'end' => '结束时间',
            'sort' => '展示顺序（填写整数，数值越小，展示越靠前）',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }


    public function getLoupan(){
        return $this->hasOne(\common\models\ar\fang\FangLoupan::className(), ['id'=>'loupan_id']);
    }
}
