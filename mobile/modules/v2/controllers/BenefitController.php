<?php

namespace mobile\modules\v2\controllers;

use common\models\hll\HllPublicBenefitDonate;
use mobile\modules\v2\models\ApiData;
use mobile\modules\v2\models\ApiResponse;
use Yii;
use mobile\components\ApiController;
use yii\base\Exception;
use yii\db\Query;
use common\components\WxpayV2;
use common\models\ecs\EcsUsers;
use common\models\ecs\EcsPayLog;
use common\models\hll\HllBill;
/**
 * Created by PhpStorm.
 * User: nancy
 * Date: 2017/10/13
 * Time: 9:56
 */

class BenefitController extends ApiController{

    /**
     *公益列表
     * @param $id
     * @return ApiResponse
     */
    public function actionIndex($id){
        $response = new ApiResponse();

        $list = (new Query())->select(['title','target_money','donate_money','donate_people'])
            ->from('hll_public_benefit')->where(['community_id'=>$id,'valid'=>1])->all();
        $response->data = new ApiData();
        $response->data->info = $list;
        return $response;
    }

    /**
     * 公益详情
     * @param $id
     * @return ApiResponse
     */
    public function actionDetail(){
        $response = new ApiResponse();

        $info = (new Query())->select(['target_money','donate_money','id','pb_detail',"round(donate_money * 100 / target_money,1) as percent"])
            ->from('hll_public_benefit')->where(['valid'=>1])->one();
        if(!$info){
            $response->data = new ApiData(101,'数据错误!');
        }else{
            $info['list'] = (new Query())->select(['t1.donate_money','t1.wish','t2.nickname','t2.headimgurl','t1.created_at'])
                ->from('hll_public_benefit_donate as t1')
                ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.user_id')
                ->where(['t1.pb_id'=>$info['id'],'t1.valid'=>1])
                ->orderBy(['created_at'=>SORT_DESC])
                ->all();

            $response->data = new ApiData();
            $response->data->info = $info;
        }
        return $response;
    }

    //微信支付参数
    public function actionWxPayParams($donateId)
    {
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;

        if (!$donateId) {
            $response->data = new ApiData(110, '参数错误');
            return $response;
        }

        $donate = (new Query())->select(['t2.title','t1.donate_money'])->from('hll_public_benefit_donate as t1')
            ->leftJoin('hll_public_benefit as t2','t2.id = t1.pb_id')
            ->where(['t1.id'=>$donateId,'t1.valid'=>0,'t2.valid'=>1])->one();

        $user = EcsUsers::getUser($user_id, ['t2.openid']);
        $user && $GLOBALS['_SESSION']['openId'] = $user['openid'];

        $apply['cash_fee'] = $donate['donate_money'];
        $apply['title'] = $donate['title'];
        $apply['bill_id'] = (new Query())->select('bill_id')->from('hll_bill')->where(['bill_sn' => $donateId,'bill_category'=>4])->scalar();
        $wxPay = new WxpayV2();
        $data = $wxPay->get_benefit_code($apply);
        $response->data = new ApiData();
        $response->data->info = $data;
        return $response;
    }

    /**
     * 捐款
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionDonate(){
        $response = new ApiResponse();

        $data = f_post('data',[]);
        $user_id = Yii::$app->user->id;

        $trans = Yii::$app->db->beginTransaction();
        try{
            if(!$data){
                throw new Exception('参数有误',100);
            }
            $pd_id = $data['id'];
            $money = $data['money'];
            $wish = $data['wish'];
            $title = (new Query())->select(['title'])->from('hll_public_benefit')
                ->where(['id'=>$pd_id,'valid'=>1])->scalar();
            if(!$title){
                throw new Exception('捐款信息有误',101);
            }
            $donate = new HllPublicBenefitDonate();
            $donate->user_id = $user_id;
            $donate->wish = $wish;
            $donate->pb_id = $pd_id;
            $donate->donate_money = $money;
            $donate->valid = 0;
            if($donate->save()){
                $bill = new HllBill();
                $bill->title = $title;
                $bill->user_id = $user_id;
                $bill->bill_sn = $donate->id;
                $bill->bill_category = 4;
                $bill->pay_id = 1;
                $bill->pay_name = '微信支付';
                $bill->point = 0;
                $bill->point_money = 0;
                $bill->discount = 1;
                $bill->bill_amount = $money;
                $bill->money_paid = $money;
                if($bill->save()){
                    $trans->commit();
                    $response->data = new ApiData();
                    $response->data->info = $donate->id;
                }else{
                    throw new Exception('创建账单记录失败',102);
                }
            }else{
                throw new Exception('创建捐款记录失败',103);
            }
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
       return $response;
    }
}