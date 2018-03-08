<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_events_apply".
 *
 * @property string $id
 * @property string $events_id
 * @property string $user_id
 * @property integer $num
 * @property string $remark
 * @property string $created_at
 * @property integer $valid
 */
class HllEventsApply extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_events_apply';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['events_id'], 'required'],
            [['events_id', 'user_id', 'num', 'valid'], 'integer'],
            [['created_at'], 'safe'],
            [['remark'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'events_id' => 'Events ID',
            'user_id' => 'User ID',
            'num' => 'Num',
            'remark' => 'Remark',
            'created_at' => 'Created At',
            'valid' => 'Valid',
        ];
    }

    //根据id获取报名列表
    //tpye 1:获取列表  2:获取个人
    public static function getApplyById($id,$apply_list,$type){
        $data = (new Query())->select(['t2.ect_uid','t2.nickname','t2.headimgurl','t1.num','t1.created_at','t1.id','t1.check_status','t1.pay_status'])->from('hll_events_apply as t1')
            ->innerJoin('ecs_wechat_user as t2','t2.ect_uid=t1.user_id')
            ->where(['t1.valid'=>1,'t1.events_id'=>$id,'t1.user_id'=>$apply_list])->orderBy(['t1.created_at'=>SORT_DESC]);
        if($type == 1){
            return $data;
        }else{
            return $data->one();
        }
    }

    public static function getApplyUserInfo($infoList,$eventsId,$ext_field,$userId){

        foreach($infoList as &$item){
            $remark = HllEventsApply::getUserApplyInfo($eventsId,$item['ect_uid']);
            $ext_info = static::getExtInfo($eventsId,$item['ect_uid']);
            if($item['ect_uid'] == $userId){
                $ext_info['is_self'] = 1;
            }else{
                $ext_info['is_self'] = 0;
            }
            $ext_info['total_fee'] = $remark['total_fee'];
            $ext_info['youyuan_fee'] = $remark['youyuan_fee'];
            $ext_info['cash_fee'] = $remark['cash_fee'];
            //个人报名信息
            $user_info = json_decode($remark['remark']);
            if($ext_field && $user_info){
                $arr = objectToArray($ext_field);
                $data_info = [];
                foreach($user_info as $key => $data) {
                    if(isset($arr[$key])){
                        $arr[$key]['value'] = $data;
                        array_push($data_info,$arr[$key]);
                    }
                }
                $item['user_info'] = $data_info;
            }else{
                $item['user_info'] = '';
            }
            $item['ext_info'] = $ext_info;
        }

        return $infoList;
    }

    public static function getExtInfo($eventsId,$user_id){
        $events = HllEvents::findOne($eventsId);
        if($events->creater == $user_id){
            $data['is_admin'] = 1;
        }else{
            $data['is_admin'] = 0;
        }
        if($events->events_type == 'sessions'){
            $sessions_log = HllEventsSessionsLog::find()->select(['sessions_id'])
                ->where(['user_id'=>$user_id,'valid'=>1,'events_id'=>$eventsId])->one();
            $sessions = HllEventsSessions::findOne($sessions_log);
            $data['content'] = $sessions->title.$sessions->content;
        }else{
            $data['content'] = '';
        }
        return $data;
    }

    /**
     * 活动用户报名信息
     * @param $eventsId
     * @param $userId
     * @param string $fields
     * @return array|bool
     * @author zend.wang
     * @time 2017-02-21 15:00
     */
    public static function getApplyInfoByUser($eventsId,$userId,$fields="*") {
        return (new Query())->select($fields)->from('hll_events_apply')
            ->where(['valid'=>1,'events_id'=>$eventsId,"user_id"=>$userId])->one();
    }

    /**
     * 用户参与活动列表
     * @param $userId
     * @return array
     */
    public static function getApplyListByUserId($userId){
        $nowTime = time();

        $fields = ['t2.id','t2.title','t2.thumbnail','t2.events_time','t2.free','t2.fee','t2.official','t2.status','t2.creater', 't2.tel',
            "if(UNIX_TIMESTAMP(t2.end_time) > $nowTime,0,1) as is_past", "if(t2.creater = $userId,1,0) as is_sponsor"];
        $eventList = (new Query())->select($fields)->from('hll_events_apply as t1')
            ->leftJoin('hll_events as t2','t2.id = t1.events_id')
            ->where(['t1.user_id'=>$userId,'t1.valid'=>1,'t2.valid'=>1])
            ->orderBy([ 'is_past' => SORT_ASC,'t1.created_at' => SORT_DESC]);

        return $eventList;
    }

    /**
     * 用户创建活动列表
     * @param $userId
     * @return array
     */
    public static function getCreateListByUserId($userId){
        $nowTime = time();

        $fields = ['t2.id','t2.title','t2.thumbnail','t2.events_time','t2.free','t2.fee','t2.official','t2.status','t2.creater',
            "if(UNIX_TIMESTAMP(t2.end_time) > $nowTime,0,1) as is_past", "if(t2.creater = $userId,1,0) as is_sponsor"];
        $eventList = (new Query())->select($fields)->from('hll_events as t2')
            ->where(['t2.creater'=>$userId,'t2.valid'=>1])
            ->orderBy([ 'is_past' => SORT_ASC,'t2.created_at' => SORT_DESC]);
        return $eventList;
    }

    /**
     * 获取用户报名缴费信息
     * @param $eventId->活动id
     * @param $userId->用户id
     */
    public static function getUserApplyInfo ($eventId, $userId) {
        $remark = HllEventsApply::find()->select(['remark','total_fee','youyuan_fee','cash_fee'])
            ->where(['events_id'=>$eventId,'valid'=>1,'user_id'=>$userId])->asArray()->one();

        $remark = (!empty($remark)) ? $remark : [];

        return $remark;
    }
}
