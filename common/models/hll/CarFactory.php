<?php

namespace common\models\hll;

use Yii;

/**
 * This is the model class for table "hll_car_factory".
 *
 * @property integer $id
 * @property integer $brand_id
 * @property string $name
 * @property string $firstletter
 */
class CarFactory extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'hll_car_factory';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'brand_id', 'name', 'firstletter'], 'required'],
            [['id', 'brand_id'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['firstletter'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '编号',
            'brand_id' => '车辆品牌编号',
            'name' => '厂商名称',
            'firstletter' => '厂商前缀',
        ];
    }
}
