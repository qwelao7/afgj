<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;

/**
 * This is the model class for table "hll_bill".
 *
 * @property string $bill_id
 * @property string $bill_sn
 * @property string $user_id
 * @property string $bill_category
 * @property string $title
 * @property string $remark
 * @property integer $pay_id
 * @property string $pay_name
 * @property string $point
 * @property string $point_money
 * @property string $bonus
 * @property string $bonus_id
 * @property string $bill_amount
 * @property string $money_paid
 * @property integer $bill_status
 * @property integer $pay_status
 * @property string $created_at
 * @property string $confirm_time
 * @property string $pay_time
 */
class HllBill extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_bill';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'pay_id', 'point', 'bonus_id', 'bill_status', 'pay_status','bill_category','bill_sn'], 'integer'],
            [['pay_name'], 'required'],
            [['point_money', 'bonus', 'bill_amount', 'money_paid'], 'number'],
            [['created_at', 'confirm_time', 'pay_time'], 'safe'],
            [['title', 'remark'], 'string', 'max' => 255],
            [['pay_name'], 'string', 'max' => 120]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bill_id' => 'Bill ID',
            'bill_sn' => 'Bill Sn',
            'user_id' => 'User ID',
            'bill_category' => 'Bill Category',
            'title' => 'Title',
            'remark' => 'Remark',
            'pay_id' => 'Pay ID',
            'pay_name' => 'Pay Name',
            'point' => 'Point',
            'point_money' => 'Point Money',
            'bonus' => 'Bonus',
            'bonus_id' => 'Bonus ID',
            'bill_amount' => 'Bill Amount',
            'money_paid' => 'Money Paid',
            'bill_status' => 'Bill Status',
            'pay_status' => 'Pay Status',
            'created_at' => 'Created At',
            'confirm_time' => 'Confirm Time',
            'pay_time' => 'Pay Time',
        ];
    }

    public static function getBillListByUser($user_id){
        $query_1 = (new Query())->select(['t1.money_paid','t3.goods_name as name',
            "date_format(t1.pay_time,'%Y-%m-%d') as add_time","date_format(t1.pay_time,'%Y%m') as month",
            'goods_thumb'])
            ->from('hll_bill as t1')
            ->leftJoin('ecs_order_goods as t2','t2.order_id = t1.bill_sn')
            ->leftJoin('ecs_goods as t3','t3.goods_id = t2.goods_id')
            ->where(['t1.user_id'=>$user_id,'t1.pay_status'=>2,'t1.bill_category'=>1,'t3.is_delete'=>0]);
        $query_2 = (new Query())->select(['t1.money_paid','t3.title as name',
            "date_format(t1.pay_time,'%Y-%m-%d') as add_time","date_format(t1.pay_time,'%Y%m') as month",
            "CONCAT('http://pub.huilaila.net/',thumbnail)  as goods_thumb"])
            ->from('hll_bill as t1')
            ->leftJoin('hll_events_apply as t2','t2.id = t1.bill_sn')
            ->leftJoin('hll_events as t3','t3.id = t2.events_id')
            ->where(['t1.user_id'=>$user_id,'t1.pay_status'=>2,'t1.bill_category'=>2,'t3.valid'=>1,'t2.valid'=>1,'t2.pay_status'=>2]);
        $query_1->union($query_2,true);
        $query = (new Query())->select(['*'])->from([$query_1])->orderBy(['add_time' => SORT_DESC]);
        return $query;
    }
}
