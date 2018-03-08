<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_user_equipment_log".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $equipment_id
 * @property integer $notification_id
 * @property string $exec_date
 * @property string $exec_content
 * @property string $exec_shop
 * @property string $exec_fee
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class UserEquipmentLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_user_equipment_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['account_id', 'equipment_id', 'notification_id', 'creater', 'updater', 'valid'], 'integer'],
            [['equipment_id', 'exec_date'], 'required'],
            [['exec_date', 'created_at', 'updated_at'], 'safe'],
            [['exec_fee'], 'number'],
            [['exec_content', 'exec_shop'], 'string', 'max' => 100],
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
            'notification_id' => 'Notification ID',
            'exec_date' => 'Exec Date',
            'exec_content' => 'Exec Content',
            'exec_shop' => 'Exec Shop',
            'exec_fee' => 'Exec Fee',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //获取设备养护信息
    public static function getEquipmentLog($equipment_id){
        $fields = ['exec_date','exec_content','exec_fee','exec_shop'];
        $notification_log = (new Query())->select($fields)->from('hll_user_equipment_log')
            ->where(['equipment_id'=>$equipment_id,'valid'=>1])->all();
        if(!$notification_log){
            return [];
        }
        return $notification_log;
    }

    //获取养护名称
    //type 1为字符串  2为数组
    public static function getMaintenanceContent($maintenance_content,$type){
        $maintenance_list = explode(',',$maintenance_content);
        $data = [];

        if(empty($maintenance_content)){
            return $data;
        }else{
            foreach($maintenance_list as $item){
                $value = (new Query())->select(['kv_value'])->from('hll_kv')
                    ->where(['kv_type'=>2,'kv_key'=>$item,'valid'=>1])->scalar();
                array_push($data, $value);
            }
            $data = $type == 1 ? implode(',',$data) : $data;
            return $data;
        }
    }
}
