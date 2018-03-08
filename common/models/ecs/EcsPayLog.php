<?php

namespace common\models\ecs;

use Yii;
use yii\db\Query;
/**
 * This is the model class for table "ecs_pay_log".
 *
 * @property string $log_id
 * @property string $order_id
 * @property string $order_amount
 * @property integer $order_type
 * @property integer $is_paid
 * @property string $openid
 * @property string $transid
 * @property string $add_time
 */
class EcsPayLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_pay_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'order_type', 'is_paid'], 'integer'],
            [['order_amount'], 'required'],
            [['order_amount'], 'number'],
            [['add_time'], 'safe'],
            [['openid', 'transid'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'log_id' => 'Log ID',
            'order_id' => 'Order ID',
            'order_amount' => 'Order Amount',
            'order_type' => 'Order Type',
            'is_paid' => 'Is Paid',
            'openid' => 'Openid',
            'transid' => 'Trans Id',
            'add_time' => 'Add Time',
        ];
    }

    public static function getPayTotal($user_id){
        $total = [];
        $data = (new Query())->select(['money_paid',"CONCAT(date_format(pay_time,'%Y%m'),'月') as add_time"])
            ->from('hll_bill')
            ->where(['user_id'=>$user_id,'pay_status'=>2])
            ->orderBy(['pay_time'=>SORT_DESC])->all();

        if(empty($data)){
            return [];
        }else{
            foreach($data as $item){
                if(isset($total[$item['add_time']])){
                    $total[$item['add_time']] += $item['money_paid'];
                }
                else{
                    $total[$item['add_time']] = 0;
                    $total[$item['add_time']] += $item['money_paid'];
                }
            }

            return $total;
        }
    }
}
