<?php
namespace mobile\modules\v2\controllers;

use common\models\ecs\EcsGoods;
use common\models\hll\HllUserPoints;
use common\models\hll\HllUserPointsLog;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use Yii;
use yii\base\Exception;
use yii\db\Query;
use common\models\ecs\EcsUsers;
use common\components\WxTmplMsg;
/**
 * Created by PhpStorm.
 * User: kaikai.qin
 * Date: 2016/11/4
 * Time: 9:22
 */
class CashierController extends ApiController
{

    /**
     * 商家收银台信息
     * @param $id  Business ID
     * @return ApiResponse
     * @author zend.wang
     * @time 2017-03-07 15:00
     */
    public function actionIndex($id){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;

        try {

            $info = (new Query())->select(['name', 'logo'])
                ->from('hll_business as t1')->where(['id' => $id,'is_show'=>1])->one();

            if (!$info) {
                throw new Exception("参数异常", 100);
            }
            $community = HllUserPoints::getCommunityByBusiness($id);
            $info['payment'] = [
                "name" => "友元",
                "ename" => "points",
                "avaiable_points" => HllUserPoints::getUserPoints($user_id,$community, 1), // 消费体验友元 + 通用友元
                "default" => true,
                "enabled" => true,
                "exchange_rate" => 100  //友元:现金 = 100:1
            ];
            $response->data = new ApiData();
            $response->data->info = $info;
        }catch (Exception $e) {
                $response->data = new ApiData($e->getCode(),$e->getMessage());
        }

        return $response;
    }

    /**
     * 收银台商家商品列表
     * @param $id
     * @return ApiResponse
     */
    public function actionList($id){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;

        try {

            $info = (new Query())->select(['name', 'logo'])
                ->from('hll_business as t1')->where(['id' => $id,'is_show'=>1])->one();

            if (!$info) {
                throw new Exception("参数异常", 100);
            }
            $community = HllUserPoints::getCommunityByBusiness($id);
            $info['payment'] = [
                "avaiable_points" => HllUserPoints::getUserPoints($user_id,$community, 1), // 消费体验友元 + 通用友元
            ];
            $info['list'] = EcsGoods::getGoodsListByBusinessId($id);
            $response->data = new ApiData();
            $response->data->info = $info;
        }catch (Exception $e) {
            Yii::warning('收银台异常：',$e);
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }

        return $response;
    }

    /**
     * 支付接口
     *
     * @return ApiResponse
     * @author zend.wang
     * @time 2016-11-25 15:00
     */
    public function actionPay(){

        $payment = f_post('payment',"points");//支付方式 默认友元
        $money = f_post('money',0);
        $remark = f_post('remark','');
        $userId = Yii::$app->user->id;

        $response = new ApiResponse();
        $trans= Yii::$app->db->beginTransaction();
        try {
            if($payment == 'points') {
                $businessId = intval(f_post('id',0));
                if(!$businessId |!$money)  {
                    throw new Exception("参数异常",100);
                }
                $result = HllUserPointsLog::businessExpend($userId,$businessId,$money,$remark);
                $point = (new Query())->select(["SUM(point) as point",'user_id'])->from('hll_user_points_log')
                    ->where(['unique_id' => $result, 'valid' => 1])->one();
                $left_point = HllUserPoints::getUserPoints($userId);

                $user = EcsUsers::getUser($point['user_id'], ['t1.user_id', 't2.openid','t2.nickname']);
                $title = (new Query())->select(['name'])
                    ->from('hll_business')->where(['id' => $businessId,'is_show'=>1])->scalar();
                $title .= '线下消费';
                WxTmplMsg::PointChangeNotice($user,$point['point'],$left_point,$title);
                $trans->commit();
                $response->data = new ApiData(0);
                $response->data->info = $result;
            }
        }catch (Exception $e) {
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    /**
     * 支付详情
     */
    public function actionDetail($id) {
        $response = new ApiResponse();

        $data = (new Query())->select(["SUM(t1.point) as point",'t1.unique_id', 't1.created_at', 't2.name', 't2.logo'])
            ->from('hll_user_points_log as t1')->leftJoin('hll_business as t2', 't1.business_id = t2.id')
            ->where(['t1.unique_id' => $id, 't1.valid' => 1, 't2.is_show' => 1])->one();
        if (!$data) {
            $response->data = new ApiData(100, '数据出错');
        } else {
            $response->data = new ApiData();
            $response->data->info = $data;
        }
        return $response;
    }

    /**
     * 收银台购买商品支付
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionOrderPay(){
        $data = [];
        $user_info = parent::$sessionInfo;
        $data['discount'] = $user_info['discount'];
        $data['money'] = f_post('money',0);
        $userId = Yii::$app->user->id;
        $data['user_id'] = $userId;
        $data['list'] = f_post('list','');
        $data['business_id'] = f_post('business_id',0);
        $response = new ApiResponse();
        $trans= Yii::$app->db->beginTransaction();
        try {
            if($data['money'] == 0 || $data['list'] == '')  {
                throw new Exception("请选择商品，",100);
            }
            EcsGoods::addOrderGoods($data);
            $result = HllUserPointsLog::businessExpend($userId,$data['business_id'],$data['money']);
            $point = (new Query())->select(["SUM(point) as point",'user_id'])->from('hll_user_points_log')
                ->where(['unique_id' => $result, 'valid' => 1])->one();
            $left_point = HllUserPoints::getUserPoints($userId);

            $user = EcsUsers::getUser($point['user_id'], ['t1.user_id', 't2.openid','t2.nickname']);
            $title = (new Query())->select(['name'])
                ->from('hll_business')->where(['id' => $data['business_id'],'is_show'=>1])->scalar();
            $title .= '线下消费';
            WxTmplMsg::PointChangeNotice($user,$point['point'],$left_point,$title);
            $trans->commit();
            $response->data = new ApiData(0);
            $response->data->info = $result;

        }catch (Exception $e) {
            $trans->rollBack();
            Yii::warning('收银台购买商品支付:'.$e);
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }
}