<?php

namespace mobile\modules\rest\controllers;

use common\models\ar\service\ServiceEngageCustomer;
use Yii;
use yii\db\Query;
use common\components\Util;
use mobile\components\ActiveController;
use common\models\ar\service\Catalog;
use common\models\ar\service\Service;
use common\models\ar\service\ServiceQuote;
use common\models\ar\service\ServiceQuoteSchedule;
use common\models\ar\service\ServiceQuoteScheduleDetail;
use common\models\ar\order\ServiceOrder;
use common\models\ar\order\ServiceOrderAddress;
use common\models\ar\order\ServiceOrderQuote;
use common\models\ar\order\ServiceOrderSchedule;

class ServiceController extends ActiveController {

    /**
     * 获取所有分类
     * @return type
     */
    public function actionCategory($fixedCatalog=0) {
        $command = Catalog::find();
        if ($fixedCatalog) {
            $command->where(['fixed_catalog'=>$fixedCatalog]);
        }
        $data = $command->orderBy(['parent_id'=>SORT_ASC])->asArray()->all();
        return $this->renderRest($data);
    }

    /**
     * 分类下的服务列表
     * @param type $cid     分类id
     */
    public function actionCategoryServiceList($cid) {
        $data = Catalog::find()->joinWith(['service'])->where(['catalog.parent_id'=>$cid])->asArray()->all();
        return $this->renderRest($data);
    }

    /**
     * 获取服务详情
     * @param type $id
     */
    public function actionDetail($id=0, $catalogId=0) {
        $command = Service::find()->joinWith(['quote', 'time']);
        if ($id) {
            $command->where(['service.id'=>$id]);
        } else if ($catalogId) {
            $command->where(['service.catalog_id'=>$catalogId]);
        }
        $data = $command->asArray()->one();
        $data['pics'] = \yii\helpers\Json::decode($data['pics']);
        return $this->renderRest($data);
    }

    /**
     * 获取活动排班情况(海豚计划)
     * @param $id service_id
     */
    public function actionActivityDetail($id) {
        $info = (new Query())->select(['t1.id','t1.title', 't1.pics'])
            ->from('service as t1')
            ->where(['t1.status'=>1, 't1.id'=>$id])
            ->one();
        if($id == "155") {
            $data = ServiceQuoteSchedule::activityInfo($id);
        }else {
            $data = ServiceQuoteSchedule::activityManageInfo($id);
        }
        return $this->renderRest(['info'=>$info, 'manage'=>$data]);
    }

    /**
     * 判断用户是否可以预约服务
     * @param $id 活动servie_id
     * @param $data 用户输入手机号
     */
    public function actionAllowJoinActivity($id,$data) {
        $num = 1; //报名人数
        $model = 0;//不能参加
        $result = ServiceEngageCustomer::find()->where('service_id='.$id)->andWhere('cust_mobile='.$data)->andWhere('valid=1')->one();
        if($result) {
            if($result->account_id==null) {
                $result->account_id = Yii::$app->user->id;
                $result->save();
                $model = 1;//能参加
                $num = $result->join_num;
            }else if($result->account_id != Yii::$app->user->id){
                $model = 2;//被占用
            }else {
                if($result->is_join == 1) {
                    $model = 3;//已经参加
                }else {
                    $model = 4;//能参加但未选择订单
                    $num = $result->join_num;
                }
            }
        }
        return $this->renderRest(['model'=>$model, 'num'=>$num]);
    }

    /**
     * 判断用户是否参加活动
     * return result(bool) sqs_id quote_id
     * @param $id 活动servie_id
     * @param $accountId 用户id
     */
    public function actionIsJoinActivity($id, $accountId) {
        $accountId = empty($accountId)?Yii::$app->user->id:'';
        $sqs = '';
        $quote = '';
        $result = ServiceEngageCustomer::find()->where('service_id='.$id)->andWhere('account_id='.$accountId)->andWhere(['is_join'=>1, 'valid'=>1])->count();
        $result = (bool)$result;
        if($result) {
            $sqs = ServiceEngageCustomer::find()->where('service_id='.$id)->andWhere('account_id='.$accountId)->andWhere('valid=1')->select('sqs_id')->one();
            $quote = ServiceQuoteSchedule::find()->where('id='.$sqs->sqs_id)->andWhere('valid=1')->select('service_quote_id')->one();
        }
        return $this->renderRest(['result'=>$result, 'sqs'=>$sqs, 'quote'=>$quote]);
    }

    /**
     * 用户选择年龄段
     * @param $data 年龄信息
     * @param $id 服务编号
     */
    public function actionChooseAge($data, $id) {
        $model = ServiceEngageCustomer::find()->where(['service_id'=>$id, 'account_id'=>Yii::$app->user->id, 'valid'=>1])->one();
        if($model) {
            $model->age_range = $data;
            $model->save();
        }
        return $this->renderRest($model);
    }

    /**
     * 获取service富文本内容
     */
    public function actionServiceRichText($id) {
        $data = [];
        $data = Service::find()->where(['id'=>$id, 'status'=>1])->select(['description'])->one();
        return $this->renderRest($data);
    }

    /**
     * 返回服务开始时间和是否过期
     * @param serviceId 活动服务id
     */
    public function actionReturnSerTime($serviceId) {
        $service = Service::find()->where(['id'=>$serviceId])->select(['stop_time', 'start_time'])->asArray()->one();
        $isDelay = false;
        if($service) {
            $service['stop_time'] = strtotime($service['stop_time']);
            $service['start_time'] = strtotime($service['start_time']);
            if( $service['stop_time'] < time()) $isDelay = true;
        }

        return $this->renderRest(['time'=>$service, 'isDelay'=>$isDelay]);
    }

}
