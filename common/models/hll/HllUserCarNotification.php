<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
use yii\base\Exception;
use yii\db\Query;

/**
 * This is the model class for table "hll_user_car_notification".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $car_id
 * @property string $notification_name
 * @property string $last_date
 * @property integer $last_km
 * @property integer $next_month
 * @property integer $next_km
 * @property integer $alert_status
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllUserCarNotification extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_user_car_notification';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'car_id', 'last_km', 'alert_status', 'valid'], 'integer'],
            [['car_id', 'notification_name'], 'required'],
            [['last_date', 'created_at', 'updated_at', 'next_month', 'next_km'], 'safe'],
            [['notification_name'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'car_id' => 'Car ID',
            'notification_name' => 'Notification Name',
            'last_date' => 'Last Date',
            'last_km' => 'Last Km',
            'next_month' => 'Next Month',
            'next_km' => 'Next Km',
            'alert_status' => 'Alert Status',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 获取默认车辆提醒
     * @param $user_id
     * @param $car_id
     * @return array
     */
    public static function getCarNotification($user_id,$car_id){
        $fields = ['notification_name','next_month','next_km'];
        $car = (new Query())->select(['brand_id','series_id'])->from('hll_user_car')
            ->where(['account_id'=>$user_id,'id'=>$car_id,'valid'=>1])->one();
        $data = (new Query())->select($fields)->from('hll_car_default_notification')
            ->where(['brand_id'=>0,'series_id'=>0,'valid'=>1])->all();
        $brand_data = static::getBrandNotification($fields,$car);
        foreach($data as &$item){
            foreach($brand_data as $key => $_v){
                if($item['notification_name'] == $_v['notification_name']){
                    $item = $_v;
                    unset($brand_data[$key]);
                }
            }
        }
        $data = array_merge($data,$brand_data);
        return $data;
    }

    /**
     * 添加默认车辆提醒
     * @param $user_id 当前用户
     * @param $car_id   车辆id
     * @param $km 车辆当前里程
     * @param $buy_date 车辆购买时间
     * @return int
     * @throws \yii\db\Exception
     */
    public static function setCarNotification($user_id,$car_id, $km, $buy_date){
        $notification_data = static::getCarNotification($user_id,$car_id);
        if(!$notification_data){
            return 1;
        }
        foreach($notification_data as &$item){
            $item['account_id'] = $user_id;
            $item['car_id'] = $car_id;
            $item['last_date'] = $buy_date; // 新建时 最后执行日期设置默认为当前时间
            $item['last_km'] = $km;
        }
        $fields = ['notification_name','next_month','next_km','account_id','car_id','last_date','last_km'];
        $command = Yii::$app->db->createCommand();
        $result = $command->batchInsert(HllUserCarNotification::tableName(),$fields,$notification_data)->execute();
        return $result;
    }

    /**
     * 删去所有车辆提醒
     * @param $user_id
     * @param $id
     * @return bool
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function updateAllNotification($user_id, $id){
        $notification = static::findAll(['car_id'=>$id,'account_id'=>$user_id]);
        if(!$notification){
            return true;
        }else{
            $trans = Yii::$app->db->beginTransaction();
            try{
                foreach($notification as &$item){
                    $item->valid = 0;
                    if($item->save()){
                        $log = new HllUserCarLog();
                        $log->account_id = $user_id;
                        $log->car_id = $id;
                        $log->log_type = 4;
                        $log->notification_id = $item->id;
                        $log->last_date = $item->last_date;
                        $log->last_km = $item->last_km;
                        if($log->save()){
                            continue;
                        }else{
                            throw new Exception($log->getFirstErrors(),102);
                        }
                    }else{
                        throw new Exception($item->getFirstErrors(),101);
                    }
                }
                $trans->commit();
                return true;
            }catch (Exception $e){
                $trans->rollBack();
                throw new Exception($e->getMessage(),$e->getCode());
            }
        }
    }

    /**
     * 获取特定的车辆提醒
     * @param $fields
     * @param $car
     * @return array
     */
    public static function getBrandNotification($fields,$car){
        $brand_data = (new Query())->select($fields)->from('hll_car_default_notification')
            ->where(['brand_id'=>$car['brand_id'],'series_id'=>0,'valid'=>1])->all();
        $series_data = (new Query())->select($fields)->from('hll_car_default_notification')
            ->where(['brand_id'=>$car['brand_id'],'series_id'=>$car['series_id'],'valid'=>1])->all();
        foreach($brand_data as &$item){
            foreach($series_data as $key => $_v){
                if($item['notification_name'] == $_v['notification_name']){
                    $item = $_v;
                    unset($series_data[$key]);
                }
            }
        }
        $brand_data = array_merge($brand_data,$series_data);
        return $brand_data;
    }

    /**
     * 某辆车的提醒列表
     * @param $user_id
     * @param $car_id
     * @params $now_km
     * @return
     */
    public static function carNotificationList($user_id,$car_id){
        $fields = ['id','notification_name','next_month','next_km','last_km', 'last_date', 'alert_status'];

        $data = (new Query())->select($fields)->from('hll_user_car_notification')
            ->where(['car_id'=>$car_id,'account_id'=>$user_id,'valid'=>1])
            ->orderBy(['alert_status'=>SORT_DESC])->all();

        foreach ($data as &$item) {
            $during_time = strtotime("+".$item['next_month']." months", strtotime($item['last_date']));
            $item['util_next_time'] = ($item['next_month'] != 0) ? date('Y-m-d',$during_time) : null;
            $item['util_next_km'] = ($item['next_km'] != 0) ? $item['last_km'] + $item['next_km'] : null;
        }
        return $data;
    }

    /**
     * 获取某个提醒的信息
     * @param $tipId 提醒id
     */
    public static function getInfoById($carId, $tipId) {
        $fields = ['account_id', 'car_id', 'last_date', 'last_km', 'next_month', 'next_km'];

        $result = HllUserCarNotification::find()->select($fields)->where(['id' => $tipId, 'car_id' => $carId, 'valid' => 1])->one();

        return $result;
    }

    /**
     * 更新某个提醒的警告状态
     * @params $now_km 车辆当前里程数
     */
    public static function updateWarnning ($tipId, $now_km) {
        $time = intval(time());
        $need_alert = 0;

        $data = HllUserCarNotification::find()->where(['id'=>$tipId, 'valid'=>1])->one();

        if ($data) {
            if ($data['next_month'] != 0) {
                $during_time = strtotime("+".$data['next_month']." months", strtotime($data['last_date']));
                ($during_time < $time) && $need_alert = 1;
            }

            if ($data['next_km'] != 0) {
                $during_km = (integer)$data['last_km'] + $data['next_km'];
                ($during_km <= (integer)$now_km) && $need_alert = 1;
            }

            $data->alert_status = $need_alert;
            $data->has_alert = ($need_alert == 1) ? $data->has_alert : 0;
            if ($data->save()) {
                static::updateCarStatus($data['car_id']);

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 更新某辆车全部提醒状态
     * $car_id 车辆id
     */
    public static function updateWarnningAll ($car_id) {
        $now_km = HllUserCar::find()->select(['now_km'])->where(['id'=>$car_id, 'valid'=>1])->scalar();
        if (empty($now_km)) return false;
        $arr = HllUserCarNotification::find()->select(['id'])->where(['car_id'=>$car_id, 'valid'=>1])->all();

        if ($arr) {
            foreach ($arr as &$item) {
                HllUserCarNotification::updateWarnning($item['id'], $now_km);
            }
            static::updateCarStatus($car_id);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 更新车辆警告状态和警告数
     * @params $car_id 车辆id
     */
    public static function updateCarStatus ($car_id) {
        $fields = ['alert_status', 'warnning_num'];

        $car = HllUserCar::find()->where(['id'=>$car_id, 'valid'=>1])->one();
        if (!$car) {
            return false;
        }

        $warnning_num = HllUserCarNotification::find()->select($fields)
                        ->where(['car_id'=>$car_id, 'valid'=>1, 'alert_status'=>1, 'has_alert' => 0])
                        ->count();

        $car['warnning_num'] = $warnning_num;
        $car['alert_status'] = ($warnning_num == 0) ? $car['alert_status'] : 0;

        if ($car->save()) {
            return true;
        } else {
            return false;
        }
    }
}
