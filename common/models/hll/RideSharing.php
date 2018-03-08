<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_ride_sharing".
 *
 * @property integer $id
 * @property integer $loupan_id
 * @property integer $account_id
 * @property integer $car_id
 * @property string $go_time
 * @property string $origin
 * @property string $destination
 * @property integer $leave_seat
 * @property string $wish_message
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class RideSharing extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'hll_ride_sharing';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['loupan_id', 'account_id', 'car_id', 'leave_seat', 'creater', 'updater', 'valid'], 'integer'],
            [['go_time', 'origin', 'destination'], 'required'],
            [['go_time', 'created_at', 'updated_at'], 'safe'],
            [['origin', 'destination'], 'string', 'max' => 20],
            [['wish_message'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '顺风车信息编号',
            'loupan_id' => '顺风车信息归属小区编号',
            'account_id' => '用户编号',
            'car_id' => '车辆编号',
            'go_time' => '出发时间',
            'origin' => '出发地点',
            'destination' => '目的地点',
            'leave_seat' => '剩余座位数',
            'wish_message' => '希望乘客',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
}
