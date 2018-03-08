<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
use yii\db\Query;
/**
 * This is the model class for table "hll_user_car".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $brand_id
 * @property integer $series_id
 * @property string $color
 * @property string $car_num
 * @property string $buy_date
 * @property integer $now_km
 * @property string $record_km_date
 * @property integer $warnning_num
 * @property integer $alert_status
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllUserCar extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_user_car';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'brand_id', 'series_id', 'now_km', 'alert_status', 'warnning_num', 'valid'], 'integer'],
            [['brand_id', 'series_id', 'color', 'car_num'], 'required'],
            [['buy_date', 'record_km_date', 'created_at', 'updated_at'], 'safe'],
            [['color'], 'string', 'max' => 10],
            [['car_num'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'brand_id' => 'Brand ID',
            'series_id' => 'Series ID',
            'color' => 'Color',
            'car_num' => 'Car Num',
            'buy_date' => 'Buy Date',
            'now_km' => 'Now Km',
            'record_km_date' => 'Record Km Date',
            'alert_status' => 'Alert Status',
            'warnning_num' => 'Warnning Num',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 某个id的车辆信息
     */
    public static function infoById($id, $fields=null){
       if ($fields == null) {
           $fields = ['t1.id', 't1.color', 't1.car_num', 't1.buy_date', 't1.now_km', 't1.record_km_date',
               't1.brand_id', 't1.series_id','t1.warnning_num', 't1.alert_status',
               't2.name as series_name', 't3.name as brand_name'];
       }

        $data = (new Query())->select($fields)
            ->from('hll_user_car as t1')->leftJoin('hll_car_series as t2', 't2.id = t1.series_id')
            ->leftJoin('hll_car_brand as t3', 't3.id = t1.brand_id')->where(['t1.id'=>$id, 't1.valid'=>1])->one();
        return $data;
    }

    /**
     * 获取用户车辆列表
     */
    public static function getList($userId) {
        if (empty($userId)) {
            return false;
        } else {
            $fields = ['t1.id', 't1.color', 't1.car_num', 't1.buy_date', 't1.now_km', 't1.record_km_date',
                't1.brand_id', 't1.series_id','t1.warnning_num', 't1.alert_status',
                't2.name as series_name', 't3.name as brand_name'];
            $result = (new Query())->select($fields)
                ->from('hll_user_car as t1')
                ->leftJoin('hll_car_series as t2', 't2.id = t1.series_id')
                ->leftJoin('hll_car_brand as t3', 't3.id = t1.brand_id')
                ->where(['t1.account_id'=>$userId, 't1.valid'=>1])
                ->orderBy(['t1.warnning_num' => SORT_DESC, 't1.created_at' => SORT_DESC]);

            return $result;
        }
    }
}
