<?php

namespace common\models\ecs;

use Yii;

/**
 * This is the model class for table "ecs_order_action".
 *
 * @property string $action_id
 * @property string $order_id
 * @property string $action_user
 * @property integer $order_status
 * @property integer $shipping_status
 * @property integer $pay_status
 * @property integer $action_place
 * @property string $action_note
 * @property string $log_time
 */
class EcsOrderAction extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_order_action';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'order_status', 'shipping_status', 'pay_status', 'action_place', 'log_time'], 'integer'],
            [['action_user'], 'string', 'max' => 30],
            [['action_note'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'action_id' => 'Action ID',
            'order_id' => 'Order ID',
            'action_user' => 'Action User',
            'order_status' => 'Order Status',
            'shipping_status' => 'Shipping Status',
            'pay_status' => 'Pay Status',
            'action_place' => 'Action Place',
            'action_note' => 'Action Note',
            'log_time' => 'Log Time',
        ];
    }
}
