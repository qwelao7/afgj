<?php

namespace common\models\ar\fang;

use Yii;

/**
 * This is the model class for table "fang_house_type".
 *
 * @property integer $id
 * @property string $loupan_id
 * @property string $name
 * @property string $pic
 * @property string $fangxin
 * @property string $area
 * @property string $lowest_total_price
 * @property string $sort
 * @property string $creater
 * @property string $create_at
 * @property string $updater
 * @property string $update_at
 */
class FangHouseType extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'fang_house_type';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['loupan_id', 'sort', 'creater', 'updater'], 'integer'],
            [['lowest_total_price'], 'number'],
            [['create_at', 'update_at'], 'safe'],
            [['updater'], 'required'],
            [['name'], 'string', 'max' => 15],
            [['pic'], 'string', 'max' => 100],
            [['area','fangxin'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'loupan_id' => '楼盘id',
            'name' => '户型名称',
            'pic' => '户型图片',
            'fangxin' => '房型',
            'area' => '面积',
            'lowest_total_price' => '最低总价，单位万',
            'sort' => '排序',
            'creater' => '创建者id',
            'create_at' => '创建时间',
            'updater' => '更新者id',
            'update_at' => '更新时间',
        ];
    }
    
    public function getDecorate() {
        return $this->hasMany(FangDecorate::className(), ['house_type_id'=>'id'])->joinWith(['service'])->orderBy(['fang_decorate.sort'=>SORT_ASC]);
    }
}
