<?php
namespace mobile\modules\v2\controllers;

use common\models\hll\HllEquipmentBrand;
use common\models\hll\HllEquipmentServiceCenterFeedback;
use common\models\hll\UserEquipment;
use common\models\hll\UserEquipmentLog;
use common\models\hll\UserEquipmentNotification;
use Yii;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\base\Exception;
use yii\db\Query;

/**
 * 设施控制器
 * Created by PhpStorm.
 * User: kaikai.qin
 * Date: 2016/10/10
 * Time: 11:58
 */
class FacilitiesController extends ApiController{

    /**
     * 设施列表
     * @param $id int 房产编号
     * @param $keywords string 搜索关键字
     * @return ApiResponse
     * User: kaikai.qin
     * Date: 2016/10/10
     * Time: 11:58
     */
    public function actionIndex($id){
        $response = new ApiResponse();
        $date = date('Y-m-d',time());
        $result = array();
        if(!$id){
            $response->data = new ApiData('110','缺少关键数据id！');
            return $response;
        }
        //查询所选房产下未完善的设备信息
        $info['unfinish'] = false;
        $num = UserEquipment::find()->where(['address_id'=>$id,'status'=>1,'valid'=>1])->count();
        if($num){
            $info['unfinish'] = true;
        }
        $info['num'] = $num;
        //查询所选房产下已完善的设备信息
        $data = UserEquipment::getEquipmentByAddress($id);
        if(!$data){
            $response->data = new ApiData('110','暂无数据！');
            return $response;
        }
        //对数据按类分组
        foreach($data as &$item){
            $item['equipment_info'] = UserEquipmentNotification::getEquipmentInfoById($item['id']);
            $item['out_date'] = ($item['guarantee_time'] >= $date)?false:true;
            $result[$item['kv_value']][] = $item;
        }
        $info['list'] = $result;
        $response->data = new ApiData();
        $response->data->info=$info;
        return $response;
    }

    /**
     * 未完善的设备详情
     * @return ApiResponse
     */
    public function actionUnfinish(){
        $response = new ApiResponse();
        $id = Yii::$app->request->get('id');
        if(!$id){
            $response->data = new ApiData('110','缺少关键数据id！');
            return $response;
        }
        $list = UserEquipment::find()->select(['bill_pics','created_at'])
            ->where(['address_id'=>$id,'status'=>1,'valid'=>1])->asArray()->all();
        if(!$list){
            $response->data = new ApiData('110','暂无数据！');
            return $response;
        }
        foreach($list as &$item){
            $date = explode(' ',$item['created_at']);
            $item['created_at'] = $date[0];
        }
        $info['list'] = $list;
        $response->data = new ApiData();
        $response->data->info=$info;
        return $response;
    }

    /**
     * 设施详情
     * @param $id int 设施编号
     * @return ApiResponse
     * User: kaikai.qin
     * Date: 2016/10/10
     * Time: 11:58
     */
    public function actionDetail(){

        $response = new ApiResponse();
        $id = Yii::$app->request->get('id');
        if(!$id){
            $response->data = new ApiData('110','缺少关键数据id！');
            return $response;
        }
        $date = date('Y-m-d');
        //查询设备信息
        $info = (new Query())->select(['t1.bill_pics','t1.brand','t1.price','t1.buy_date',
            't1.model', 't4.name', 't1.guarantee_time','t1.shop','t2.kv_value','t2.kv_key','t3.address_desc'])
            ->from('hll_user_equipment as t1')
            ->leftJoin('hll_kv as t2','t2.kv_key = t1.equipment_type')
            ->leftJoin('hll_user_address as t3','t3.address_id = t1.address_id')
            ->leftJoin('hll_equipment_brand as t4', 't4.id = t1.brand')
            ->where(['t1.id'=>$id,'t1.status'=>2,'t1.valid'=>1,'t2.valid'=>1,'t3.valid'=>1,'t4.valid'=>1])->one();
        if(!$info){
            $response->data = new ApiData('110','暂无数据！');
            return $response;
        }
        $info['out_date'] = ($info['guarantee_time'] >= $date)?false:true;
        $response->data = new ApiData();
        $response->data->info=$info;
        return $response;
    }

