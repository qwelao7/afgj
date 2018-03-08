<?php

namespace common\models\ar\fang;

use Yii;

/**
 * This is the model class for table "fang_decorate_upgrade".
 *
 * @property string $id
 * @property string $decorate_id
 * @property integer $service_id
 * @property string $price
 * @property string $price_unit
 * @property string $sort
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class FangDecorateUpgrade extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'fang_decorate_upgrade';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'updater'], 'required'],
            [['id', 'decorate_id', 'service_id', 'sort', 'creater', 'updater', 'valid'], 'integer'],
            [['price'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['price_unit'], 'string', 'max' => 15]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'decorate_id' => 'fang_decorate.id',
            'service_id' => 'service.id',
            'price' => '价格',
            'price_unit' => '价格的单位',
            'sort' => '排序',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
    
    public function getService() {
        return $this->hasOne(\common\models\ar\service\Service::className(), ['id'=>'service_id']);
    }
}
