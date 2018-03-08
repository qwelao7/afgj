<?php
/**
 * 微信模板消息
 * User: wangzend
 * Date: 16/6/27
 * Time: 下午7:44
 */

namespace common\components;
use common\models\ar\user\AccountAuth;
use common\models\ecs\EcsUserAddress;
use common\models\ecs\EcsUsers;
use common\models\hll\HllLxhealthyTemp;
use common\models\ecs\EcsWechatUser;
use common\models\hll\ItemSharing;
use common\models\hll\ItemSharingThanks;
use common\models\hll\RideSharing;
use common\models\hll\RideSharingCustomer;
use common\models\hll\UserAddress;
use common\models\hll\HllUserPoints;
use common\models\hll\UserAddressTemp;
use common\models\need\DecorateRequirement;
use Yii;
use common\models\ar\user\Account;
use common\models\ar\message\MessageNotification;
use yii\db\Query;

class WxTmplMsg {

    /**帐户变更提醒
     * @param $userId 用户ID
     * @param $first
     * @param $remindType
     * @param $remark
     * @param $redirectUrl
     * @return bool
     * @author zend.wang
     * @date  2016-06-28 13:00
     */
    public static function changeAccountRemind($userId,$first,$remindType,$remark,$redirectUrl) {

        $model = Account::findOne($userId);
        if( !$model || !$model->weixin_code ) return false;
        if( !$model->nickname ) $model->nickname="~~佚名~~";

        $data = ['first'=>['value'=>$first],
            'account'=>['value'=>$model->nickname, "color"=>"#173177"],
            'time'=>['value'=> f_date(time()), "color"=>"#173177"],
            'type'=>['value'=>$remindType, "color"=>"#173177"],
            'remark'=>['value'=>$remark]];
        !strstr($redirectUrl,"http://") && $redirectUrl =  Yii::$app->request->hostInfo.'/redirect?state='.$redirectUrl;
        $sendResult = Yii::$app->wx->sendTemplateMessage($model->weixin_code ,'account_change_remind',$data,$redirectUrl);
        static::saveMessageNotification($userId,$model->admin_id,$data,$sendResult,$redirectUrl);
    }

    public static function houseAuthNotice($authId,$address_id) {

        $model = AccountAuth::findOne($authId);
        if($model) {
            if($model->auth_status == 3 ){
                $first ="恭喜您房产认证通过";
                $remark="";
            }elseif($model->auth_status == 2 ){
                $first ="很抱歉，您房产认证失败";
                $remark = "失败原因：".$model->failcause;
            }else{
                return false;
            }
            $userAddress = UserAddress::findOne(['id'=>$address_id]);
            if(!$userAddress){
                $userAddress = UserAddressTemp::findOne(['id'=>$model->address_temp_id]);
                $redirectUrl =Yii::$app->request->hostInfo."/estate-info.html?id={$userAddress->id}%26fang=2";
            }else{
                $redirectUrl =Yii::$app->request->hostInfo."/estate-info.html?id={$userAddress->id}%26fang=1";
            }
            $wechatUser = EcsWechatUser::findOne(['ect_uid'=>$model->account_id]);
            if($userAddress) {
                $data = ['first'=>['value'=>$first],
                    'name'=>['value'=>$userAddress->consignee],
                    'tel'=>['value'=> $userAddress->mobile],
                    'address'=>['value'=>$userAddress->address_desc],
                    'remark'=>['value'=>$remark]];
                $sendResult = Yii::$app->wx->sendTemplateMessage($wechatUser->openid ,'house_auth_notice',$data,$redirectUrl);
                return static::saveMessageNotification($model->account_id,0,$data,$sendResult,$redirectUrl);
            }
        }
    }

    /**
     * 感谢用户模板消息
     * @param $id
     * @params $totalPoints 用户友元总额 (元)
     * @param $type 1:顺丰车感谢 2:借用感谢
     * @param $loupanId
     * @return bool|void
     * @author kaikai.qin
     */

