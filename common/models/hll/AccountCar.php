<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\models\hll\CarBrand;
use common\models\hll\CarFactory;
use common\models\hll\CarSeries;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_account_car".
 *
 * @property integer $id
 * @property integer $account_id
 * @property integer $brand_id
 * @property integer $model_id
 * @property string $color
 * @property string $car_num
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class AccountCar extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'hll_account_car';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'brand_id', 'model_id', 'creater', 'updater', 'valid'], 'integer'],
            [['brand_id', 'model_id', 'color', 'car_num'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['color'], 'string', 'max' => 10],
            [['car_num'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '车辆信息编号',
            'account_id' => '用户编号',
            'brand_id' => '车辆品牌编号',
            'model_id' => '车辆型号编号',
            'color' => '车辆颜色',
            'car_num' => '车牌号',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }


    /**
     * 某个id的车辆信息
     */
    public static function infoById($id){
        $data = (new Query())->select(['t1.color', 't1.car_num', 't1.brand_id', 't1.model_id', 't2.name as model_name', 't3.name as brand_name'])
                            ->from('hll_account_car as t1')->leftJoin('hll_car_series as t2', 't2.id = t1.model_id')
                            ->leftJoin('hll_car_brand as t3', 't3.id = t1.brand_id')->where(['t1.id'=>$id, 't1.valid'=>1])->one();
        return $data;
    }
}
