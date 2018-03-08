<?php

namespace common\models\ar\order;

use Yii;

/**
 * This is the model class for table "service_order_schedule".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $order_id
 * @property integer $service_id
 * @property integer $sqs_id
 * @property string $sdate
 * @property string $created_at
 * @property string $updated_at
 * @property integer $valid
 */
class ServiceOrderSchedule extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'service_order_schedule';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'order_id', 'service_id', 'sqs_id', 'valid'], 'integer'],
            [['sdate'], 'required'],
            [['sdate', 'created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '服务订单排班编号',
            'account_id' => '用户编号',
            'order_id' => '订单编号',
            'service_id' => '服务编号',
            'sqs_id' => '服务报价排班编号',
            'sdate' => '服务日期',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'valid' => '状态：0无效，1有效',
        ];
    }
}
