<?php

namespace mobile\components\events;

use Yii;
use yii\base\Exception;
use common\models\hll\HllEventsSessions;
use common\models\hll\HllEventsSessionsLog;
/**
 * 场次报名活动类
 * Class ApplyEvents
 * @package mobile\components\events
 */
class SessionsApplyEvents extends ApplyEvents {

    public $sessionsId;
    private $sessions;

    public function init()
    {
        parent::init();

        $this->sessions = HllEventsSessions::findOne(['id'=>$this->sessionsId,'events_id'=>$this->eventsId,'valid'=>1]);
        if(!$this->sessions) {
            throw new Exception("活动场次不存在", 101);
        }

    }
    public function validate() {

        parent::validate();

        $this->sessions = HllEventsSessions::findOne($this->sessionsId);
        $this->sessions->joined_num += $this->num;
        if($this->sessions->sessions_num < $this->sessions->joined_num){
            throw new Exception("当前活动本场次报名人数已满", 107);
        }
        return true;
    }
    public function apply() {
        $info = parent::apply();

        if($info['way'] == 'weixin'){
            $this->sessions->joined_num -= $this->num;
        }
        $trans = Yii::$app->db->beginTransaction();
        try{
            if($this->sessions->save()){
                $sessions_log = new HllEventsSessionsLog();
                $sessions_log->sessions_id = $this->sessionsId;
                $sessions_log->events_id = $this->eventsId;
                $sessions_log->user_id = $this->userId;
                if($sessions_log->save()){
                    $trans->commit();

                    return $info;
                }else{
                    throw new Exception("添加报名场次信息保存失败", 110);
                }
            }else{
                throw new Exception("修改活动场次报名人数失败", 111);
            }
        }catch (\yii\db\Exception $e){
            $trans->rollBack();
            throw new Exception("场次报名失败", 112);
        }
    }

    public function applyCancel(){
        parent::applyCancel();

        if($this->free == 1 || ($this->pay == 0 && $this->point == 0)){
            $events_id = $this->eventsId;
            $user_id = $this->userId;
            $trans = Yii::$app->db->beginTransaction();
            try{
                $session_log = HllEventsSessionsLog::find()->where(['events_id'=>$events_id,'user_id'=>$user_id,'valid'=>1])->orderBy(['id'=>SORT_DESC])->one();
                $session = HllEventsSessions::findOne($session_log->sessions_id);
                $session_log->valid = 0;
                $session->joined_num -= $this->num;
                if($session->save() && $session_log->save()){
                    $trans->commit();
                    return true;
                }else{
                    throw new Exception("修改场次数目失败!", 102);
                }
            }catch (\yii\db\Exception $e){
                $trans->rollBack();
                throw new Exception("场次报名失败", 103);
            }
        }else{
            return true;
        }
    }

}