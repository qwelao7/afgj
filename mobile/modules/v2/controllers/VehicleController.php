<?php
namespace mobile\modules\v2\controllers;

use common\models\hll\AccountCar;
use common\models\hll\HllUserCar;
use common\models\hll\HllUserCarLog;
use common\models\hll\HllUserCarNotification;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\data\ActiveDataProvider;
use Yii;
use yii\base\Exception;
use yii\db\Query;

class VehicleController extends ApiController
{
    /**
     * Created by PhpStorm.
     * User: nancy
     * Date: 2017/4/8
     * Time: 14:38
     */

    /**
     * 车辆列表
     */
    public function actionIndex () {
        $userId = Yii::$app->user->id;

        $response = new ApiResponse();
        
        $query = HllUserCar::getList($userId);

        if (!empty($query)) {
            //数据分页
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => []
            ]);

            if ($dataProvider && $dataProvider->count > 0) {
                $response->data = new ApiData();
                $list = $dataProvider->getModels();

                foreach ($list as &$item) {
                    $item['warnnings'] = HllUserCarNotification::find()->where(['car_id'=>$item['id'], 'alert_status'=>1, 'valid'=>1])->count();
                }

                $info['list'] = $list;
                $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
                $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();

                $response->data = new ApiData();
                $response->data->info = $info;
            } else {
                $response->data = new ApiData(110, '无相关数据');
            }
        } else {
            $response->data = new ApiData(100, '参数错误');
        }
        return $response;
    }

    /**
     * 车辆提醒信息列表
     * @param $id
     */
    public function actionDetail($id){
        $response = new ApiResponse();

        if (empty($id)) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $userId = Yii::$app->user->id;

        $info['base'] = HllUserCar::infoById($id);
        $info['list'] = HllUserCarNotification::carNotificationList($userId,$id);
        $info['time'] = date('Y-m-d', time());

        if (empty($info) || empty($info['base'])) {
            $response->data = new ApiData(101, '数据错误');
            return $response;
        }

        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /***
     * 新增车辆提醒
     */
    public function actionAddRemind () {
        $response = new ApiResponse();
        $userId = Yii::$app->user->getId();
        $curTime = strtotime(date('Y-m-d', time()));

        $remind['notification_name'] = f_post('name');
        $remind['next_month'] = !empty(f_post('next_month')) ? f_post('next_month') : 0; //默认为0 不限制
        $remind['next_km'] = !empty(f_post('next_km')) ? f_post('next_km') : 0;    //默认为为0 不限制
        $remind['account_id'] = $userId;
        $remind['car_id'] = f_post('car_id', 0);
        $remind['last_date'] = f_post('last_date', 0);

        if($remind['car_id'] == 0 || empty($remind['notification_name'])) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        if(!empty($remind['last_date'])) {
            $time = strtotime(date($remind['last_date']));
            
            if ($time > $curTime) {
                $response->data = new ApiData(101, '上次保养时间不能超过当前时间');
                return $response;
            }
        } else {
            $remind['last_date'] = HllUserCar::find()->select('buy_date')->where(['id'=>$remind['car_id'], 'valid'=>1])->scalar();
        }

        //与next_km 关联
        $remind['last_km'] = 0;

        $now_km = HllUserCar::find()->select(['now_km'])->where(['id'=> $remind['car_id'], 'valid'=>1])->scalar();
        //新建车辆提醒
        $remind_info = new HllUserCarNotification();
        $trans = Yii::$app->db->beginTransaction();
        try{
            //更新hll_user_car_notification
            if($remind_info->load($remind,'') && $remind_info->save()){
                $log = new HllUserCarLog();
                $log->account_id = $userId;
                $log->car_id = $remind_info->car_id;
                $log->log_type = 3;
                $log->notification_id = $remind_info->id;
                $log->last_date = $remind_info->last_date;
                $log->last_km = $remind_info->last_km;
                $log->creater = $userId;
                //创建日志
                if($log->save()){
                    HllUserCarNotification::updateWarnning($remind_info->id, $now_km);

                    $trans->commit();
                    $response->data = new ApiData();
                    $response->data->info = $remind_info->id;
                }else{
                    throw new Exception('添加日志记录失败',112);
                }
            }else{
                throw new Exception('保存车辆提醒信息失败',111);
            }
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    /**
     * 更新车辆提醒信息
     * @return ApiResponse
     */
    public function actionRemind(){
        $response = new ApiResponse();

        $userId = Yii::$app->user->getId();
        $curTime = strtotime(date('Y-m-d', time()));

        $remind['id'] = f_post('id');
        $remind['car_id'] = f_post('car_id');
        $remind['next_month'] = !empty(f_post('next_month')) ? f_post('next_month') : 0;
        $remind['next_km'] = !empty(f_post('next_km')) ? f_post('next_km') : 0;
        $remind['last_date'] = f_post('last_date', 0);
        $remind['last_km'] = f_post('last_km', 0);

        if(empty($remind['car_id']) || empty($remind['id'])){
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        if(!empty($remind['last_date'])) {
            $time = strtotime(date($remind['last_date']));

            if ($time > $curTime) {
                $response->data = new ApiData(101, '最新保养时间不能超过当前时间');
                return $response;
            }
        } else {
            $remind['last_date'] = date('Y-m-d', time());
        }

        //查找当前车辆提醒
        $remind_info = HllUserCarNotification::find()->where(['id'=>$remind['id'],'valid'=>1])->one();
        if(!$remind_info){
            $response->data = new ApiData(110, '没有相关车辆提醒信息');
            return $response;
        }
        
        $trans = Yii::$app->db->beginTransaction();
        try{
            //更新hll_user_car_notification
            if($remind_info->load($remind,'') && $remind_info->save()){
                $log = new HllUserCarLog();
                $log->account_id = $userId;
                $log->car_id = $remind_info->car_id;
                $log->notification_id = $remind_info->id;
                $log->log_type = 1;
                $log->last_date = $remind_info->last_date;
                $log->last_km = $remind_info->last_km;
                $log->creater = $userId;
                //创建日志
                if($log->save()){
                    //更新提醒警告状态
                    $now_km = HllUserCar::find()->select(['now_km'])->where(['id'=>$remind['car_id'], 'valid'=>1])->scalar();
                    HllUserCarNotification::updateWarnning($remind['id'], $now_km);

                    $trans->commit();
                    $response->data = new ApiData();
                    $response->data->info = $remind_info->id;
                }else{
                    throw new Exception('添加日志记录失败',112);
                }
            }else{
                throw new Exception('保存车辆提醒信息失败',111);
            }
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    /**
     * 删除车辆信息
     * @param $id
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionDelete($id){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;

        $car = HllUserCar::find()->where(['id'=>$id,'account_id'=>$user_id,'valid'=>1])->one();
        if(!$car){
            $response->data = new ApiData(111,'没有相关车辆信息');
            return $response;
        }

        $trans = Yii::$app->db->beginTransaction();
        try{
            $car->valid = 0;
            if($car->save()){
                HllUserCarNotification::updateAllNotification($user_id,$id);
                $trans->commit();
                $response->data = new ApiData();
            }else{
                throw new Exception($car->getFirstErrors(),112);
            }
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    /**
     * 删除车辆提醒信息
     * @param $id
     * @return ApiResponse
     */
    public function actionDeleteRemind($id){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;

        $remind = HllUserCarNotification::find()->where(['id'=>$id, 'account_id'=>$user_id,'valid'=>1])->one();
        $car = HllUserCar::findOne(['id'=>$remind['car_id'],'valid'=>1]);
        if(!$remind){
            $response->data = new ApiData(101,'没有相关车提醒信息');
        }
        else if(!$car){
            $response->data = new ApiData(104,'没有相关车辆信息');
        }
        else{
            $trans = Yii::$app->db->beginTransaction();
            try{
                $remind->valid = 0;
                if($remind->save() && $car->save()){
                    $log = new HllUserCarLog();
                    $log->account_id = $user_id;
                    $log->car_id = $car->id;
                    $log->notification_id = $remind->id;
                    $log->log_type = 4;
                    $log->last_date = $remind->last_date;
                    $log->last_km = $remind->last_km;
                    if($log->save()){
                        $trans->commit();
                        $response->data = new ApiData();
                        $response->data->info = $remind->id;
                    }
                    else{
                        throw new Exception('添加日志记录失败',103);
                    }
                }
                else{
                    throw new Exception('修改车辆提醒信息失败',102);
                }
            }catch (Exception $e){
                $trans->rollBack();
                $response->data = new ApiData($e->getCode(),$e->getMessage());
            }
        }
        return $response;
    }

    /**
     * 更新里程
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionUpdateKm(){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $now_km = f_get('now_km',0);
        $id = f_get('id',0);

        $car = HllUserCar::find()->where(['id'=>$id,'valid'=>1])->one();
        if(!$car){
            $response->data = new ApiData(111,'没有相关车辆信息');
            return $response;
        }
        $trans = Yii::$app->db->beginTransaction();
        try{
            $car->now_km = $now_km;
            $car->record_km_date = date("Y-m-d");
            $car->alert_status = 0;
            if($car->save()){
                $log = new HllUserCarLog();
                $log->account_id = $user_id;
                $log->car_id = $id;
                $log->notification_id = 0;
                $log->log_type = 1;
                $log->last_km = $now_km;
                $log->last_date = date("Y-m-d");
                if($log->save()){
                    //更新车辆所有提醒状态
                    HllUserCarNotification::updateWarnningAll($id);

                    $trans->commit();
                    $response->data = new ApiData();
                    $response->data->info['date'] = $car->record_km_date; //返回服务器更新时间
                }else{
                    throw new Exception('添加日志记录失败',102);
                }
            }else{
                throw new Exception('更新里程失败',101);
            }
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    /**
     * 车辆详情
     * @param $id
     * @return ApiResponse
     */
    public function actionCarInfo($id){
        $response = new ApiResponse();
        $car = HllUserCar::infoById($id);
        if(!$car){
            $response->data = new ApiData(101, '无车辆信息');
        }else{
            $response->data = new ApiData();
            $response->data->info = $car;
        }
        return $response;
    }

    /**
     * ajax更新提醒列表
     * @params id 车辆id
     */
    public function actionAjaxUpdateList ($id) {
        $response = new ApiResponse();

        if (empty($id)) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $userId = Yii::$app->user->id;
        $list = HllUserCarNotification::carNotificationList($userId,$id);

        if ($list) {
            $response->data = new ApiData();
            $response->data->info['list'] = $list;
        } else {
            $response->data = new ApiData(101, '数据错误');
        }

        return $response;
    }
    
    /**
     * ajax获取某个车辆提醒的信息
     * @params tipId 车辆提醒id
     * @params carId 车辆id
     */
    public function actionAjaxGetNotification ($carId,$tipId) {
        $response = new ApiResponse();
        
        if (empty($tipId)) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $result = HllUserCarNotification::getInfoById($carId, $tipId);
        if ($result) {
            $response->data = new ApiData();
            $response->data->info['base'] = $result;
            $response->data->info['cur'] = date('Y-m-d', time());
        } else {
            $response->data = new ApiData(101,'数据错误');
        }
        
        return $response;
    }

    /**
     * 获取养护名称列表
     * @param $id
     * @return ApiResponse
     */
    public function actionGetNotificationByCar(){
        $response = new ApiResponse();
        $id = f_get('id',0);
        $info = [];
        $date = date("Y-m-d");

        //获取默认的第一个养护名称
        $first_notification = (new Query())->select(['notification_name','id',
                "DATE_ADD(last_date,INTERVAL next_month MONTH) as time"])
                ->from('hll_user_car_notification')->where(['car_id'=>$id,'valid'=>1])
                ->andWhere(['>',"DATE_ADD(last_date,INTERVAL next_month MONTH)",$date])
                ->orderBy(['time'=>SORT_ASC])->one();
        $notification =(new Query())->select(['notification_name','id',
            "DATE_ADD(last_date,INTERVAL next_month MONTH) as time"])
            ->from('hll_user_car_notification')->where(['car_id'=>$id,'valid'=>1])
            ->andWhere(['<>','id',$first_notification['id']])->all();
        array_unshift($notification,$first_notification);

        if(!$notification){
            $info['id'] = ['0'];
            $info['notification_name'] = ['其他'];
        }else{
            $info['id'] = array_column($notification,'id');
            $info['notification_name'] = array_column($notification,'notification_name');
            array_push($info['id'],'0');
            array_push($info['notification_name'],'其他');
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 获取上一次的养护信息
     * @return ApiResponse
     */
    public function actionGetLastNotification(){
        $response = new ApiResponse();

        $car_id = f_get('car_id',0);
        $notification_id = f_get('notification_id',0);

        if($car_id == 0){
            $response->data = new ApiData(101,'数据有误！');
            return $response;
        }

        $notification = (new Query())->select(['t1.next_month','t1.next_km', 't2.exec_shop'])
            ->from('hll_user_car_notification as t1')
            ->leftJoin('hll_user_car_log as t2','t2.notification_id = t1.id')
            ->where(['t1.id'=>$notification_id,'t1.valid'=>1])
            ->orderBy(['t1.created_at'=>SORT_DESC])->one();
        if(!$notification){
            $notification = [];
        }
        $car = HllUserCar::find()->select(['now_km','record_km_date'])->where(['id'=> $car_id, 'valid'=>1])->asArray()->one();
        $car['now_time'] = date('Y-m-d', time());
        $notification['time'] = $car;
        $response->data = new ApiData();
        $response->data->info = $notification;
        return $response;
    }

    /**
     * 添加养护记录
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionAddNotification(){
        $response = new ApiResponse();
        $data = f_post('data','0');
        $user_id = Yii::$app->user->id;
        $data['account_id'] = $user_id;
        if((integer)$data['notification_id'] == 0){
            $notification = new HllUserCarNotification();
            $notification->account_id = $user_id;
            $notification->car_id = $data['car_id'];
            $notification->notification_name = $data['notification_name'];
            $notification->last_km = $data['last_km'];
            $notification->last_date = $data['last_date'];
            $notification->next_month = $data['next_month'];
            $notification->next_km = $data['next_km'];
        }else{
            $notification = HllUserCarNotification::findOne(['id'=>$data['notification_id'],'valid'=>1]);
            if(!$notification){
                $response->data = new ApiData(101,'数据有误！');
                return $response;
            }else{
                $notification->last_km = $data['last_km'];
                $notification->last_date = $data['last_date'];
                $notification->next_month = $data['next_month'];
                $notification->next_km = $data['next_km'];
            }
        }

        $car = HllUserCar::findOne(['id'=>$data['car_id'],'valid'=>1]);
        if(!$car){
            $response->data = new ApiData(104,'数据有误！');
            return $response;
        }

        $trans = Yii::$app->db->beginTransaction();
        try{
            if($data['last_km'] > $car->now_km || strtotime($data['last_date']) > strtotime($car->record_km_date)){
                $car->record_km_date = strtotime($data['last_date']) > strtotime($car->record_km_date) ? $data['last_date'] : $car->record_km_date;
                $car->now_km = $data['last_km'] > $car->now_km ? $data['last_km'] : $car->now_km;
                if($car->save()){
                    HllUserCarNotification::updateWarnningAll($car->id);
                }else{
                    throw new Exception('修改车辆信息失败',105);
                }
            }
            if($notification->save()){
                $data['notification_id'] = $notification->id;
                $data['log_type'] = 2;
                $notification_log = new HllUserCarLog();
                if($notification_log->load($data,'') && $notification_log->save()){
                    $now_km = HllUserCar::find()->select(['now_km'])->where(['id'=> $data['car_id'], 'valid'=>1])->scalar();
                    HllUserCarNotification::updateWarnning($notification->id,$now_km);
                    $trans->commit();
                    $response->data = new ApiData();
                }
                else{
                    throw new Exception('添加养护设备',102);
                }
            }
            else{
                throw new Exception('更新养护信息失败',103);
            }
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getName());
        }
        return $response;
    }

    /**
     * 养护记录列表
     * @return ApiResponse
     */
    public function actionNotificationList(){
        $response = new ApiResponse();

        $id = f_get('id',0);
        $page = f_get('page',1);
        $query = (new Query())->select(['t1.exec_shop','t1.exec_fee','t1.last_date','t2.notification_name'])->from('hll_user_car_log as t1')
            ->leftJoin('hll_user_car_notification as t2','t2.id = t1.notification_id')
            ->where(['t1.car_id'=>$id,'t1.log_type'=>2,'t1.valid'=>1])->orderBy(['t1.created_at'=>SORT_ASC]);
        $info = $this->getDataPage($query,$page);

        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }
}