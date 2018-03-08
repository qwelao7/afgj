<?php

namespace common\models\ar\user;

use Yii;

/**
 * This is the model class for table "home_page_pic".
 *
 * @property string $id
 * @property integer $loupan_id
 * @property string $loupan_logo
 * @property string $loupan_url
 * @property string $loupan_pics
 * @property integer $isdefault
 * @property string $created_at
 * @property string $updated_at
 */
class HomePagePic extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'home_page_pic';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['loupan_id', 'loupan_logo', 'loupan_url', 'loupan_pics'], 'required'],
            [['loupan_id', 'isdefault'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['loupan_logo', 'loupan_url'], 'string', 'max' => 100],
            [['loupan_pics'], 'string', 'max' => 5000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '首页图片编号',
            'loupan_id' => '楼盘编号',
            'loupan_logo' => '楼盘logo图片',
            'loupan_url' => '楼盘详情url',
            'loupan_pics' => '楼盘图片集',
            'isdefault' => '是否默认显示：0否，1是',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
}
