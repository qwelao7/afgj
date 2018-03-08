<?php

namespace mobile\components\events;

use common\models\hll\HllEventsApplyRefund;
use Yii;
use yii\base\Object;
use yii\base\Exception;
use common\models\hll\HllBill;
use common\models\ar\system\QrCode;
use common\models\hll\HllEvents;
use common\models\hll\HllEventsApply;
use common\models\hll\HllUserPointsLog;
use common\models\hll\HllUserPoints;
/**
 * 报名活动基础类
 * Class ApplyEvents
 * @package mobile\components\events
 */
class ApplyEvents extends Object {

    public $eventsId;
    public $events;
    public $userId;
    public $pay;
    public $fee;
    public $free;
    public $point;
    public $communityId;
    protected $num;
    public $extFields;
    public $code;
    public static $events_id = [80];

    public function init()
    {
        parent::init();
        $this->num = 1;
        $this->events = HllEvents::findOne(['id'=>$this->eventsId,'valid'=>1]);
        if(!$this->events) {
            throw new Exception("活动不存在", 101);
        }

    }

    public function validate() {
        //当前活动是否结束
        $currentDate = f_date(time());

        if($currentDate > $this->events->deadline ) {
            throw new Exception("当前活动报名已结束", 106);
        }
        //报名人数参数校验
        if($this->extFields) {
            if(array_key_exists("num",$this->extFields) && is_int($this->extFields['num']) && $this->events->ext_fields) {
                $this->num = intval($this->extFields['num']);
//                $eventsExtFieldsModel = json_decode($this->events->ext_fields,true);
//                if($this->num < $eventsExtFieldsModel['num']['min']) {
//                    throw new Exception("报名人数不满足本次活动条件", 102);
//                }
            }
            if(array_key_exists("_idcard",$this->extFields) && in_array($this->eventsId,static::$events_id)){
                $year = intval(substr($this->extFields['_idcard'],6,4));
                $now = intval(date("Y",time()));
                $time = $now - $year;
                if($time > 13 || $time < 5){
                    throw new Exception("本次活动只接受5-12岁的小朋友报名", 102);
                }
            }
        }

        //当前活动报名人数已达上限
        $this->events->joined_num += $this->num;
        if($this->events->events_num > 0 && $this->events->events_num < $this->events->joined_num) {
            throw new Exception("当前活动报名人数已达上限", 104);
        }
        //当前用户是否已报名
        if(HllEventsApply::getApplyInfoByUser($this->eventsId,$this->userId,"id")) {
            throw new Exception("当前活动已报名，无需重复提交", 105);
        }
        return true;
    }