    public static function thanksAccountNotice($id, $account_id, $type) {
        $totalPoints = HllUserPoints::getUserPoints($account_id);
        if($type == 1){
            $model = RideSharingCustomer::findOne(['id'=>$id,'valid'=>1,'status'=>3]);
            if($model) {
                $query = RideSharing::findOne(['id'=>$model->rs_id, 'valid'=>1]);
                $first = "您有新友元到账，详情如下。";
                $desc = "友邻感谢赠送友元";
                $time = date('Y-m-d H:i:s',time());
                $wechatUser = EcsWechatUser::findOne(['ect_uid'=>$query->account_id]);
                $user = EcsUsers::findOne(['user_id'=>$query->account_id]);
                if($wechatUser && $user) {
                    $data = ['first'=>['value'=>$first],
                        'account'=>['value'=>$wechatUser->nickname],
                        'time'=>['value'=>$time],
                        'type'=>['value'=> $desc],
                        'creditChange'=>['value'=> "到帐"],
                        'number'=>['value'=> ($model->thanks_point).'友元'],
                        'creditName'=>['value'=> "友元"],
                        'amount'=>['value'=> ($totalPoints).'友元'],
                        'remark'=>['value'=>$model->thanks_word]];
                    $redirectUrl =Yii::$app->request->hostInfo."/freeride-detail.html?id=".$model->rs_id;
                    $sendResult = Yii::$app->wx->sendTemplateMessage($wechatUser->openid ,'thanks_account_notice',$data,$redirectUrl);
                    return static::saveMessageNotification($query->account_id,0,$data,$sendResult,$redirectUrl);
                }
            }else{
                return false;
            }
        }else if($type == 2){

            $model = (new Query())->select(['t2.id','t2.account_id','t1.content','t1.thanks_point','t1.created_at'])
                ->from('hll_item_sharing_thanks as t1')
                ->leftJoin('hll_item_sharing as t2','t2.id = t1.is_id')
                ->where(['t1.id'=>$id,'t1.valid'=>1])->one();
            if($model) {
                $first = "您有新友元到账，详情如下。";
                $desc = "友邻感谢赠送友元";
                $time = date('Y-m-d H:i:s',time());
                $wechatUser = EcsWechatUser::findOne(['ect_uid'=>$model['account_id']]);
                $user = EcsUsers::findOne(['user_id'=>$model['account_id']]);
                if($wechatUser && $user) {
                    $data = ['first'=>['value'=>$first],
                        'account'=>['value'=>$wechatUser->nickname],
                        'time'=>['value'=>$time],
                        'type'=>['value'=> $desc],
                        'creditChange'=>['value'=> "感谢"],
                        'number'=>['value'=> ($model['thanks_point']).'友元'],
                        'creditName'=>['value'=> "友元"],
                        'amount'=>['value'=> ($totalPoints).'友元'],
                        'remark'=>['value'=>$model['content']]];
                    $redirectUrl =Yii::$app->request->hostInfo."/borrow-detail.html?id=".$model['id'];
                    $sendResult = Yii::$app->wx->sendTemplateMessage($wechatUser->openid ,'thanks_account_notice',$data,$redirectUrl);
                    return static::saveMessageNotification($model['account_id'],0,$data,$sendResult,$redirectUrl);
                }
            }else{
                return false;
            }
        }else if($type == 3){
            $model = (new Query())->select(['content','thanks_point','created_at'])
                ->from('hll_item_sharing_thanks')->where(['id'=>$id,'valid'=>1])->one();
            if($model) {
                $first = "您有新友元到账，详情如下。";
                $desc = "友邻感谢赠送友元";
                $time = date('Y-m-d H:i:s',time());
                $wechatUser = EcsWechatUser::findOne(['ect_uid'=>$account_id]);
                if($wechatUser) {
                    $data = ['first'=>['value'=>$first],
                        'account'=>['value'=>$wechatUser->nickname],
                        'time'=>['value'=>$time],
                        'type'=>['value'=> $desc],
                        'creditChange'=>['value'=> "感谢"],
                        'number'=>['value'=> ($model['thanks_point']).'友元'],
                        'creditName'=>['value'=> "友元"],
                        'amount'=>['value'=> ($totalPoints).'友元'],
                        'remark'=>['value'=>$model['content']]];
                    $redirectUrl =Yii::$app->request->hostInfo."/points-index.html";
                    $sendResult = Yii::$app->wx->sendTemplateMessage($wechatUser->openid ,'thanks_account_notice',$data,$redirectUrl);
                    return static::saveMessageNotification($account_id,0,$data,$sendResult,$redirectUrl);
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    /**
     * 保存微信模板消息发送日志
     * @param $userId 发送用户ID
     * @param $adminId 发送管理ID
     * @param $data 发送内容
     * @param $sendResult 发送结果
     * @param $redirectUrl 跳转URL
     * @author zend.wang
     * @date  2016-06-28 13:00
     */
    private static function saveMessageNotification($userId,$adminId,$data,$sendResult,$redirectUrl) {
        $msgNotification = new MessageNotification();
        $msgNotification->account_id = $userId;
        if($adminId > 0 ){
            $msgNotification->account_type = 2;
        } else {
            $msgNotification->account_type = 1;
        }
        $msgNotification->content = json_encode($data);
        $msgNotification->send_way = 2;
        $msgNotification->to_url = $redirectUrl;
        $msgNotification->send_time = f_date(time());

        if($sendResult['errcode']==0){
            $msgNotification->send_result = 2;
        }else {
            $msgNotification->send_result=3;
            $msgNotification->fail_reason = $sendResult['errcode'];
        }
        $msgNotification->save(false);
    }


    /**
     * @param $id int 顺风车Id
     * @param $type int 通知类型
     * @param $customerId int 客户ID
     * @author kaikai.qin
     * @return bool
     */
    public static function rideSharingNotification($id,$type,$customerId=0){
        if($type == 1){
            $model = RideSharing::findOne(['id'=>$id,'valid'=>0]);
            $redirectUrl = Yii::$app->request->hostInfo.'/freeride-list.html?id='.$model->loupan_id;
            if($model){
                $first = "对不起，车主已取消顺风车行程!";
                $remark = "请选乘其它顺风车或改乘其它交通工具，谢谢！";
            }else{
                return false;
            }
            $user = EcsUsers::findOne(['user_id'=>$model->account_id]);
            $wechatUser = EcsWechatUser::findOne(['ect_uid'=>$model->account_id]);
            if($user && $wechatUser){
                $customer = RideSharingCustomer::getUserInfo($id);
                $data = ['first' => ['value' => $first],
                    'keyword1' => ['value' => $model->origin],
                    'keyword2' => ['value' => $model->destination],
                    'keyword3' => ['value' => $model->go_time],
                    'keyword4' => ['value' => $wechatUser->nickname],
                    'keyword5' => ['value' => $user->mobile_phone],
                    'remark' => ['value' => $remark]];
                foreach($customer as $item) {
                    $sendResult = Yii::$app->wx->sendTemplateMessage($item['openid'], 'ride_sharing_notice', $data, $redirectUrl);
                    static::saveMessageNotification($item['ect_uid'], 0, $data, $sendResult, $redirectUrl);
                }
                return true;
            }else{
                return false;
            }
        }else{
            $model = RideSharing::findOne(['id'=>$id,'valid'=>1]);
            $rscStatus = RideSharingCustomer::find()->select('status')->where(['rs_id'=>$id,'account_id'=>$customerId,'valid'=>1])->orderBy('id DESC')->scalar();
            $reJoin = 0;
            if($model && $rscStatus == 1) {

                $currentTime = time();
                $goTime = strtotime($model->go_time);
                $intervalTime=  ($goTime-$currentTime)/60;

                if($intervalTime>0 && $intervalTime <= 330 && $intervalTime >= 270) {
                    $first = "您预约的顺风车5分钟后将要出发，请准时搭乘哦。";
                    $remark = " ";
                    $redirectUrl = HOST . "/freeride-detail.html?id={$model->id}";
                    $reJoin = 2;
                } elseif($intervalTime<0 && abs($intervalTime) >= 1800){
                    $first = "您搭上顺风车了吗？";
                    $remark = "如果是，别忘了感谢一下好心的车主哦。";
                    $redirectUrl =HOST."/freeride-detail.html?id={$model->id}";
                    $reJoin = 1;
                } else {
                    return 2;
                }

                $mobilePhone = EcsUsers::find()->select('mobile_phone')->where(['user_id'=>$model->account_id])->scalar();
                $nickName = EcsWechatUser::find()->select('nickname')->where(['ect_uid'=>$model->account_id])->scalar();

                if($mobilePhone && $nickName){

                    $data = ['first' => ['value' => $first],
                        'keyword1' => ['value' => $model->origin],
                        'keyword2' => ['value' => $model->destination],
                        'keyword3' => ['value' => $model->go_time],
                        'keyword4' => ['value' => $nickName],
                        'keyword5' => ['value' => $mobilePhone],
                        'remark' => ['value' => $remark]];

                    $customerOpenId = EcsWechatUser::find()->select('openid')->where(['ect_uid'=>$customerId])->scalar();

                    $sendResult = Yii::$app->wx->sendTemplateMessage($customerOpenId, 'ride_sharing_notice', $data, $redirectUrl);
                    static::saveMessageNotification($model->account_id, 0, $data, $sendResult, $redirectUrl);
                }

            }

            return $reJoin;

        }
    }

    /**
     * 拼车加入提醒
     * @param $id int 拼车记录Id
     * @return bool|void
     */
    public static function joinRideSharingNotification($id){
        $model = RideSharingCustomer::findOne(['id'=>$id,'valid'=>1,'status'=>1]);
        if($model) {
            $ride = RideSharing::findOne(['id'=>$model->rs_id]);
            if($ride){
                $first = "有邻居要搭乘您发布的顺风车!";
                $redirectUrl =Yii::$app->request->hostInfo."/freeride-detail.html?id=".$model->rs_id;
                $remark = "";
                $wechatUser = EcsWechatUser::findOne(['ect_uid'=>$model->account_id]);
                $driver = EcsWechatUser::findOne(['ect_uid'=>$ride->account_id]);
                $user = EcsUsers::findOne(['user_id'=>$model->account_id]);
                if($user && $wechatUser && $driver){
                    $data = ['first'=>['value'=> $first],
                        'keyword1'=>['value'=> $ride->go_time],
                        'keyword2'=>['value'=> $ride->origin],
                        'keyword3'=>['value'=> $wechatUser->nickname.' '.$model->customer_num.'名乘客'],
                        'keyword4'=>['value'=> $user->mobile_phone],
                        'remark'=>['value'=> $remark]];
                    $sendResult = Yii::$app->wx->sendTemplateMessage($driver->openid ,'join_ridesharing_notice',$data,$redirectUrl);
                    return static::saveMessageNotification($ride->account_id,0,$data,$sendResult,$redirectUrl);
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
    /**
     * 拼车取消提醒
     * @param $id int 拼车记录Id
     * @return bool|void
     */
    public static function quitRideSharingNotification($id){
        $model = RideSharingCustomer::findOne(['id'=>$id,'valid'=>1,'status'=>2]);
        if($model) {
            $ride = RideSharing::findOne(['id'=>$model->rs_id]);
            if($ride){
                $first = "有邻居取消搭乘您发布的顺风车!";
                $redirectUrl =Yii::$app->request->hostInfo."/freeride-detail.html?id=".$model->rs_id;
                $remark = "";
                $wechatUser = EcsWechatUser::findOne(['ect_uid'=>$model->account_id]);
                $user = EcsUsers::findOne(['user_id'=>$model->account_id]);
                $driver = EcsWechatUser::findOne(['ect_uid'=>$ride->account_id]);
                if($user && $wechatUser && $driver){
                    $data = ['first'=>['value'=> $first],
                        'keyword1'=>['value'=> $ride->go_time],
                        'keyword2'=>['value'=> $wechatUser->nickname.' '.$model->customer_num.'名乘客'],
                        'remark'=>['value'=> $remark]];
                    $sendResult = Yii::$app->wx->sendTemplateMessage($driver->openid ,'quit_ridesharing_notice',$data,$redirectUrl);
                    return static::saveMessageNotification($ride->account_id,0,$data,$sendResult,$redirectUrl);
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 用户咨询提醒
     * @param $id int 借用或小市Id
     * @param $type 1：借用 2：小市
     * @return bool|void
     */
    public static function userAdvisoryNotification($id,$type){
        $model = ItemSharing::findOne(['id'=>$id,'valid'=>1,'status'=>2]);
        $wechatUser = EcsWechatUser::findOne(['ect_uid'=>$model->account_id]);
        $user = EcsUsers::findOne(['user_id'=>$model->account_id]);
        $cur = Yii::$app->user->id;
        if($type == 1) {
            $param = $model->borrow_item_type - 1;
        }else if($type == 2) {
            $param = $model->sell_item_type - 1;
        }
        if($model && $wechatUser && $user){
            if($type == 1){
                $content = $wechatUser->nickname."，你好！我想借用你的".$model->item_desc;
            }elseif($type == 2){
                $content = $wechatUser->nickname."，你好！我喜欢你的".$model->item_desc;
            }else{
                return false;
            }
        }else{
            return false;
        }
        $first = "您有一条新的咨询信息，请处理。";
        $remark = "";
        $data = ['first'=>['value'=>$first],
            'keyword1'=>['value'=>$wechatUser->nickname],
            'keyword2'=>['value'=> $content],
            'remark'=>['value'=>$remark]];
        $redirectUrl =Yii::$app->request->hostInfo."/neighbor-chat.html?id=".$cur.'%26straight=1';
        $sendResult = Yii::$app->wx->sendTemplateMessage($wechatUser->openid ,'user_advisory_notice',$data,$redirectUrl);
        return static::saveMessageNotification($model->account_id,0,$data,$sendResult,$redirectUrl);
    }

    /**
     * 用户单聊
     * @param $id
     * @param $userid
     */
    public static function neighborTalkNotification($id,$userid){
        $wechatUser = EcsWechatUser::findOne(['ect_uid'=>$id]);
        $user = EcsWechatUser::findOne(['ect_uid'=>$userid]);
        if($wechatUser && $user){
            $time = date('Y-m-d H:i:s',time());
            $first = "您有一条新消息。";
            $content = $user->nickname."，你好！";
            $remark = "";
            $data = ['first'=>['value'=>$first],
                'keyword1'=>['value'=>$wechatUser->nickname],
                'keyword2'=>['value'=> $time],
                'keyword3'=>['value'=> $content],
                'remark'=>['value'=>$remark]];
            $redirectUrl =Yii::$app->request->hostInfo."/neighbor-chat.html?id={$id}"."%26straight=1";
            $sendResult = Yii::$app->wx->sendTemplateMessage($user->openid ,'neighbor_talk_notice',$data,$redirectUrl);
            return static::saveMessageNotification($userid,0,$data,$sendResult,$redirectUrl);
        }else{
            return false;
        }
    }

    /**
     * 厚木装饰预约通知
     */
    public static function decorateOrderNotification($id, $user) {
        $query = DecorateRequirement::findOne($id);
        if($query) {
            $uid = Yii::$app->params['homeDecAdmin'];
            $first = '您好,有客户提交装修请求。';
            $remark = "装修类型: ".$query->decorate_type."\n户型: ".$query->house_type."\n联系方式: ".$query->cust_name." ".$query->cust_phone."\n请及时处理，谢谢!";
            $data = [
                'first' => ['value'=>$first],
                'keyword1'=> ['value'=>$query->area_name],
                'keyword2'=> ['value'=>$query->community_name],
                'keyword3'=> ['value'=>$query->house_area],
                'keyword4'=> ['value'=>$query->create_at.'提交'],
                'remark'=> ['value'=>$remark]
            ];
            $redirectUrl = '';
            $sendResult = Yii::$app->wx->sendTemplateMessage($uid,'decorate_order_notice',$data,$redirectUrl);
            return static::saveMessageNotification($user,0,$data,$sendResult,$redirectUrl);
        }else {
            return false;
        }
    }

    /**
     * 体检结果通知
     * @param $id 消息id
     * anthor kaikai.qin
     */
    public static function physicalExaminationNotification($id){
        $model = HllLxhealthyTemp::findOne($id);
        $dataBody=json_decode($model->data);
        $wechatUser = EcsWechatUser::findOne(['openid'=>$model->openId]);
        if($model){
            $data = ['first'=>['value'=>$dataBody->first],
                'keyword1'=>['value'=>$dataBody->keyword1],
                'keyword2'=>['value'=> $dataBody->keyword2],
                'keyword3'=>['value'=>$dataBody->keyword3],
                'remark'=>['value'=>$dataBody->remark]];
            $redirectUrl =Yii::$app->request->hostInfo."$dataBody->redirect_url";
            $sendResult = Yii::$app->wx->sendTemplateMessage($model->openId ,'physical_examination_notice',$data,$redirectUrl);
            return static::saveMessageNotification($wechatUser->ect_uid,0,$data,$sendResult,$redirectUrl);
        }
    }

    /**
     * 房产认证通知管理员
     * @param $user
     */
    public static function taskHandleNotification($user) {
        $first = '您有一条任务处理通知。';
        $keyword1 = '用户申请房产认证';
        $keyword2 = '提醒';
        $remark = '用户申请房产认证';
        $url = '';

        $data = ['first'=>['value'=>$first],
            'keyword1'=>['value'=>$keyword1],
            'keyword2'=>['value'=> $keyword2],
            'remark'=>['value'=>$remark]];

        if (IS_PROD_MACHINE) {
            $uid = Yii::$app->params['prodAdmin'];
        } else {
            $uid = Yii::$app->params['devAdmin'];
        }

        $uid = EcsWechatUser::findOne(['ect_uid'=>$uid]);

        $sendResult = Yii::$app->wx->sendTemplateMessage($uid->openid ,'task_handle_notice',$data,$url);
        return static::saveMessageNotification($user,0,$data,$sendResult,$url);
    }

    /**
     * 提醒用户更新里程数
     * @params $user 用户信息
     * @params $car 车辆信息
     */
    public static function carUpdateKm ($user, $car) {
        $first          = '您好，请更新您的车辆里程信息。';
        $keyword1       = '里程更新';
        $keyword4       = '请更新您的车辆里程信息，使维保提醒准确触发。';
        $remark         = '';
        $time           = date('Y-m-d H:i:s', time());
        $redirectUrl    = Yii::$app->params['afgjDomain'].'/vehicle-alert.html?id='.$car['id'];

        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $car['car_num']],
            'keyword3'  => ['value' => $time],
            'keyword4'  => ['value' => $keyword4],
            'remark'    => ['value' => $remark]
        ];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'car_handle_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }

    /**
     * 用户车辆提醒
     * @params $car 车辆信息
     * @params $curTime 服务器当前时间
     */
    public static function carNotice ($user, $car, $count){
        $first          = '您好，您的车辆有待处理提醒';
        $keyword1       = '提醒';
        $keyword4       = '您的车辆有'. $count .'个提醒，请查看。';
        $remark         = '';
        $redirectUrl    = Yii::$app->params['afgjDomain'].'/vehicle-alert.html?id='.$car['id'];
        $time           = date('Y-m-d H:i:s', time());

        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $car['car_num']],
            'keyword3'  => ['value' => $time],
            'keyword4'  => ['value' => $keyword4],
            'remark'    => ['value' => $remark]
        ];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'car_handle_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }

    /**
     * 用户设备提醒
     * @params $address 房产信息
     * @params $curTime 服务器当前时间
     */
    public static function equipmentNotice ($user, $notification){
        $first          = '您有一条任务处理通知';
        $keyword1       = '设施需要养护';
        $keyword2       = '提醒';
        $remark         = '您的设施下有'. $notification['name'] . $notification['model'] .'需要养护，请查看。';
        $redirectUrl    = Yii::$app->params['afgjDomain'].'/equip-care-list.html?id='.$notification['id'].'%26address='.$notification['address_id'];
        $time           = date('Y-m-d H:i:s', time());

        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $keyword2],
            'remark'    => ['value' => $remark]
        ];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'task_handle_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }

    /**
     * 社区故障提醒
     * @params $curTime 服务器当前时间
     */
    public static function FeedbackNotice ($user, $work_id){
        $first          = '您有一条任务处理通知';
        $keyword1       = '报障处理提醒';
        $keyword2       = '提醒';
        $remark         = '您有报障任务需要处理，请查看。';
        $redirectUrl    = Yii::$app->params['afgjDomain'].'/error-list.html?id='.$work_id;
        $time           = date('Y-m-d H:i:s', time());
        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $keyword2],
            'remark'    => ['value' => $remark]
        ];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'task_handle_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }

    /**
     * 社区故障评价
     * @params $curTime 服务器当前时间
     */
    public static function DecorateRateNotice ($user, $case_id){
        $first          = '您有一条任务处理通知';
        $keyword1       = '报障处理提醒';
        $keyword2       = '提醒';
        $remark         = '您有报障任务已经完成处理，请评价。';
        $redirectUrl    = Yii::$app->params['afgjDomain'].'/error-rate.html?id='.$case_id;

        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $keyword2],
            'remark'    => ['value' => $remark]
        ];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'task_handle_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }

    /**
     * 社区故障评价查看
     * @params $curTime 服务器当前时间
     */
    public static function DecorateRateShowNotice ($user, $case_id){
        $first          = '您有一条任务处理通知';
        $keyword1       = '报障处理提醒';
        $keyword2       = '提醒';
        $remark         = '您有一条报障任务评价，请查看。';
        $redirectUrl    = Yii::$app->params['afgjDomain'].'/error-evaluation.html?id='.$case_id;

        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $keyword2],
            'remark'    => ['value' => $remark]
        ];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'task_handle_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }

    /**
     * 活动报名提醒
     * @param $user
     * @param $work_id
     * @return bool
     */
    public static function EventApplyNotice ($user, $events_id, $title){
        $first          = '您有一条活动报名成功通知。';
        $keyword1       = $title;
        $keyword2       = '报名成功';
        $remark         = '点击详情查看签到二维码和签到数字码，参加活动时请向工作人员出示签到码，完成签到，谢谢。';
        $redirectUrl    = Yii::$app->params['afgjDomain'].'/event-qrcode.html?id='.$events_id;

        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $keyword2],
            'remark'    => ['value' => $remark]
        ];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'task_handle_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }

    /**
     * 取消活动报名提醒
     * @param $user
     * @param $events_id
     * @param $type 1为取消报名通知 4为取消报名审核通知 5为审核报名带提醒 2为审核通过 3为审核失败 6给管理员发退费通知
     * @return bool
     */
    public static function EventCancelNotice ($user, $events_id, $title,$type=1){
        $first          = '您有一条取消活动报名通知。';
        $keyword1       = $title;
        switch($type){
            case 4:
                $keyword2 = '取消报名审核';
                $redirectUrl    = Yii::$app->params['afgjDomain'].'/confirm-tpl.html?type=3%26apply_id='.$user['apply_id'].'%26id='.$user['refund_id'];
                break;
            case 5:
                $keyword2 = '取消活动报名,等待管理员审核。';
                $redirectUrl    = Yii::$app->params['afgjDomain'].'/event-detail.html?id='.$events_id.'%26type=1 ';
                break;
            case 2:
                $keyword2 = '取消报名审核成功，等待退款。';
                $redirectUrl    = Yii::$app->params['afgjDomain'].'/event-detail.html?id='.$events_id.'%26type=1 ';
                break;
            case 3:
                $keyword2 = '取消报名审核失败。';
                $redirectUrl    = Yii::$app->params['afgjDomain'].'/event-detail.html?id='.$events_id.'%26type=1 ';
                break;
            case 6:
                $keyword2 = '取消报名审核成功，请退款。';
                $redirectUrl = '';
                break;
            default:
                $keyword2 = '取消报名';
                $redirectUrl    = Yii::$app->params['afgjDomain'].'/event-detail.html?id='.$events_id.'%26type=1 ';
                break;
        }
        $remark         = '';

        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $keyword2],
            'remark'    => ['value' => $remark]
        ];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'task_handle_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }

    /**
     * 活动审核提醒
     * @param $user
     * @param $work_id
     * @return bool
     */
    public static function EventCheckNotice ($user, $apply_id, $title,$unique_id = '0'){
        $first          = '您有一个活动报名需要审核。';
        $keyword1       = $title;
        $keyword2       = '报名审核';
        $remark         = '点请点击详情进行审核，谢谢。';
        $redirectUrl    = Yii::$app->params['afgjDomain'].'/confirm-tpl.html?type=2%26id='.$apply_id.'%26unique_id='.$unique_id;

        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $keyword2],
            'remark'    => ['value' => $remark]
        ];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'task_handle_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }

    /**
     * 活动审核结果通知
     * @param $user
     * @param $work_id
     * @return bool
     */
    public static function EventCheckResult ($user, $title, $fail_reason){
        $first          = '您报名的活动已审核。';
        $keyword1       = $title;
        $keyword2       = '审核不通过';
        $remark         = $fail_reason;
        $redirectUrl    = '';

        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $keyword2],
            'remark'    => ['value' => $remark]
        ];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'event_check_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }

    /**
     * 积分变动提醒
     * @param $user
     * @param $apply_id
     * @param $title
     * @param $type 1为默认 2为绑定发放 3为宝华发放 4为分享友元
     * @return bool
     */
    public static function PointChangeNotice ($user, $point, $left_point ,$title, $type=1){
        $first = '友元变动提醒';
        $time = date('Y-m-d H:i:s',time());
        if($type == 3){
            $redirectUrl = Yii::$app->params['afgjDomain'].'/send-points-desc.html';
            $remark = '获得';
        }else if($type == 2){
            $redirectUrl = Yii::$app->params['afgjDomain'].'/points-index.html';
            $remark = '获得';
        }else if($type == 4){
            $redirectUrl = Yii::$app->params['afgjDomain'].'/points-index.html';
            $remark = '分享';
        }else{
            $redirectUrl = Yii::$app->params['afgjDomain'].'/points-index.html';
            $remark = '消费';
        }
        $data = ['first'=>['value'=>$first],
            'account'=>['value'=>$user['nickname']],
            'time'=>['value'=>$time],
            'type'=>['value'=> $title],
            'creditChange'=>['value'=> $remark],
            'number'=>['value'=> $point.'友元'],
            'creditName'=>['value'=> "友元"],
            'amount'=>['value'=> $left_point.'友元'],
            'remark'=>['value'=>'']];

        $sendResult = Yii::$app->wx->sendTemplateMessage($user['openid'] ,'thanks_account_notice',$data,$redirectUrl);
        static::saveMessageNotification($user['user_id'],0,$data,$sendResult,$redirectUrl);
        return true;
    }
}

