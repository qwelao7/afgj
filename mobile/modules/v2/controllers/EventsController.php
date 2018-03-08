<?php
namespace mobile\modules\v2\controllers;

use common\models\ar\system\QrCode;
use common\models\ecs\EcsAccountLog;
use common\models\ecs\EcsUsers;
use common\models\event\SignModel;
use common\models\hll\HllBill;
use common\models\hll\HllBillRefund;
use common\models\hll\HllEventCheckLog;
use common\models\hll\HllEventKidContactCard;
use common\models\hll\HllEventsApplyRefund;
use common\models\hll\HllEventsCommunity;
use common\models\hll\HllEventSignInLog;
use common\models\hll\HllEventsSessions;
use common\models\hll\HllEventsSessionsLog;
use common\models\hll\HllEventUsercommit;
use common\models\hll\HllEventWorker;
use common\models\hll\HllUserPoints;
use common\models\hll\ItemSharingThanks;
use Doctrine\Common\Annotations\AnnotationException;
use Yii;
use yii\db\Query;
use common\models\hll\HllEvents;
use common\models\hll\HllEventsApply;
use common\models\hll\HllEventsComment;
use common\models\hll\UserAddress;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiData;
use mobile\modules\v2\models\ApiResponse;
use common\components\WxpayV2;
use yii\base\Exception;
use yii\log\Logger;

use common\components\WxTmplMsg;
/**
 * 官方活动
 * Created by PhpStorm.
 * User: kaikai.qin
 * Date: 2017/2/17
 * Time: 16:48
 */
class EventsController extends ApiController
{

