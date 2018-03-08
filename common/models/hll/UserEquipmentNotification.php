<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
use yii\base\Exception;
use yii\db\Query;

/**
 * This is the model class for table "hll_user_equipment_notification".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $equipment_id
 * @property integer $maintenance_content
 * @property string $last_date
 * @property integer $next_month
 * @property integer $alert_status
 * @property integer $is_send
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class UserEquipmentNotification extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_user_equipment_notification';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'equipment_id', 'next_month', 'alert_status', 'is_send', 'creater', 'updater', 'valid'], 'integer'],
            [['equipment_id'], 'required'],
            [['last_date', 'created_at', 'updated_at', 'maintenance_content'], 'safe'],
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
            'equipment_id' => 'Equipment ID',
            'maintenance_content' => 'Maintenance Content',
            'last_date' => 'Last Date',
            'next_month' => 'Next Month',
            'alert_status' => 'Alert Status',
            'is_send' => 'Is Send',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 获取默认设备提醒
     * @param $equipment
     * @return array|bool
     */
    public static function getEquipmentNotification($equipment){
        $default_notification = (new Query())->select(['maintenance_content','next_month'])
            ->from('hll_equipment_default_notification')
            ->where(['type_id'=>$equipment['equipment_type'],'brand_id'=>$equipment['brand'],'series_id'=>$equipment['model'],'valid'=>1])
            ->one();
        if(!$default_notification){
            $default_notification = (new Query())->select(['maintenance_content','next_month'])
                ->from('hll_equipment_default_notification')
                ->where(['type_id'=>$equipment['equipment_type'],'brand_id'=>$equipment['brand'],'series_id'=>0,'valid'=>1])
                ->one();
        }
        if(!$default_notification){
            $default_notification = (new Query())->select(['maintenance_content','next_month'])
                ->from('hll_equipment_default_notification')
                ->where(['type_id'=>$equipment['equipment_type'],'brand_id'=>0,'series_id'=>0,'valid'=>1])
                ->one();
        }
        return $default_notification;
    }

    /**
     * 设置默认提醒
     * @param $equipment
     * @param $user_id
     * @return bool
     * @throws Exception
     */
    public static function setEquipmentNotification($equipment,$user_id){
        $default_notification = static::getEquipmentNotification($equipment);
        if(!$default_notification){
            return true;
        }
        $equipment_notification = new UserEquipmentNotification();
        $default_notification['account_id'] = $user_id;
        $default_notification['last_date'] = $equipment->buy_date;
        $default_notification['equipment_id'] = $equipment->id;
        $default_notification['alert_status'] = static::diffBetweenDates($default_notification) == 0 ? 1 : 0;
        try{
            if($equipment_notification->load($default_notification,'') && $equipment_notification->save()){
                return true;
            }else{
                throw new Exception($equipment_notification->getFirstErrors(),'110');
            }
        }catch (Exception $e){
            throw new Exception($e->getMessage(),$e->getCode());
        }
    }

    /**
     * 获取设备养护详情
     * @param $id
     * @return array|bool
     */
    public static function getEquipmentInfoById($id){
        $equipment_info = (new Query())->select(['id','next_month','alert_status','last_date'])
            ->from('hll_user_equipment_notification')->where(['equipment_id'=>$id,'valid'=>1])->one();
        if(!$equipment_info){
            return [];
        }
        $alert_info['alert_status'] = $equipment_info['alert_status'];
        $alert_info['id'] = $equipment_info['id'];
        $alert_info['left_date'] = static::diffBetweenDates($equipment_info);
        return $alert_info;
    }

    /**
     * 获取下次保养天数
     * @param $equipment
     * @return float|int
     */
    public static function diffBetweenDates($equipment){
        $now_date = strtotime(date("Y-m-d"));
        $next_date = strtotime('+'.$equipment['next_month'].'month',strtotime($equipment['last_date']));
        if($now_date >= $next_date){
            $diff_date = 0;
        }else{
            $diff_date = ($next_date - $now_date) / 86400;
        }
        return $diff_date;
    }

    /**
     * 更新提醒状态
     * @return int
     * @throws \yii\db\Exception
     */
    public static function updateEquipmentInfo(){
        $notification_id = (new Query())->select(['id','next_month','last_date'])
            ->from('hll_user_equipment_notification')->where(['is_send'=>0,'valid'=>1])->all();
        $data = [];
         foreach($notification_id as $item){
             $left_date = static::diffBetweenDates($item);
             if($left_date == 0){
                 array_push($data,$item['id']);
             }
         }
        if(!$data){
            return [];
        }
        else{
            $id = implode(',',$data);
            $sql = "update hll_user_equipment_notification set alert_status = 1 where id IN ($id)";
            Yii::$app->db->createCommand($sql)->execute();
            return $data;
        }
    }
}
