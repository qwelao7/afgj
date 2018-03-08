<?php

namespace common\models\ecs;

use Yii;
use yii\db\Query;
use common\models\ecs\EcsAdminUser;
use common\models\ecs\EcsUsers;

/**
 * This is the model class for table "ecs_return_action".
 *
 * @property integer $action_id
 * @property integer $ret_id
 * @property integer $action_user_type
 * @property integer $action_user_id
 * @property string  $action_user
 * @property integer $return_status
 * @property integer $refund_status
 * @property integer $is_check
 * @property integer $action_place
 * @property string  $action_note
 * @property string  $action_info
 * @property integer $log_time
 */
class EcsReturnAction extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_return_action';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ret_id','action_user_type','action_user_id','action_user','action_info','log_time'], 'required'],
            [['ret_id','action_user_type','action_user_id','return_status','refund_status','is_check','action_place'], 'integer'],
            [['action_user', 'action_note', 'action_info','log_time'], 'string'],
            [['action_note','action_info'], 'string', 'max' => 255],
            [['action_user'], 'string', 'max' => 30],
            [['log_time'], 'string', 'max' => 11],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'action_id' => '操作记录id',
            'ret_id' => '退货单编号',
            'action_user_type' => '用户类型 1-商家 2-客户',
            'action_user_id' => '用户id',
            'action_user' => '用户昵称',
            'return_status' => '退货单状态',
            'refund_status' => '退款状态',
            'is_check' => '审核是否通过',
            'action_place' => 'action_place',
            'action_note' => 'action_note',
            'action_info' => '操作介绍',
            'log_time' => '时间戳'
        ];
    }

    /**
     * 某个订单的售后服务记录
     */
    public static function returnLogsByOrder($orderId, $fields=null) {
        $userId = Yii::$app->user->id;

        if (!$fields) {
            $fields = ['t1.action_user_type', 't1.action_user_id','t1.action_info','t1.log_time'];
        }

        $data = (new Query())->select($fields)->from('ecs_return_action as t1')
                            ->leftJoin('ecs_order_return as t2', 't1.ret_id = t2.ret_id')
                            ->where(['t2.order_id'=>$orderId, 't2.user_id'=>$userId])->all();

        if ($data) {
            foreach ($data as &$item) {
                $item['log_time'] = date('m-d H:i', $item['log_time']);
                if ($item['action_user_type'] == 1) {
                    $item['action_user_info'] = EcsAdminUser::getAdminInfo($item['action_user_id'], ['headimgurl']);
                }else if($item['action_user_type'] == 2) {
                    $item['action_user_info'] = EcsUsers::getUser($item['action_user_id'], ['t2.headimgurl']);
                }
            }
        }

        return $data;
    }

    /**
     * 当前订单是否支持售后
     * @param $order_id 订单ID
     * @return bool true：支持售后 false:不支持售后
     * @author zend.wang
     * @time 2017-04-20 15:00
     */
    public static function isSupportAfterMarket($order_id) {

        $order = (new Query())->select(['order_id','pay_status',
            'order_status','shipping_status'])->from('ecs_order_info')->one();

        if ($order) {
            if ($order['pay_status'] == 2
                && $order['order_status']== 5
                && in_array($order['shipping_status'],[1,2] )) {

                $log_time = (new Query())->select(['log_time'])
                        ->from('ecs_order_action')->where(['shipping_status'=>$order['shipping_status'],
                        'order_id'=>$order_id])->scalar();
                if($log_time) {

                    $service = (new Query())->select(['unreceived_days','received_days'])
                        ->from('ecs_service_type')->where(['service_type'=>[1,3]])->indexBy('service_type')->all();

                    $days = round(((time() - $log_time) / 3600 / 24));

                    if($order['shipping_status'] == 1) {
                        if ($days <= $service[1]['unreceived_days']) {
                            return true;
                        } else if ($days <= $service[1]['unreceived_days']) {
                            return true;
                        }
                    } elseif ($order['shipping_status'] == 2) {
                        if ($days <= $service[3]['unreceived_days']) {
                            return true;
                        } else if ($days <= $service[3]['unreceived_days']) {
                            return true;
                        }
                    }
                }
            }

        }
        return false;
    }
}