    /**
     * 添加设施
     * @return ApiResponse
     * User: kaikai.qin
     * Date: 2016/10/10
     * Time: 11:58
     */
    public function actionCreate(){
        $response = new ApiResponse();
        $data = Yii::$app->request->post('data');

        $model = new UserEquipment();
        $data['account_id'] = Yii::$app->user->id;
        if($model->load($data,'')){
            if($model->save()){
                $response->data = new ApiData('0','添加数据成功！');
            }else{
                $response->data = new ApiData('110','添加数据失败！');
            }
        }else{
            $response->data = new ApiData('112','加载数据失败！');
        }

        return $response;
    }

    /**
     * 手动添加设施
     * @return ApiResponse
     * User: kaikai.qin
     * Date: 2016/10/10
     * Time: 11:58
     */
    public function actionCreateByUser(){
        $response = new ApiResponse();
        $data = Yii::$app->request->post('data');

        $model = new UserEquipment();
        if($data['brand'] == 0){
            $data['brand'] = HllEquipmentBrand::getBrandByName($data['brand_name'],$data['equipment_type']);
        }
        $data['account_id'] = Yii::$app->user->id;
        $data['status'] = 2;

        $trans = Yii::$app->db->beginTransaction();
        try{
            if($model->load($data,'')){
                if($model->save()){
                    UserEquipmentNotification::setEquipmentNotification($model,$data['account_id']);
                    $response->data = new ApiData('0','添加数据成功！');

                    $trans->commit();
                }
                else{
                    throw new Exception('添加数据失败！','111');
                }
            }else{
                throw new Exception('加载数据失败！','112');
            }
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }

        return $response;
    }

    /**
     * 编辑设施信息
     * @param $id int 设施编号
     * @return ApiResponse
     * User: kaikai.qin
     * Date: 2016/10/10
     * Time: 11:58
     */
    public function actionUpdate(){
        $response = new ApiResponse();
        $data = Yii::$app->request->post('data');
        $id = Yii::$app->request->post('id');

        $model = UserEquipment::findOne($id);
        if($data['brand'] == 0){
            $data['brand'] = HllEquipmentBrand::getBrandByName($data['brand_name'],$data['equipment_type']);
        }
        if($model->load($data,'')){
            if($model->save()){
                $response->data = new ApiData('0','修改数据成功！');
            }else{
                $response->data = new ApiData('110',$model->getErrors());
            }
        }else{
            $response->data = new ApiData('112','加载数据失败！');
        }

        return $response;
    }

    /**
     * 删除设备
     * @return ApiResponse
     */
    public function actionDelete(){
        $response = new ApiResponse();
        $id = Yii::$app->request->get('id');
        $model = UserEquipment::findOne(['id'=>$id]);
        $model->valid = 0;
        if($model->save()){
            $response->data = new ApiData('0','删除成功！');
            $response->data->info = $model->address_id;
        }else{
            $response->data = new ApiData('110','删除失败！');
        }
        return $response;
    }

    /**
     * 获取设备类型列表
     */
    public function actionEquipmentType()
    {
        $response = new ApiResponse();
        $address = (new Query())->select(['kv_value','kv_key'])->from('hll_kv')
            ->where(['kv_type'=>1,'valid'=>1])->all();
        if(!$address){
            $response->data = new ApiData('110', '无相关数据');
            return $response;
        }
        $info['typeNum'] = array_column($address, 'kv_key');
        $info['typeName'] = array_column($address, 'kv_value');
        $response->data = new ApiData();
        $response->data->info=$info;
        return $response;
    }

    /**
     * 获取养护信息
     * @param int $id
     * @return ApiResponse
     */
    public function actionEquipmentNotification($id){
        $response = new ApiResponse();

        $notification_log = UserEquipmentLog::getEquipmentLog($id);
        $advice = UserEquipmentNotification::find()->select(['maintenance_content'])
            ->where(['equipment_id'=>$id,'valid'=>1])->scalar();
        $advice = UserEquipmentLog::getMaintenanceContent($advice,1);
        $response->data = new ApiData();
        $response->data->info['list'] = $notification_log;
        $response->data->info['advice'] = $advice;
        $response->data->info['desc'] = UserEquipment::getEquipById($id, ['t1.model', 't3.name']);
        return $response;
    }