    public function apply() {

        $trans = Yii::$app->db->beginTransaction();
        try{
            if($this->events->save()){
                $apply = new HllEventsApply();
                $apply->events_id = $this->eventsId;
                $apply->user_id = $this->userId;
                $apply->num = $this->num;
                $apply->total_fee = $this->fee;
                $apply->youyuan_fee = $this->point;
                $apply->cash_fee = $this->pay;
                $apply->pay_status = 2;
                $apply->sign_in_code = $this->code;
                $this->extFields && $apply->remark = json_encode($this->extFields,JSON_UNESCAPED_UNICODE);
                if($this->events->free == 0 && (float)$this->pay > 0) {
                    $apply->valid = 0;
                    $apply->pay_status = 1;
                    $this->events->joined_num -= $this->num;
                    if($apply->save() && $this->events->save()){
                        $bill = new HllBill();
                        $bill->title = $this->events->title;
                        $bill->user_id = $this->userId;
                        $bill->bill_sn = $apply->id;
                        $bill->bill_category = 2;
                        $bill->pay_id = 1;
                        $bill->pay_name = '微信支付';
                        $bill->point = $this->point * 100;
                        $bill->point_money = $this->point;
                        $bill->bill_amount = $this->fee;
                        $bill->money_paid = $this->pay;
                        if ($bill->save()) {
                            $trans->commit();
                            $info['id'] = $apply->id;
                            $info['way'] = 'weixin';
                            $info['point'] = 0;
                            return $info;//报名成功
                        } else {
                            throw new Exception("添加账单信息失败", 113);
                        }
                    }else{
                        throw new Exception("添加报名信息失败", 114);
                    }
                }
                else if($this->events->free == 0 && (float)$this->pay == 0 && (float)$this->point > 0){
                    $data['item_id'] = $this->eventsId;
                    $data['icon'] = 'http://pub.huilaila.net/'.$this->events->thumbnail;
                    $data['category'] = 'events';
                    $data['change_type'] = HllUserPointsLog::EXPEND_POINT_TYPE;
                    $data['income_type'] = HllUserPointsLog::INCOME_POINT_TYPE;
                    $data['scenes'] = HllUserPointsLog::$scenes_type[2];
                    $data['change_reason'] =$this->events->title.'活动消费';
                    $data['income_reason'] = $this->events->title.'活动收入';
                    $data['creater'] = $this->events->creater;

                    $community_id = HllUserPoints::getCommunityByEvents($this->events->accept_point_community_id,$this->communityId);
                    $unique_id = HllUserPoints::eventsPoints($this->userId, $this->point*100,$data,$community_id);

                    if($apply->save()){
                        $bill = new HllBill();
                        $bill->title = $this->events->title;
                        $bill->user_id = $this->userId;
                        $bill->bill_sn = $apply->id;
                        $bill->bill_category = 2;
                        $bill->pay_id = 1;
                        $bill->pay_name = '微信支付';
                        $bill->point = $this->point * 100;
                        $bill->point_money = $this->point;
                        $bill->bill_amount = $this->fee;
                        $bill->money_paid = $this->pay;
                        $bill->pay_time = date("Y-m-d H:i:s");
                        $bill->pay_status = 2;
                        $bill->save();
                        $trans->commit();
                        $info['id'] = $apply->id;
                        $info['way'] = 'youyuan';
                        $info['point'] = $this->point*100;
                        $info['unique_id'] = $unique_id;
                        return $info;//报名成功
                    }
                    else{
                        throw new Exception("添加报名信息失败", 115);
                    }
                }
                else{
                    if ($apply->save()) {
                        $trans->commit();
                        $info['id'] = $apply->id;
                        $info['way'] = '';
                        $info['point'] = 0;
                        return $info;//报名成功
                    } else {
                        throw new Exception("添加账单信息失败", 113);
                    }
                }
            }else{
                throw new Exception("修改报名人数失败", 111);
            }
        }catch (\yii\db\Exception $e){
            $trans->rollBack();
            throw new Exception($e->getName(), $e->getCode());
        }
    }

    /**
     * 取消报名
     * @return bool
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function applyCancel(){
        $events_id = $this->eventsId;
        $user_id = $this->userId;
        $event_apply = HllEventsApply::find()->where(['events_id'=>$events_id,'user_id'=>$user_id,'valid'=>1])->one();
        $this->num = intval($event_apply->num);
        $trans = Yii::$app->db->beginTransaction();
        try{
            //是否需要退费
            if($this->free == 0 && $this->pay > 0){
                $event_apply->pay_status = 3;
                $apply_refund = new HllEventsApplyRefund();
                $apply_refund->user_id = $user_id;
                if((float)$event_apply->cash_fee < (float)$this->pay){
                    $apply_refund->fee = $event_apply->cash_fee;
                    $apply_refund->point = ($this->pay - $event_apply->cash_fee)*100;
                }else{
                    $apply_refund->fee = $this->pay;
                    $apply_refund->point = 0;
                }
                $apply_refund->apply_id = $event_apply->id;
                $apply_refund->save();
            }else{
                $event_apply->valid = 0;
                $this->events->joined_num -= $this->num;
            }
            if($event_apply->save() && $this->events->save()){
                $trans->commit();
                return true;
            }else{
                throw new Exception("修改报名记录失败!", 101);
            }
        }catch (\yii\db\Exception $e){
            $trans->rollBack();
            throw new Exception("取消报名失败!", 104);
        }
    }

}