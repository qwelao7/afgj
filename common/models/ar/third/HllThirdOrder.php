<?php

namespace common\models\ar\third;

use Yii;

/**
 * This is the model class for table "hll_third_order".
 *
 * @property string $id
 * @property integer $third_id
 * @property integer $order_id
 * @property string $order_push_status
 * @property string $order_push_last_time
 * @property string $third_work_order_id
 * @property string $third_work_order_status
 * @property string $third_order_id
 * @property string $third_order_status
 * @property string $order_detail
 * @property string $order_memo1
 * @property string $order_memo2
 * @property string $order_memo3
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllThirdOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_third_order';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['third_id', 'order_id', 'creater', 'updater', 'valid'], 'integer'],
            [['order_push_last_time', 'created_at', 'updated_at'], 'safe'],
            [['third_order_id', 'order_detail', 'order_memo3'], 'required'],
            [['order_push_status'], 'string', 'max' => 10],
            [['third_work_order_id', 'third_work_order_status', 'third_order_id', 'third_order_status'], 'string', 'max' => 50],
            [['order_detail', 'order_memo1', 'order_memo2', 'order_memo3'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '第三方服务商订单信息编号',
            'third_id' => '服务商编号',
            'order_id' => '我方订单编号',
            'order_push_status' => '订单推送状态',
            'order_push_last_time' => '订单最后推送时间',
            'third_work_order_id' => '第三方工单编号',
            'third_work_order_status' => '第三方工单状态',
            'third_order_id' => '第三方订单编号',
            'third_order_status' => '第三方订单状态',
            'order_detail' => '订单详情',
            'order_memo1' => '订单备注1',
            'order_memo2' => '订单备注2',
            'order_memo3' => '订单备注3',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
}
