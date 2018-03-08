<?php
namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
use yii\db\Query;

/**
 * This is the model class for table "hll_user_equipment".
 *
 * @property integer $id
 * @property integer $address_id
 * @property integer $account_id
 * @property string  $bill_pics
 * @property integer $equipment_type
 * @property string $brand
 * @property string $model
 * @property string $price
 * @property string $buy_date
 * @property string $guarantee_time
 * @property string $shop
 * @property integer $status
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class UserEquipment extends ActiveRecord
{
    /**
     * Created by PhpStorm.
     * User: kaikai.qin
     * Date: 2016/10/12
     * Time: 9:34
     */
    public static function tableName() {
        return 'hll_user_equipment';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['address_id','account_id'], 'required'],
            [['id','address_id','account_id','equipment_type','status'], 'integer'],
            [['brand','model','price','shop'], 'string', 'max' => 20],
            [['buy_date','guarantee_time'], 'safe'],
            [['bill_pics'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '物品共享信息编号',
            'address_id' => '归属房产编号',
            'account_id' => '用户编号',
            'bill_pics' => '发票照片',
            'equipment_type' => '设备类型',
            'brand' => '设备品牌',
            'model' => '设备型号',
            'prive' => '购买价格',
            'buy_date' => '购买日期',
            'guarantee_time' => '保修期限',
            'shop' => '商家',
            'status' => '物品状态',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '有效',
        ];
    }

    /**
     * 获取执行id下的设备信息
     */
    public static function getEquipById($id, $fields=null) {
        if (!$fields) {
            $fields = ['t1.id', 't1.guarantee_time', 't1.model','t1.shop', 't2.kv_value','t3.name'];
        }

        $data = (new Query())->select($fields)
            ->from('hll_user_equipment as t1')
            ->leftJoin('hll_kv as t2', 't2.kv_key = t1.equipment_type')
            ->leftJoin('hll_equipment_brand as t3', 't3.id = t1.brand')
            ->where(['t1.id'=>$id,'t1.status'=>2, 't1.valid'=>1])
            ->andWhere(['t2.kv_type'=>1, 't2.valid'=>1,'t3.valid'=>1])->one();
        return $data;
    }


    /**
     * 获取指定房产下完善的设备信息
     * @param $id
     * @return array
     */
    public static function getEquipmentByAddress($id, $fields=null){
        if (!$fields) {
            $fields = ['t1.id', 't1.guarantee_time', 't1.model','t1.shop', 't2.kv_value','t3.name'];
        }

        $data = (new Query())->select($fields)
            ->from('hll_user_equipment as t1')
            ->leftJoin('hll_kv as t2', 't2.kv_key = t1.equipment_type')
            ->leftJoin('hll_equipment_brand as t3', 't3.id = t1.brand')
            ->where(['t1.address_id'=>$id,'t1.status'=>2, 't1.valid'=>1])
            ->andWhere(['t2.kv_type'=>1, 't2.valid'=>1,'t3.valid'=>1])->all();
        return $data;
    }

    /**
     * 获取设备的官方客服
     * @param $id
     * @return array|bool
     */
    public static function getEquipmentFix($id){
        $equipment_info = (new Query())->select(['t1.brand','t2.city'])->from('hll_user_equipment as t1')
            ->leftJoin('ecs_user_address as t2','t2.address_id = t1.address_id')
            ->where(['t1.id'=>$id,'t1.valid'=>1,'t2.valid'=>1])->one();
        if(!$equipment_info){
            return [];
        }
        $fix['service'] = (new Query())->select(['t1.service_time','t1.cust_service_phone',"CONCAT(t1.name,t2.kv_value) as name"])
            ->from('hll_equipment_brand as t1')->leftJoin('hll_kv as t2','t2.id = t1.type_id')
            ->where(['t1.id'=>$equipment_info['brand'],'t1.valid'=>1,'t2.valid'=>1])->one();
        $fix['address'] = (new Query())->select(['id','company_name','company_address','phone'])
            ->from('hll_equipment_service_center')
            ->where(['city_id'=>$equipment_info['city'],'brand_id'=>$equipment_info['brand'],'valid'=>1])
            ->orderBy(['company_address'=>SORT_ASC])->all();
        $fix['service']['num'] = count($fix['address']);
        return $fix;
    }
}