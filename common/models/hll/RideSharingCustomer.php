<?php

namespace common\models\hll;

use Yii;
use common\models\ecs\EcsWechatUser;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_ride_sharing_customer".
 *
 * @property integer $id
 * @property integer $rs_id
 * @property integer $account_id
 * @property integer $customer_num
 * @property string $thanks_word
 * @property integer $thanks_point
 * @property integer $status
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class RideSharingCustomer extends ActiveRecord
 {
    //微信模板提醒日志
    const EVENT_ADD_WX_TPL_MSG = 'joinWxTemplateMessageQueue';
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'hll_ride_sharing_customer';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['rs_id', 'account_id', 'customer_num', 'thanks_point', 'status', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['thanks_word'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '顺风车乘客信息编号',
            'rs_id' => '顺风车信息编号',
            'account_id' => '用户编号',
            'customer_num' => '乘客数量',
            'thanks_word' => '感谢的话',
            'thanks_point' => '感谢积分',
            'status' => '状态：1、已预约，2已取消，3已感谢',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    public static function getUserInfo($id){
        $result = array();
        $customer = RideSharingCustomer::findAll(['rs_id'=>$id,'valid'=>1,'status'=>1]);
        foreach($customer as $item){
            $result[] = (new Query())->select(['openid','ect_uid'])
                ->from('ecs_wechat_user')
                ->where(['ect_uid'=>$item['account_id']])->one();
        }
        return $result;
    }
    public function afterSave($insert, $changedAttributes) {
        if ($insert) {
            $this->on(self::EVENT_ADD_WX_TPL_MSG,[$this,'joinWxTemplateMessageQueue']);
            $this->trigger(self::EVENT_ADD_WX_TPL_MSG);
        }
    }
    /**
     * 用户确定拼车后,加入到微信提醒队列
     * 1.如果出发前5分钟,发提醒
     * 2.如果出发后30分钟,提醒感谢
     * @param $rsId 当前发布顺风车记录ID
     * @param $customerId 客户ID
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function joinWxTemplateMessageQueue($event) {
        $redis = Yii::$app->redis;
        $redis->rpush("ride_sharing_remind","{$event->sender->rs_id}:{$event->sender->account_id}");
    }
}
