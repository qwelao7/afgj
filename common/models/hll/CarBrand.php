<?php

namespace common\models\hll;

use Yii;

/**
 * This is the model class for table "hll_car_brand".
 *
 * @property integer $id
 * @property string $name
 * @property string $bfirstletter
 */
class CarBrand extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'hll_car_brand';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'name', 'bfirstletter'], 'required'],
            [['id'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['bfirstletter'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '车辆品牌编号',
            'name' => '车辆品牌名',
            'bfirstletter' => '车辆品牌名前缀',
        ];
    }
}
