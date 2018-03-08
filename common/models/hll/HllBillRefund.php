<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "hll_bill_refund".
 *
 * @property string $id
 * @property string $user_id
 * @property integer $bill_id
 * @property integer $pay_id
 * @property string $pay_name
 * @property string $money_paid
 * @property integer $pay_status
 * @property string $commercial_id
 * @property string $trans_id
 * @property string $created_at
 * @property string $confirm_time
 * @property string $pay_time
 */
class HllBillRefund extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_bill_refund';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'bill_id', 'pay_id', 'pay_status'], 'integer'],
            [['pay_name'], 'required'],
            [['money_paid'], 'number'],
            [['created_at', 'confirm_time', 'pay_time'], 'safe'],
            [['pay_name'], 'string', 'max' => 120],
            [['commercial_id', 'trans_id'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'bill_id' => 'Bill ID',
            'pay_id' => 'Pay ID',
            'pay_name' => 'Pay Name',
            'money_paid' => 'Money Paid',
            'pay_status' => 'Pay Status',
            'commercial_id' => 'Commercial ID',
            'trans_id' => 'Trans ID',
            'created_at' => 'Created At',
            'confirm_time' => 'Confirm Time',
            'pay_time' => 'Pay Time',
        ];
    }

    public static function getBillRefundByUser($user_id,$type,$id){
        $bill_refund = (new Query())->select([])->from('hll_bill_refund as t1')
            ->leftJoin('hll_bill as t2','t2.bill_id = t1.bill_id')
            ->where(['t1.user_id'=>$user_id,'t2.bill_category'=>$type,'t2.bill_sn'=>$id])->count();
        return $bill_refund;
    }

}