    /**
     * 添加养护信息
     * @param int $id
     * @return ApiResponse
     */
    public function actionAddNotification(){
        $response = new ApiResponse();

        if(Yii::$app->request->Post('data')){
            $id = f_post('id');
            $data = f_post('data');
            $trans = Yii::$app->db->beginTransaction();
            try{
                $notification = UserEquipmentNotification::find()
                    ->select(['next_month','maintenance_content','account_id','id','equipment_id'])
                    ->where(['equipment_id'=>$id,'valid'=>1])->one();
                if(!$notification){
                    $notification = new UserEquipmentNotification();
                    $notification->account_id = Yii::$app->user->id;
                    $notification->maintenance_content = '';
                    $notification->equipment_id = $id;
                }
                $notification->next_month = $data['next_month'];
                $notification->last_date = $data['last_date'];
                $notification->alert_status = UserEquipmentNotification::diffBetweenDates($data) == 0 ? 1 : 0;
                $notification->is_send = 0;
                if($notification->save()){
                    $notification_log = new UserEquipmentLog();
                    $notification_log->account_id = $notification->account_id;
                    $notification_log->equipment_id = $id;
                    $notification_log->notification_id = $notification->id;
                    $notification_log->exec_date = $data['last_date'];
                    $notification_log->exec_shop = $data['shop'];
                    $notification_log->exec_fee = $data['fee'];
                    $notification_log->exec_content = $data['content'];
                    if($notification_log->save()){
                        $trans->commit();
                        $response->data = new ApiData();
                    }else{
                        throw new Exception('保存日志失败',102);
                    }
                }else{
                    $response->data = new ApiData('保存养护信息失败',103);
                }
            }catch (Exception  $e){
                $trans->rollBack();
                $response->data = new ApiData($e->getCode(),$e->getName());
            }
        }else{
            $id = f_get('id');
            $notification = UserEquipmentNotification::find()
                ->select(['next_month','maintenance_content','account_id','id','equipment_id'])
                ->where(['equipment_id'=>$id,'valid'=>1])->one();

            if (!empty($notification)) {
                $content = UserEquipmentLog::getMaintenanceContent($notification['maintenance_content'],2);
            } else {
                $content = [];
                $notification['next_month'] = '';
            }


            $response->data = new ApiData();
            $response->data->info['list'] = $content;
            $response->data->info['month'] = $notification['next_month'];
            $response->data->info['date'] = date('Y-m-d');
        }

        return $response;
    }

    /**
     * 获取设备维修信息
     * @return ApiResponse
     */
    public function actionEquipmentFix(){
        $response = new ApiResponse();
        $id = f_get('id',0);

        $equipment_fix = UserEquipment::getEquipmentFix($id);
        $response->data = new ApiData();
        $response->data->info = $equipment_fix;
        return $response;
    }

    /**
     * 维修报错列表
     * @return ApiResponse
     */
    public function actionQuestionList(){
        $response = new ApiResponse();

        $kv = (new Query())->select(['kv_key','kv_value'])->from('hll_kv')
            ->where(['kv_type'=>3,'valid'=>1])->all();

        $info['key'] = array_column($kv,'kv_key');
        $info['value'] = array_column($kv,'kv_value');
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 提交报错信息
     * @return ApiResponse
     */
    public function actionEquipmentApply(){
        $response = new ApiResponse();
        $esc_id = f_get('id',0);
        $feedback_reason = f_get('reason',0);

        $feedback = new HllEquipmentServiceCenterFeedback();
        $feedback->esc_id = $esc_id;
        $feedback->feedback_reason = $feedback_reason;
        $feedback->feedback_time = date("Y-m-d H:i:s");
        if($feedback->save()){
            $response->data = new ApiData();
            $response->data->info = $feedback->id;
        }else{
            $response->data = new ApiData(101,'保存错误');
        }
        return $response;
    }

    /**
     * 获取品牌列表
     * @param $type_id
     * @return ApiResponse
     */
    public function actionEquipmentList($type_id){
        $response = new ApiResponse();

        $kv = (new Query())->select(['id','name'])->from('hll_equipment_brand')
            ->where(['type_id'=>$type_id,'valid'=>1])->all();

        $info['id'] = array_column($kv,'id');
        $info['name'] = array_column($kv,'name');
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }
}