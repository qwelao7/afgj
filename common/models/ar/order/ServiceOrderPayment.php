<?php

namespace common\models\ar\order;

use Yii;

/**
 * This is the model class for table "service_order_payment".
 *
 * @property integer $id
 * @property integer $order_id
 * @property string $transaction_id
 * @property integer $pay_type
 * @property string $pay_account
 * @property string $pay_price
 * @property string $pay_remark
 * @property integer $add_time
 * @property integer $add_user
 * @property integer $edit_time
 * @property integer $edit_user
 * @property integer $valid
 */
class ServiceOrderPayment extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'service_order_payment';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['order_id'], 'required'],
            [['order_id', 'pay_type', 'add_time', 'add_user', 'edit_time', 'edit_user', 'valid'], 'integer'],
            [['pay_price'], 'number'],
            [['pay_remark'], 'string'],
            [['transaction_id'], 'string', 'max' => 32],
            [['pay_account'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'order_id' => '订单号',
            'transaction_id' => '第三方交易单号',
            'pay_type' => '支付类型 1 微信',
            'pay_account' => '支付账户',
            'pay_price' => '支付金额',
            'pay_remark' => '支付备注',
            'add_time' => '添加时间',
            'add_user' => '添加人',
            'edit_time' => '编辑时间',
            'edit_user' => '编辑人',
            'valid' => '是否有效(0 无效 1有效)',
        ];
    }
}