    //活动详情
    public function actionDetail($id){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;

        $info = HllEvents::find()->where(["id"=>$id])->one();

        $time = time();
        $end_time = strtotime($info->end_time);
        $deadline = strtotime($info->deadline);
        $is_end = ($end_time > $time) ? 1 : 0;
        if(!$is_end && $info->status == 1) {
            $info->status=2;
            $info->update(false);
        }
        $info->status = $deadline > $time ? $info->status : 5;
        $info=$info->attributes;
        $info['nickname'] = (new Query())->select('nickname')->from('ecs_wechat_user')->where( ["ect_uid"=> $info['creater']])->scalar();
        $info['is_end'] = $is_end;
        $info['status_desc'] = HllEvents::$event_status_list[$info['status']];
        $info['is_join'] = (bool)HllEventsApply::find()->where(['events_id'=>$id,'user_id'=>$user_id,'valid'=>1])->count();
        //是否待退款
        $info['is_refund'] = HllEventsApply::find()->select(['pay_status'])->where(['events_id'=>$id,'user_id'=>$user_id,'valid'=>1])->scalar();
        if($info['is_refund'] == 2){
            $info['refund_desc'] = '';
        }else{
            $info['refund_desc'] = '待退款';
        }
        $info['is_full'] = ($info['events_num'] > 0 && $info['joined_num'] == $info['events_num']) ? 1 : 0;
        $begin_time = strtotime($info['begin_time']);
        $info['is_begin'] = ($time > $begin_time) ? 1 : 0;

        if($info['events_num'] > 0) {
            $info['signup_stat'] = "已报名人{$info['joined_num']} / 限{$info['events_num']}人报名";
        } else {
            $info['signup_stat'] = '报名人数不限';
        }

        //感谢人数
        $info['thanks_num'] = (new Query())->from('hll_item_sharing_thanks')
            ->where(['is_id'=>$id, 'type_id'=>2, 'valid'=>1])->count();

        $event_worker = (new Query())->from('hll_event_worker')->where(['event_id'=>$id,'user_id'=>$user_id,'valid'=>1])->count();
        //1为创建者  2为管理者  3为普通用户
        if ($info['creater'] == Yii::$app->user->id) {
            $info['isself'] = 1;
        } else if($event_worker > 0) {
            $info['isself'] = 2;
        }else{
            $info['isself'] = 3;
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //活动评论表
    public function actionCommentList(){
        $response = new ApiResponse();
        $id = f_get('id',0);
        $page = f_get('page',0);
        $sql = HllEventsComment::getCommentByEventsId($id);
        $info = $this->getDataPage($sql,$page);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //活动参与表
    public function actionApplyList(){
        $response = new ApiResponse();
        $id = f_get('id',0);
        $page = f_get('page',1);
        $userId = Yii::$app->user->id;
        $eventsId = HllEvents::find()->select(['creater','ext_fields'])->where(['id'=>$id,'valid'=>1])->asArray()->one();
        //查找本人是否已经报名
        $user_apply = HllEventsApply::find()->where(['user_id'=>$userId,'events_id'=>$id,'valid'=>1])->count();
        $apply_list = HllEventsApply::find()->select(['user_id'])->where(['events_id'=>$id,'valid'=>1])->column();
        if($user_apply){
            $user_apply = HllEventsApply::getApplyById($id,$userId,2);
            if (!empty($eventsId['ext_fields'])) {
                $user_apply['ext_field'] = json_decode($eventsId['ext_fields']);
            }else{
                $user_apply['ext_field'] = '';
            }
            //将一维数组变为二维数组
            $list[] = $user_apply;
            $user_apply = HllEventsApply::getApplyUserInfo($list,$id,$user_apply['ext_field'],$userId);
            //将二维数组变回一维数组
            $user_apply = $user_apply[0];
            //是否已经退款
            $refund = (new Query())->select(['id','point','fee','check_user','check_reason','status'])->from('hll_events_apply_refund')
                ->where(['apply_id'=>$user_apply['id'],'valid'=>1])->orderBy(['id'=>SORT_DESC])->one();

            // 退款申请
            if($refund){
                $user_apply['ext_info']['is_refund'] = 1;
                $user_apply['ext_info']['refund'] = $refund;
            }else{
                $user_apply['ext_info']['is_refund'] = 0;
            }
            unset($user_apply['ext_field']);

            //获取提出本人以后的列表
            $index = array_search($userId,$apply_list);
            unset($apply_list[$index]);
            $sql = HllEventsApply::getApplyById($id,$apply_list,1);
            $info = $this->getDataPage($sql,$page,5);
        }else{
            $sql = HllEventsApply::getApplyById($id,$apply_list,1);
            $info = $this->getDataPage($sql,$page,5);
        }
        //判断本人是否是创建者
        if($userId == $eventsId['creater']){
            if (!empty($eventsId['ext_fields'])) {
                $info['ext_field'] = json_decode($eventsId['ext_fields']);
            }else{
                $info['ext_field'] = '';
            }
            $info['list'] = HllEventsApply::getApplyUserInfo($info['list'],$id,$info['ext_field'],$userId);
            foreach($info['list'] as &$item){
                $refund = (new Query())->select(['id','point','fee','check_user','check_reason','status'])->from('hll_events_apply_refund')
                    ->where(['apply_id'=>$item['id'],'valid'=>1])->orderBy(['id'=>SORT_DESC])->one();

                // 退款申请
                if($refund){
                    $item['ext_info']['refund'] = $refund;
                    $item['ext_info']['is_refund'] = 1;
                }else{
                    $item['ext_info']['is_refund'] = 0;
                }
            }
            unset($info['ext_field']);
        }
        //如果本人已报名，就将数据添加到列表中
        if($user_apply && $page == 1){
            array_unshift($info['list'],$user_apply);
        }
        $check = HllEvents::find()->select(['apply_check'])->where(['id'=>$id,'valid'=>1])->scalar();
        foreach ($info['list'] as &$item) {
            if (isset($item['check_status'])) {
                if($check == 1 && $item['check_status'] == 0){
                    $item['is_check'] = 0;
                }else{
                    $item['is_check'] = 1;
                }
            }
        }

        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //活动列表
    public function actionList(){
        $response = new ApiResponse();
        $community_id = f_get('community_id',0);
        $page = f_get('page',0);
        $sql = HllEvents::getActivityList($community_id);
        $info = $this->getDataPage($sql,$page);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //活动留言
    public function actionComment(){
        $response = new ApiResponse();
        $content = f_post('content',0);
        $event_id = f_post('event_id',0);

        $events = HllEvents::findOne($event_id);
        if($events->status <> 1 || $events->valid <> 1) {
            $response->data = new ApiData(108,'当前活动不能留言！');
            return $response;
        }
        $model = new HllEventsComment();
        $model->content = $content;
        $model->events_id = $event_id;
        $trans = Yii::$app->db->beginTransaction();
        try{
            if($model->validate() && $model->save()){
                $event = HllEvents::findOne($event_id);
                $event->comment_num +=1;
                if($event->save()){
                    $response->data = new ApiData('0','添加留言成功！');
                    $trans->commit();
                    $response->data->info = $model->id;
                }else{
                    $response->data = new ApiData('101','增加评论数失败！');
                }
            }else{
                $response->data = new ApiData('102','添加失败！');
            }
        }catch (\yii\db\Exception $e){
            $response->data = new ApiData('103','添加失败！');
            $trans->rollBack();
        }
        return $response;
    }

    //活动富文本
    public function actionDesc($id) {
        $response = new ApiResponse();
        $info = HllEvents::getActivityDetailById($id, ['t1.content']);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //活动报名详情
    public function actionApplyDetail($id){
        $response = new ApiResponse();
        $events = HllEvents::getEvents($id,['title','events_time','ext_fields','events_type', 'events_num', 'joined_num','free','fee','apply_check','apply_tip','accept_point_community_id','accept_point', 'business_id']);
        $events['ext_fields'] = ($events['ext_fields'] != '') ? json_decode($events['ext_fields']) : [];
        if($events['events_type'] == 'sessions'){
            $events['sessions'] = HllEventsSessions::find()->select(['id','title','content','sessions_num','joined_num'])
                ->where(['events_id'=>$id,'valid'=>1])->asArray()->all();
        }
        if($events['accept_point'] == 1){
            if ($events['accept_point_community_id'] == 0) {
                $community_id = null;
            } else {
                $community_id = explode(',',$events['accept_point_community_id']);

                if (!in_array('0', $community_id)) {
                    array_push($community_id, '0');
                }
            }

            $events['point'] = HllUserPoints::getUserPoints(Yii::$app->user->id, $community_id, 2);
        }
        $response->data = new ApiData();
        $response->data->info = $events;
        return $response;
    }

    //报名
    public function actionApply(){
        $response = new ApiResponse();

        $config['userId'] = Yii::$app->user->id;
        $config['eventsId'] = f_post('id',0);
        $config['point'] = f_post('point',0);
        $config['fee'] = f_post('fee',0);
        $config['pay'] = f_post('pay',0);
        $config['extFields'] = json_decode(urldecode(f_post('data',false)),true);
        $str = null;
        $strPol = "0123456789";
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < 5; $i++) {
            $str .= $strPol[rand(0, $max)];
        }
        $config['code'] = $str;
        try{
            $events = HllEvents::findOne($config['eventsId']);
            if($events->auth_way == 'community'){
                $config['communityId'] = (new Query())->select(['community_id'])
                    ->from('hll_events_community')->where(['events_id'=>$config['eventsId'],'valid'=>1])->scalar();
            }else{
                $config['communityId'] = 0;
            }
            if($events->status <> 1 || $events->valid <> 1) {
                throw  new Exception("当前活动不能进行报名",108);
            }
            if($events->events_type == 'sessions'){
                $config['sessionsId'] = f_post('sessions_id',0);
            }

            $applyClass= "mobile\\components\\events\\".ucfirst($events->events_type)."ApplyEvents";
            $applyEventObj = new $applyClass($config);
            if($applyEventObj->validate()) {
                $info = $applyEventObj->apply();
                if($info){
                    if($info['way'] == 'weixin'){
                        $response->data = new ApiData();
                        $response->data->info['id'] =  $info['id'];
                    }else{
                        if($events->apply_check == 1){
                            if($info['point'] > 0){
                                $unique_id = $info['unique_id'];
                            }else{
                                $unique_id = 0;
                            }
                            $creater = EcsUsers::getUser($events->creater,['t1.user_id', 't2.openid']);
                            WxTmplMsg::EventCheckNotice($creater, $info['id'], $events->title,$unique_id);
                            $response->data = new ApiData(0,'等待管理员审核！');
                            $response->data->info['id'] = $info['id'];
                        }else{
                            $user_id = Yii::$app->user->id;
                            $user = EcsUsers::getUser($user_id, ['t1.user_id', 't2.openid']);
                            WxTmplMsg::EventApplyNotice($user, $events->id,$events->title);
                            if($info['point'] > 0){
                                //友元变动
                                $left_point = HllUserPoints::getUserPoints($user_id);
                                $user = EcsUsers::getUser($user_id, ['t1.user_id', 't2.openid','t2.nickname']);
                                $title = $events->title.'活动消费';
                                WxTmplMsg::PointChangeNotice($user,$info['point'],$left_point,$title);
                                $creater = EcsUsers::getUser($events->creater, ['t1.user_id', 't2.openid','t2.nickname']);
                                $title = $events->title.'活动收入';
                                $left_point = HllUserPoints::getUserPoints($events->creater);
                                WxTmplMsg::PointChangeNotice($creater,$info['point'],$left_point,$title);
                            }
                            $response->data = new ApiData(0,'报名成功！');
                            $response->data->info['id'] =  $info['id'];
                        }
                    }
                }
            }
        }catch (Exception $e) {
            // 记录报名失败日志
            $curUser = EcsUsers::getUser($config['userId'], ['t2.nickname']);
            $logStr = $curUser['nickname'].'报名'.$events['title'].'失败!失败原因:'.$e->getMessage();
            Yii::error($logStr, 'event');

            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }

        return $response;
    }

    //活动审核详情
    public function actionApplyCheck($id)
    {
        $response = new ApiResponse();

        $apply = HllEventsApply::find()->where(['id' => $id])->asArray()->one();
        if ($apply) {
            $log = HllEventCheckLog::find()->select(['check_status', 'fail_reason'])
                ->where(['user_id' => $apply['user_id'], 'apply_id' => $id, 'valid' => 1])->asArray()->one();
            if ($log) {
                $apply['status'] = $log['check_status'] == 1 ? '通过' : '未通过';
                $apply['fail_reason'] = $log['fail_reason'];
                $apply['is_handle'] = 1;
            } else {
                $apply['is_handle'] = 0;
            }
            $ext_fields = HllEvents::find()->select(['ext_fields'])->where(['id' => $apply['events_id']])->scalar();
            if ($ext_fields) {
                $ext_fields = json_decode($ext_fields);
                $remark = json_decode($apply['remark']);
                if (array_key_exists('points', $remark)) {
                    unset($remark->points);
                }
                if (array_key_exists('num', $remark)) {
                    unset($remark->num);
                }
                foreach ($remark as $key => $val) {
                    $ext_fields->$key->value = $val;
                }
                foreach ($ext_fields as $key => $val) {
                    if ($ext_fields->$key->type == 'pics') {
                        $ext_fields->$key->value = explode(',', $ext_fields->$key->value);
                    }
                }
                $apply['remark'] = $ext_fields;
            }
        $response->data = new ApiData();
        $response->data->info = $apply;
    }
        return $response;
    }

    //活动审核结果
    public function actionApplyCheckResult(){
        $response = new ApiResponse();

        $id = f_get('id',0);
        $unique_id = f_get('unique_id','0');
        $user_id = f_get('user_id',0);
        $events_id = f_get('events_id',0);
        $check_status = f_get('check_status',2);  //默认审核不通过
        $fail_reason = f_get('fail_reason','');

        $apply = HllEventsApply::findOne($id);
        if(!$apply){
            $response->data = new ApiData(101,'数据错误');
        }else{
            $apply->check_status = $check_status;
            $apply->valid = ($check_status == 1) ? 1 : 0;
            $check_log = new HllEventCheckLog();
            $check_log->user_id = $user_id;
            $check_log->events_id = $events_id;
            $check_log->apply_id = $id;
            $check_log->check_status = $check_status;
            $check_log->fail_reason = $fail_reason;
            $event = HllEvents::findOne($events_id);
            if($check_status == 2){
                $event->joined_num -= $apply->num;
                $event->save();
                $title = HllEvents::find()->select(['title'])->where(['id'=>$events_id,'valid'=>1])->scalar();
                $user = EcsUsers::getUser($user_id, ['t1.user_id', 't2.openid']);
                WxTmplMsg::EventCheckResult($user, $title,$fail_reason);
            }else{
                //友元变动
                if($unique_id != '0'){
                    $point = (new Query())->select(["SUM(point) as point",'user_id'])->from('hll_user_points_log')
                        ->where(['unique_id' => $unique_id, 'user_id'=>$user_id, 'valid' => 1])->one();
                    $left_point = HllUserPoints::getUserPoints($user_id);
                    $user = EcsUsers::getUser($point['user_id'], ['t1.user_id', 't2.openid','t2.nickname']);
                    $title = $event->title.'活动消费';
                    WxTmplMsg::PointChangeNotice($user,$point['point'],$left_point,$title);
                    $creater = EcsUsers::getUser($event->creater, ['t1.user_id', 't2.openid','t2.nickname']);
                    $title = $event->title.'活动收入';
                    $left_point = HllUserPoints::getUserPoints($event->creater);
                    WxTmplMsg::PointChangeNotice($creater,$point['point'],$left_point,$title);
                }
                $user = EcsUsers::getUser($user_id, ['t1.user_id', 't2.openid']);
                WxTmplMsg::EventApplyNotice($user, $event->id,$event->title);
            }
            if($apply->save() && $check_log->save()){
                $response->data = new ApiData(0,'审核成功');
            }else{
                $response->data = new ApiData(102,'审核失败');
            }
        }
        return $response;
    }

    //报名时花的费用
    public function actionApplyPayDetail($events_id){
        $response = new ApiResponse();

        $apply = (new Query())->select(['total_fee','youyuan_fee','cash_fee'])->from('hll_events_apply')
            ->where(['user_id'=>Yii::$app->user->id,'events_id'=>$events_id,'valid'=>1])->one();
        $response->data = new ApiData();
        $response->data->info = $apply;
        return $response;
    }

    //取消报名
    public function actionApplyCancel(){
        $response = new ApiResponse();
        $config['eventsId'] = f_get('events_id',0);
        $config['userId'] = Yii::$app->user->id;
        $event_apply = HllEventsApply::find()->where(['events_id'=>$config['eventsId'],'user_id'=>$config['userId'],'valid'=>1])->one();
        if(!$event_apply){
            $response->data = new ApiData(101,'无报名记录！');
            return $response;
        }
        $events = HllEvents::findOne($config['eventsId']);
        //是否需要付费
        if($events->free == 1){
            $config['free'] = 1;
        }else{
            $config['free'] = 0;
            $config['pay'] = f_get('pay',0);
        }
        //是否有场次
        if($events->events_type == 'sessions'){
            $session_log = HllEventsSessionsLog::find()->where(['events_id'=>$config['eventsId'],'user_id'=>$config['userId'],'valid'=>1])->one();
            if(!$session_log){
                $response->data = new ApiData(102,'无报名记录！');
                return $response;
            }
            $config['sessionsId'] = $session_log->sessions_id;
        }
        try{
            $applyClass= "mobile\\components\\events\\".ucfirst($events->events_type)."ApplyEvents";
            $applyEventObj = new $applyClass($config);
            if($applyEventObj->applyCancel()) {
                $response->data = new ApiData(0,'取消报名成功！');
                if($config['free'] == 1 || ($config['free'] == 0 && $config['pay'] == 0)){
                    $user = EcsUsers::getUser($config['userId'],['t1.user_id', 't2.openid']);
                    WxTmplMsg::EventCancelNotice($user,$events->id,$events->title,1);
                }else{
                    $user = EcsUsers::getUser($events->creater,['t1.user_id', 't2.openid']);
                    $user['apply_id'] = $event_apply->id;
                    $refund_id = (new Query())->select(['id'])->from('hll_events_apply_refund')
                        ->where(['apply_id'=>$user['apply_id'],'status'=>1,'valid'=>1])->scalar();
                    $user['refund_id'] = $refund_id;
                    WxTmplMsg::EventCancelNotice($user,$events->id,$events->title,4);
                    $applyer = EcsUsers::getUser($config['userId'],['t1.user_id', 't2.openid']);
                    WxTmplMsg::EventCancelNotice($applyer,$events->id,$events->title,5);
                }
            }
        }catch (Exception $e) {
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    /**
     * 取消报名审核页面
     * @params $apply_id -> apply_id
     * @params $refund_id ->refund_id
     * @return ApiResponse
     */
    public function actionApplyCancelDetail($apply_id, $refund_id){
        $response = new ApiResponse();

        $refund = (new Query())->select(['point','fee','id','status', 'check_reason'])->from('hll_events_apply_refund')
            ->where(['apply_id'=>$apply_id,'id'=>$refund_id,'valid'=>1])->one();
        $apply = HllEventsApply::find()->select(['total_fee', 'youyuan_fee', 'cash_fee', 'events_id'])->where(['id' => $apply_id])->one();

        if(!$refund || !$apply){
            $response->data = new ApiData(101,'数据错误');
        }else{
            $events = HllEvents::findOne($apply->events_id);
            if($events->events_type == 'sessions'){
                $events_title = (new Query())->select(['t2.title','t2.content'])->from('hll_events_sessions_log as t1')
                    ->leftJoin('hll_events_sessions as t2','t2.id = t1.sessions_id')
                    ->where(['events_id'=>$apply->events_id,'valid'=>1])->one();
                $refund['title'] = $events_title['title'].' '.$events_title['content'];
            }else{
                $refund['title'] = $events->title;
            }
        }

        // 报名人信息
        $applyer = HllEventsApply::find()->select(['user_id'])->where(['id' => $apply_id])->one();
        $users = EcsUsers::getUser($applyer, ['t2.nickname', 't2.headimgurl']);

        // 是否已经处理过
        $params['is_handle'] = ($refund['status'] != 1) ? true : false;

        $response->data = new ApiData();
        $response->data->info['refund'] = $refund;
        $response->data->info['user'] = $users;
        $response->data->info['apply'] = $apply;
        $response->data->info['params'] = $params;
        return $response;
    }

    //取消报名审核
    public function actionApplyCancelOperate(){
        $response = new ApiResponse();
        $refund_id = f_post('id',0);
        $reason = f_post('reason','');
        $status = f_post('status',3);
        $user_id = Yii::$app->user->id;

        $refund = HllEventsApplyRefund::findOne($refund_id);
        if(!$refund){
            $response->data = new ApiData(101,'数据错误');
            return $response;
        }else{
            $refund->check_user = $user_id;
            $refund->check_reason = $reason;
            $refund->status = $status;
            $trans = Yii::$app->db->beginTransaction();
            try{
                if($refund->save()){
                    $apply = HllEventsApply::findOne($refund->apply_id);
                    $events = HllEvents::findOne($apply->events_id);
                    if($status == 2){
                        $apply->pay_status = 4;
                        $apply->valid = 0;
                        if($apply->save()){
                            if($events->events_type == 'sessions'){
                                $session_log = HllEventsSessionsLog::find()->where(['events_id'=>$apply->events_id,'user_id'=>$refund->user_id,'valid'=>1])
                                    ->orderBy(['id'=>SORT_DESC])->one();
                                $session = HllEventsSessions::findOne($session_log->sessions_id);
                                $session->joined_num -= $apply->num;
                                $session->save();
                            }
                            $events->joined_num -= $apply->num;
                            if($events->save()){
                                $trans->commit();
                                $response->data = new ApiData();
                                $response->data->info = $events->joined_num;
                                $applyer = EcsUsers::getUser($refund->user_id,['t1.user_id', 't2.openid']);
                                WxTmplMsg::EventCancelNotice($applyer,$events->id,$events->title,$status);
//                                $admin = EcsUsers::getUser(897,['t1.user_id', 't2.openid']);
//                                WxTmplMsg::EventCancelNotice($admin,$events->id,$events->title,6);
                            }
                            else{
                                throw new Exception('修改报名人数失败',  103);
                            }
                        }else{
                            throw new Exception('修改报名信息失败',104);
                        }
                    }else{
                        $apply->pay_status = 2;
                        if($apply->save()){
                            $trans->commit();
                            $response->data = new ApiData();
                            $applyer = EcsUsers::getUser($refund->user_id,['t1.user_id', 't2.openid']);
                            WxTmplMsg::EventCancelNotice($applyer,$events->id,$events->title,$status);
                        }else{
                            throw new Exception('修改报名信息失败',105);
                        }
                    }
                }
                else{
                    throw new Exception('修改数据失败',102);
                }
            }catch (Exception $e){
                $trans->rollBack();
                $response->data = new ApiData($e->getCode(),$e->getMessage());
            }
            return $response;
        }
    }

    //我参与的活动
    public function actionSignup(){
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        $page = f_get('page',1);
        $eventList = HllEventsApply::getApplyListByUserId($userId);
        $info = $this->getDataPage($eventList,$page);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //我发起的活动
    public function actionLaunch(){
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        $page = f_get('page',1);
        $eventList = HllEventsApply::getCreateListByUserId($userId);
        $info = $this->getDataPage($eventList,$page);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //创建或更新活动
    public function actionCreate(){
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        $events_data = f_post();
        try{
            $info = HllEvents::createOrUpdateApply($events_data);
            if($info){
                $response->data = new ApiData(0,'创建活动成功');
            }
        }catch(Exception $e){
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    //要更新的活动信息
    public function actionInfo($id){
        $response = new ApiResponse();
        $info['events'] = HllEvents::find()->select('*')
            ->where(['id'=>$id,'valid'=>1])->asArray()->one();
        if(!$info['events']){
            $response->data = new ApiData(101,'无此活动！');
        }else{
            if($info['events']['auth_way']=='community'){
                $info['community'] = (new Query())->select(['t1.community_id','t2.name'])
                    ->from('hll_events_community as t1')
                    ->leftJoin('hll_community as t2','t2.id = t1.community_id')
                    ->where(['t1.events_id'=>$info['events']['id'],'t1.valid'=>1,'t2.valid'=>1])
                    ->one();
            }
            $year = date('Y').'-';
            $info['events']['begin_time'] = $year.substr($info['events']['events_time'],0,11).':00';
            $info['events']['accept_point_community_id'] = in_array($info['events']['accept_point_community_id'], ['0', '']) ? [] : explode(',',$info['events']['accept_point_community_id']);
            $response->data = new ApiData();
            $response->data->info = $info;
        }
        return $response;
    }

    //获取可用友元小区
    public function actionPointCommunity(){
        $response = new ApiResponse();

        $community = (new Query())->select(['t1.community_id','t2.name'])
            ->from('hll_point_community as t1')->leftJoin('hll_community as t2', 't2.id = t1.community_id')
            ->where(['t1.valid'=>1])->all();
        if(!$community){
            $info = [];
        }else{
            $info['id'] = array_column($community,'community_id');
            $info['name'] = array_column($community,'name');
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //取消活动
    public function actionCancel(){
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        $eventsId = f_post('id',0);
        $events = HllEvents::find()->where(['id'=>$eventsId,'valid'=>1])->one();
        if(empty($events)){
            $response->data = new ApiData(101,'无此活动！');
            return $response;
        }
        $creater = HllEvents::find()->where(['id'=>$eventsId,'valid'=>1,'creater'=>$userId])->one();
        if(empty($creater)){
            $response->data = new ApiData(102,'你无权修改此活动！');
            return $response;
        }
        $events->valid = 0;
        if($events->save()){
            $response->data = new ApiData(0, '取消成功!');
            return $response;
        }else{
            $response->data = new ApiData(103, '取消失败!');
            return $response;
        }
    }

    //毕业联系卡
    public function actionGraduateCard(){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $data = f_post('data');
        if(empty($data)){
            $response->data = new ApiData(101,'没有数据');
        }
        else{
            $card = HllEventKidContactCard::find()->where(['kidname'=>$data['kidname'],
                'sex'=>$data['sex'],'kindergarten'=>$data['kindergarten'],'class'=>$data['class'],'valid'=>1])->count();
            if($card > 0){
                $response->data = new ApiData(103,'已存在该宝宝的联系卡，请不要重复创建');
            }else{
                $trans = Yii::$app->db->beginTransaction();
                try{
                    $data['account_id'] = $user_id;
                    $card = new HllEventKidContactCard();
                    if($card->load($data,'') && $card->save()){
                        $qr_code = new QrCode();
                        $scene_id = 'contact_card'.$card->id;
                        $result = Yii::$app->wechat->getQrcode()->forever($scene_id);
                        $qr_code->qr_code = $scene_id;
                        $qr_code->qr_url = $result->url;
                        $qr_code->item_type = 4;
                        $qr_code->item_id = $card->id;
                        if($qr_code->save()){
                            $card->qr_code = $qr_code->id;
                            if($card->save()){
                                $trans->commit();
                                $info['id'] = $card->id;
                                $info['sex'] = $card->sex;
                                $info['qr_code'] = QrCode::getUrlById($card->qr_code);
                                if($info['qr_code'] == ''){
                                    $response->data = new ApiData(106,'数据错误');
                                }else{
                                    $response->data = new ApiData();
                                    $response->data->info = $info;
                                }
                            }else{
                                throw new Exception($card->getFirstErrors(),105);
                            }
                        }else{
                            throw new Exception($qr_code->getFirstErrors(),104);
                        }
                    }
                    else{
                        throw new Exception('保存错误',102);
                    }
                }catch (Exception $e){
                    $trans->rollBack();
                    $response->data = new ApiData($e->getCode(),$e->getName());
                }
            }
        }
        return $response;
    }

    //联系卡详情
    public function actionGraduateCardInfo($id){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $code_id = QrCode::getCodeByUrl($id);
        if($code_id == ''){
            $response->data = new ApiData(106,'数据错误');
            return $response;
        }
        $user = EcsUsers::getUser($user_id,['t2.headimgurl']);
        $info = (new Query())->select(['kidname','kindergarten','class','sex',
        'mobilephone','qq','wechat','blessing','pics','qr_code'])
            ->from('hll_event_kid_contact_card')->where(['qr_code'=>intval($code_id), 'valid'=>1])->one();
        if($info['pics']){
            $info['pics'] = explode(',', $info['pics']);
            foreach($info['pics'] as &$item){
                $data = [];
                $url = 'http://pub.huilaila.net/'.$item.'?imageInfo';
                $html = file_get_contents($url);
                $image = json_decode($html);
                $data['path'] = $item;
                $data['width'] = $image->width;
                $data['height'] = $image->height;
                $item = $data;
            }
        }
        $info['headimgurl'] = $user['headimgurl'];
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //查找是否存在
    public function actionSearchCard(){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;

        $info = (new Query())->select(['id','kidname','kindergarten','class','sex',
            'mobilephone','qq','wechat','blessing','pics'])
            ->from('hll_event_kid_contact_card')->where(['account_id'=>$user_id, 'valid'=>1])->one();


        if (empty($info)){
            $info = [];
        } else {
            $info['pics'] = !empty($info['pics']) ? explode(',', $info['pics']) : [];
        }

        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //更新联系卡
    public function actionUpdateCard(){
        $response = new ApiResponse();

        $data = f_post('data');
        if(empty($data)){
            $response->data = new ApiData(101,'没有数据');
        }
        else{
            $card = HllEventKidContactCard::find()->where(['kidname'=>$data['kidname'],
                'sex'=>$data['sex'],'kindergarten'=>$data['kindergarten'],'class'=>$data['class'],'valid'=>1])
                ->andWhere(['<>','id',$data['id']])->count();
            if($card > 0){
                $response->data = new ApiData(103,'已存在该宝宝的联系卡，请不要重复创建');
            }else{
                $card = HllEventKidContactCard::findOne($data['id']);
                if($card->load($data,'') && $card->save()){
                    $response->data = new ApiData();
                    $info['id'] = $card->id;
                    $info['sex'] = $card->sex;
                    $info['qr_code'] = QrCode::getUrlById($card->qr_code);
                    if($info['qr_code'] == ''){
                        $response->data = new ApiData(106,'数据错误');
                    }else{
                        $response->data = new ApiData();
                        $response->data->info = $info;
                    }
                }
                else{
                    $response->data = new ApiData(102,'保存错误');
                }
            }
        }
        return $response;
    }

    //幼儿园列表
    public function actionSchoolList(){
        $response = new ApiResponse();

        $info = (new Query())->select(['kindergarten'])->from('hll_event_kid_contact_card')->distinct()->all();

        if (empty($info)){
            $info = [];
        }
        else{
            $info = array_column($info,'kindergarten');
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //判断男女
    public function actionGetSex(){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $info = (new Query())->select(['sex','qr_code'])->from('hll_event_kid_contact_card')
            ->where(['account_id'=>$user_id,'valid'=>1])->one();

        if (empty($info)){
            $response->data = new ApiData(101,'没有数据');
        }else{
            $info['qr_url'] = QrCode::getUrlById($info['qr_code']);
            $response->data = new ApiData();
            $response->data->info = $info;
        }
        return $response;
    }

    //签到
    public function actionSignIn(){
        $response = new ApiResponse();
        $events_id = f_get('events_id',0);
        $code = f_get('code',0);
        $num = f_get('num',0);

        $user_id = Yii::$app->user->id;
        $apply = HllEventsApply::find()->select(['user_id','events_id'])->where(['events_id'=>$events_id,'sign_in_code'=>$code,'valid'=>1])->asArray()->one();
        $event = HllEvents::find()->select(['title','creater'])->where(["id"=>$apply['events_id']])->asArray()->one();
        $event_worker = HllEventWorker::find()->select(['user_id'])->where(['event_id'=>$events_id,'valid'=>1])->column();
        array_push($event_worker,$event['creater']);
        //是否有权限签到
        if(!in_array($user_id,$event_worker)){
            $response->data = new ApiData(103,'您没有权限签到');
            return $response;
        }
        if ($apply){
            $date = date("Y-m-d");
            $sign_in = (new Query())->from('hll_event_sign_in_log')
                ->where(['events_id'=>$apply['events_id'],'user_id'=>$apply['user_id'],'valid'=>1])
                ->andWhere(["date_format(sign_in_time,'%Y-%m-%d')"=>$date])->count();
            if($sign_in == 1){
                $response->data = new ApiData(101,'今天已经签到过了');
            }else{
                $sign_in = new HllEventSignInLog();
                $sign_in->user_id = $apply['user_id'];
                $sign_in->events_id = $apply['events_id'];
                $sign_in->sign_in_time = date("y-m-d H:i:s");
                $sign_in->sign_in_user_num = $num;
                if($sign_in->save()){
                    $info['title'] = $event['title'];
                    $response->data = new ApiData();
                    $response->data->info = $info;
                }else{
                    $response->data = new ApiData(102,'签到失败');
                }
            }
        }else{
            $response->data = new ApiData(103,'该用户未报名参加活动');
        }
        return $response;
    }

    //签到二维码展示
//    public function actionSignInCode($id){
//        $response = new ApiResponse();
//
//        $user_id = Yii::$app->user->id;
//        $info = (new Query())->select(['sign_in_code',"(id) as apply_id"])->from('hll_events_apply')
//            ->where(['user_id'=>$user_id,'events_id'=>$id,'valid'=>1])->one();
//        if($info){
//            $info['url'] = Yii::$app->params['afgjDomain'].'/event-signin.html';
//            $response->data = new ApiData();
//            $response->data->info = $info;
//        }else{
//            $response->data = new ApiData(101,'数据错误');
//        }
//        return $response;
//    }

    //分享二维码
    public function actionShareQrcode($id){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;

        $info = (new Query())->select(['sign_in_code',"(id) as apply_id"])->from('hll_events_apply')
            ->where(['user_id'=>$user_id,'events_id'=>$id,'valid'=>1])->one();

        if(!$info){
            $response->data = new ApiData(101,'数据错误');
        }else{
            $info['event'] = HllEvents::find()->select(['events_time','title', 'id','events_type','events_tip', 'address'])->where(["id"=>$id])->asArray()->one();
            if($info['event']['events_type'] == 'sessions'){
                $events_session = (new Query())->select(['t2.title','t2.content'])->from('hll_events_sessions_log as t1')
                    ->leftJoin('hll_events_sessions as t2','t2.id = t1.sessions_id')
                    ->where(['t1.user_id'=>$user_id,'t1.events_id'=>$id,'t1.valid'=>1])->one();
                $info['event']['session_title'] = $events_session['title'];
                $info['event']['session_content'] = $events_session['content'];
            }
            $response->data = new ApiData();
            $response->data->info = $info;
        }
        return $response;
    }

    //微信支付参数
    public function actionWxPayParams($applyId)
    {
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;

        if (!$applyId) {
            $response->data = new ApiData(110, '参数错误');
            return $response;
        }

        $apply_info = HllEventsApply::findOne(['id'=>$applyId]);
        $event = HllEvents::findOne(['id'=>$apply_info->events_id,'valid'=>1]);
        if (!$apply_info || !$event) {
            $response->data = new ApiData(111, '数据错误');
            return $response;
        }

        $user = EcsUsers::getUser($user_id, ['t2.openid']);
        $user && $GLOBALS['_SESSION']['openId'] = $user['openid'];

        $apply['cash_fee'] = $apply_info->cash_fee;
        $apply['title'] = $event->title;
        $apply['bill_id'] = (new Query())->select('bill_id')->from('hll_bill')->where(['bill_sn' => $applyId,'bill_category'=>2])->scalar();
        $wxPay = new WxpayV2();
        $data = $wxPay->get_apply_code($apply);
        $response->data = new ApiData();
        $response->data->info = $data;
        return $response;
    }

    //支付成功回调
    public function actionPaySuccess($applyId){
        $response = new ApiResponse();

        $apply = HllEventsApply::findOne(['id'=>$applyId]);
        $events = HllEvents::findOne(['id'=>$apply->events_id]);
        if($events->apply_check == 1){
            $creater = EcsUsers::getUser($events->creater,['t1.user_id', 't2.openid']);
            WxTmplMsg::EventCheckNotice($creater, $applyId, $events->title);
            $response->data = new ApiData(0,'等待管理员审核！');
            $response->data->info['id'] = $applyId;
        }else{
            $user_id = Yii::$app->user->id;
            $user = EcsUsers::getUser($user_id, ['t1.user_id', 't2.openid']);
            WxTmplMsg::EventApplyNotice($user, $events->id,$events->title);
            $response->data = new ApiData(0,'报名成功！');
            $response->data->info['id'] = $applyId;
        }
        return $response;
    }

    //支付失败回调
    public function actionPayError($applyId){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $trans = Yii::$app->db->beginTransaction();
        try{
            $apply = HllEventsApply::findOne(['id'=>$applyId]);
            $apply->valid = 0;
            if($apply->save()){
                $bill = HllBill::findOne(['bill_sn'=>$applyId,'user_id'=>$user_id,'bill_status'=>1]);
                $bill->bill_status = 3;
                if($bill->save()){
                    $event = HllEvents::findOne(['id'=>$apply->events_id]);
                    if($event->events_type == 'sessions'){
                        $sessions_log = HllEventsSessionsLog::find()->where(['events_id'=>$apply->events_id,'user_id'=>$user_id,'valid'=>1])->one();
                        $sessions_log->valid = 0;
                        $sessions_log->save();
                    }
                    $trans->commit();
                    $response->data = new ApiData();
                    $response->data->info = $apply->events_id;
                }else{
                    throw new Exception('更新账单失败',102);
                }
            }else{
                throw new Exception('更新报名信息失败',103);
            }
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getCode());
        }
        return $response;
    }

    /**
     * @params $uid 用户id
     * @param $event_id 活动id
     * @return ApiResponse
     */
    public function actionEventPayMoney($event_id, $uid){
        $response = new ApiResponse();
        $result = [];

        $query = EcsUsers::getUser($uid, ['t2.nickname']);
        $result['nickname'] = $query['nickname'];

        $result['pay'] = (new Query())->select(['t2.total_fee'])->from('hll_events as t1')
            ->leftJoin('hll_events_apply as t2','t2.events_id = t1.id')
            ->where(['t1.id'=>$event_id,'t1.valid'=>1,'t2.user_id'=>$uid,'t2.pay_status'=>2,'t2.valid'=>1])->scalar();

        if(!$result['pay']){
            $response->data = new ApiData(101,'数据错误');
        }else{
            $response->data = new ApiData();
            $response->data->info = $result;
        }
        return $response;
    }

    public function actionPayBackApply($event_id, $uid,$money_paid){
        $response = new ApiResponse();
        $event_apply_id = HllEventsApply::find()->select(['id'])
            ->where(['events_id'=>$event_id, 'user_id'=>$uid, 'pay_status'=>2, 'valid'=>1])->scalar();
        $field =['bill_id','pay_id','pay_name','commercial_id','trans_id','user_id'];
        $bill = (new Query())->select($field)->from('hll_bill')
            ->where(['bill_category'=>2,'pay_status'=>2,'bill_sn'=>$event_apply_id,'user_id'=>$uid])->one();
        if(!$bill){
            $response->data = new ApiData(101,'数据错误');
        }
        else{
            $bill_back = new HllBillRefund();
            $bill['money_paid'] = $money_paid;
            if($bill_back->load($bill,'') && $bill_back->save()){
                $response->data = new ApiData();
                $response->data->info = $bill_back->id;
            }else{
                $response->data = new ApiData(102,'退款申请保存失败');
            }
        }
        return $response;
    }

    /**
     * 管理者操作报名界面
     */
    public function actionAdminSignIn($id) {
        $response = new ApiResponse();

        $event = HllEvents::find()->select(['id', 'title'])->where(['id'=>$id, 'valid'=>1])->asArray()->one();

        if ($event) {
            $response->data = new ApiData();
            $response->data->info['event'] = $event;
        } else {
            $response->data = new ApiData(200, '数据错误');
        }
        return $response;
    }

    /**
     * 添加活动管理人
     * @param $id
     * @return ApiResponse
     */
    public function actionAddEventAdmin($id){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;

        $worker = new HllEventWorker();
        $worker->event_id = $id;
        $worker->user_id = $user_id;

        if($worker->save()){
            $response->data = new ApiData(0,'保存成功');
        }else{
            $response->data = new ApiData(101,'保存失败');
        }
        return $response;
    }

    /**
     * 活动管理者列表
     * @param $id
     * @return ApiResponse
     */
    public function actionEventAdminList($id){
        $response = new ApiResponse();

        $list = (new Query())->select(['t2.nickname','t2.headimgurl','t1.user_id'])->from('hll_event_worker as t1')
            ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.user_id')
            ->where(['t1.event_id'=>$id,'t1.valid'=>1])->all();

        if(!$list){
            $list = [];
        }
        $response->data = new ApiData();
        $response->data->info['list'] = $list;
        return $response;
    }

    public function actionDeleteEventAdmin($id,$user_id){
        $response = new ApiResponse();

        $admin = HllEventWorker::findOne(['event_id'=>$id,'user_id'=>$user_id,'valid'=>1]);

        if(!$admin){
            $response->data = new ApiData(101,'该管理人员不存在');
        }else{
            $admin->valid = 0;
            if($admin->save()){
                $response->data = new ApiData();
            }else{
                $response->data = new ApiData(102,'删除失败');
            }
        }
        return $response;
    }
    
    public function actionWorkerIndex($id) {
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $data = HllEvents::find()->select(['title'])->where(['id' => $id, 'valid' => 1])->asArray()->one();
        $hasPass = (bool)HllEventWorker::find()->where(['event_id'=>$id,'user_id'=>$user_id,'valid'=>1])->count();
        
        if ($data) {
            $response->data = new ApiData();
            $response->data->info['content'] = $data;
            $response->data->info['hasPass'] = $hasPass;
        } else {
            $response->data = new ApiData(200, '数据错误');
        }
        
        return $response;
    }

    /**
     * 活动感谢页面
     * @param $id
     * @return ApiResponse
     */
    public function actionEventsThanksDetail($id){
        $response = new ApiResponse();

        $detail = (new Query())->select(['t2.nickname','t2.headimgurl','t1.title'])->from('hll_events as t1')
            ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.creater')
            ->where(['t1.id'=>$id,'t1.valid'=>1])->one();
        if(!$detail){
            $detail = [];
        }
        $response->data = new ApiData();
        $response->data->info = $detail;
        return $response;
    }

    /**
     * 活动感谢积分
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionEventsThanks(){
        $response = new ApiResponse();
        $account_id = Yii::$app->user->id;
        $data = Yii::$app->request->post('data');
        $events = HllEvents::findOne($data['id']);
        if (!$events) {
            $response->data = new ApiData(112, '无相关数据');
            return $response;
        }
        $community_id = explode(',',$events->accept_point_community_id);
        if(!in_array(0,$community_id)){
            array_push($community_id,0);
        }
        $userPoints = HllUserPoints::getUserPoints($account_id,$community_id,3);
        if ($userPoints > 0 && $userPoints < $data['thanks_point']) {
            $response->data = new ApiData(100, '友元余额不足!');
            $response->data->info['pay_points'] = $userPoints;
        } elseif ($userPoints == 0) {
            $response->data = new ApiData(101, '友元余额为0,请充值友元!');
            $response->data->info['pay_points'] = $userPoints;
        } else {
            $trans = Yii::$app->db->beginTransaction();
            try {
                //扣除乘客的积分

                $result = EcsAccountLog::log_account_change($community_id,$account_id, $events->creater, 0, 0, 0, $data['thanks_point']);
                if ($result) {
                    //保存此次感谢语句与感谢积分
                    $model = new ItemSharingThanks();
                    $model->type_id = 2;
                    $model->content = $data['thanks_word'];
                    $model->thanks_point = $data['thanks_point'];
                    $model->is_id = $data['id'];
                    //感谢数目增加
                    if ($model->save()) {
                        $trans->commit();
                        $response->data = new Apidata(0, '保存成功');
                        WxTmplMsg::thanksAccountNotice($model->id, $events->creater, 3); //userPoints 用户当前友元总额(分)
                    } else {
                        $response->data = new Apidata(110, '保存失败');
                    }
                } else {
                    $response->data = new Apidata(112, '扣款失败');
                }
            } catch (\yii\db\Exception $e) {
                $trans->rollBack();
                $response->data = new Apidata(113, '操作失败');
            }
        }
        return $response;

    }

    /**
     * 活动感谢列表
     * @param $id -> event_id
     * @return ApiResponse
     */
    public function actionEventsThanksList($id){
        $response = new ApiResponse();
        $list = (new Query())->select(['t1.thanks_point','t1.content','t2.nickname','t2.headimgurl', 't1.created_at'])
            ->from('hll_item_sharing_thanks as t1')
            ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.creater')
            ->where(['t1.is_id'=>$id, 't1.type_id'=>2, 't1.valid'=>1])->orderBy(['t1.created_at' => SORT_DESC])->all();
        if(!$list){
            $list = [];
        }
        $response->data = new ApiData();
        $response->data->info['list'] = $list;
        return $response;
    }

    /**
     * 获取用户活动缴费简略信息
     * @param $event_id
     * @return ApiResponse
     */
    public function actionGetUserApplyInfo($event_id) {
        $response = new ApiResponse();

        $userId = Yii::$app->user->id;

        $result = HllEventsApply::getUserApplyInfo($event_id, $userId);

        if ($result) {
            $response->data = new ApiData();
            $response->data->info = $result;
        } else {
            $response->data = new ApiData(200, '数据错误');
        }

        return $response;
    }

    /**
     * 中花岗项目活动
     * @return ApiResponse
     */
    public function actionZHGEvents(){
        $response = new ApiResponse();
        $code = f_post('code',0);
        $userId = Yii::$app->user->id;
        $community_id = 19668;

        $log = new HllEventUsercommit();
        $log->info = $code;
        $log->user_id = $userId;
        $log->save(false);
        $model = new SignModel();
        $model->setCommunityId($community_id);
        $result = $model->sign($userId,1,$code);
        if($result['code']){
            $response->data = new ApiData(0,'success');
            $data = ['type'=>0,'message'=>'成功领取'];
            $left_point = HllUserPoints::getUserPoints($userId);
            $user = EcsUsers::getUser($userId, ['t1.user_id', 't2.openid','t2.nickname']);
            $title = '中花岗圣诞活动赠送友元';
            WxTmplMsg::PointChangeNotice($user,5000,$left_point,$title);
        }else{
            if($result['message'] == 'succeed'){
                $response->data = new ApiData(0,'success');
                $data = ['type'=>1,'message'=>'已领取'];
            }else if($result['message'] == 'not_staff'){
                $response->data = new ApiData(0,'success');
                $data = ['type'=>2,'message'=>'号码不对'];
            }else if($result['message'] == 'is_used'){
                $response->data = new ApiData(0,'success');
                $data = ['type'=>3,'message'=>'号码已被使用'];
            }else{
                $response->data = new ApiData(101,'success');
                $data = ['type'=>4,'message'=>$result['message']];
            }
        }
        $response->data->info = $data;
        return $response;
    }
}

