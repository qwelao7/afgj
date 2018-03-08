<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\models\hll\CarBrand;
use common\models\hll\CarFactory;

/**
 * This is the model class for table "hll_car_series".
 *
 * @property integer $id
 * @property integer $brand_id
 * @property integer $factory_id
 * @property string $name
 * @property string $firstletter
 * @property integer $seriesstate
 * @property integer $seriesorder
 */
class CarSeries extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'hll_car_series';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'brand_id', 'factory_id', 'name', 'firstletter', 'seriesstate', 'seriesorder'], 'required'],
            [['id', 'brand_id', 'factory_id', 'seriesstate', 'seriesorder'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['firstletter'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '车型编号',
            'brand_id' => '车辆品牌编号',
            'factory_id' => '生产厂家编号',
            'name' => '车型名称',
            'firstletter' => '车型前缀',
            'seriesstate' => 'Seriesstate',
            'seriesorder' => 'Seriesorder',
        ];
    }
}
