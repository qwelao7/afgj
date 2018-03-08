<?php

namespace mobile\modules\rest\controllers;

use common\models\ar\service\ServiceEngageCustomer;
use Yii;
use yii\helpers\Json;
use yii\db\Query;
use mobile\components\ActiveController;
use common\models\ar\service\ServiceQuote;
use common\models\ar\service\ServiceQuoteSchedule;
use common\models\ar\service\Catalog;
use common\models\ar\service\Provider;
use common\models\ar\order\ServiceOrder;
use common\models\ar\order\ServiceOrderComment;
use common\models\ar\order\ServiceOrderAddress;
use common\models\ar\order\ServiceOrderQuote;
use common\models\ar\order\ServiceOrderSchedule;
use common\models\ar\fang\FangDecorate;
use common\models\ar\fang\FangHouseType;

class OrderController extends ActiveController {

    /**
     * 添加订单
     * @param type $serviceId
     * @param type $quote
     * @param type $address
     * @param type $servicetime
     * @return type
     */
    public function actionAdd($serviceId, $quote, $address, $servicetime) {
        $uid = Yii::$app->user->id;
        $orderId = ServiceOrder::add($uid, $serviceId, $quote, $address, $servicetime);
        if ($orderId) {
            return $this->renderRest($orderId);
        } else {
            return $this->renderRestErr('下单失败，请稍后再试！');
        }
    }

    /**
     * 添加装修订单
     */
    public function actionAddDecorateOrder($serviceId, $amount, $settlemet, $quote, $addressList, $area){
        $orderId = ServiceOrder::addDecorate($serviceId, $amount, $settlemet, $quote, $addressList, $area);
        if ($orderId) {
            return $this->renderRest($orderId);
        } else {
            return $this->renderRestErr('下单失败，请稍后再试！');
        }
    }

    /**
     * 添加活动订单
     */
    public function actionAddActivityOrder($scheduleId, $quoteId, $serviceId, $join_num, $mobile){
        $orderId = ServiceOrder::addActivity($scheduleId, $quoteId, $serviceId, $join_num, $mobile);
        if ($orderId) {
            return $this->renderRest($orderId);
        } else {
            return $this->renderRestErr('下单失败，请稍后再试！');
        }
    }

    /**
     * 订单列表
     */
    public function actionList() {
        $data = ServiceOrder::find()->join('LEFT JOIN', 'service', 'service.id = service_order.service_id')
                                    ->where(['service_order.user_id'=>Yii::$app->user->id])
                                    ->select('service.title, service.logo')
                                    ->addSelect('service_order.*')
                                    ->orderBy(['service_order.id'=>SORT_DESC])->asArray()->all();
        foreach($data as &$item) {
            //判断订单类型
            $item['type'] = Catalog::find()->join('LEFT JOIN','service','service.catalog_id = catalog.id')
                                    ->where('service.id='.$item['service_id'])
                                    ->select('fixed_catalog')->one();
            $item['provider'] = Provider::find()->join('LEFT JOIN', 'service', 'provider.id = service.provider_id')
                                                    ->where('service.status=1')
                                                    ->select('provider.name')->one();
            switch($item['type']['fixed_catalog']) {
                case '5':
                    //活动
                    $item['schedule'] = ServiceQuoteSchedule::find()->join('LEFT JOIN', 'service_order_schedule', 'service_order_schedule.sqs_id = service_quote_schedule.id')
                        ->where('service_order_schedule.order_id='.$item['id'])
                        ->andWhere('service_quote_schedule.valid=1')
                        ->select('service_quote_schedule.start_date, service_quote_schedule.end_date, service_quote_schedule.end_time, service_quote_schedule.start_time')
                        ->one();
                    break;
                case '4':
                    //维修
                    break;
                case '2':
                    //装修
                    $item['decorate'] = FangDecorate::decorateHouseType($item['service_id']);
                    break;
                case '1':
                    //保养
                    break;
            }
        }
        return $this->renderRest($data);
    }

    /**
     * 订单详情
     * @param type $id
     * @param type $type
     */
    public function actionDetail($type, $id) {
        if(!isset($id) || !isset($type)) return $this->renderRest('error');
        $data = ServiceOrder::find()->join('LEFT JOIN', 'service', 'service.id = service_order.service_id')
                                    ->join('LEFT JOIN', 'service_quote', 'service_quote.service_id = service_order.service_id')
                                    ->where('service_order.id='.$id)
                                    ->select('service.logo, service.title')
                                    ->addSelect('service_order.*')
                                    ->addSelect('service_quote.description')
                                    ->asArray()->one();
        $data['provider'] = Provider::find()->join('LEFT JOIN', 'service', 'provider.id = service.provider_id')
                                            ->where('service.status=1')
                                            ->select('provider.name')->one();
        switch($type) {
            case '5':
                $data['schedule'] = ServiceQuoteSchedule::find()->join('LEFT JOIN', 'service_order_schedule', 'service_order_schedule.sqs_id = service_quote_schedule.id')
                    ->where('service_order_schedule.order_id='.$data['id'])
                    ->andWhere('service_quote_schedule.valid=1')
                    ->select('service_quote_schedule.end_time, service_quote_schedule.start_time, service_quote_schedule.start_date, service_quote_schedule.end_date')
                    ->one();
                break;
            case '3': break;
            case '2':
                $data['decoration'] = (new Query())->select(['t2.name'])
                    ->from('fang_decorate as t1')
                    ->leftJoin('fang_house_type as t2','t2.id=t1.house_type_id')
                    ->where('t1.service_id='.$data['service_id'])
                    ->one();
                $data['quote'] = ServiceOrderQuote::quoteDetail($id);
                $data['address'] = ServiceOrderAddress::addressDetail($id);
                break;
            case '1':break;
        }
        return $this->renderRest($data);
    }

    /**
     * 取消订单
     * @param type $id
     */
    public function actionCancel($type, $id) {
        $model = ServiceOrder::find()->where(['id'=>$id, 'user_id'=>Yii::$app->user->id])->andWhere(['<>', 'userstatus','2,3,4' ])->one();
        $model->userstatus = 3;
        if ($model->save()) {
            if($type == 5) {
                $result = ServiceEngageCustomer::find()->where(['account_id'=>Yii::$app->user->id, 'service_id'=>$model->service_id, 'valid'=>1])->one();
                if($result) {
                    $result->account_id = null;
                    $result->age_range = null;
                    $result->is_join = 0;
                    $result->sqs_id = 0;
                    $result->save();
                }
            }
            return $this->renderRest('取消成功');
        } else {
            return $this->renderRestErr('取消失败，请稍后再试！');
        }
    }

    /**
     * 终止服务
     * @param type $id
     */
    public function actionTermination($type, $id) {
        $model = ServiceOrder::find()->where(['id'=>$id, 'user_id'=>Yii::$app->user->id])->andWhere(['<>', 'userstatus', '2,3,4'])->one();
        $model->userstatus = 4;
        if ($model->save()) {
            if($type == 5) {
                $result = ServiceEngageCustomer::find()->where(['account_id'=>Yii::$app->user->id, 'service_id'=>$model->service_id, 'valid'=>1])->one();
               if(result) {
                   $result->join = 0;
                   $result->sqs_id = 0;
                   $result->account_id = null;
                   $result->age_range = null;
                   $result->save();
               }
            }
            return $this->renderRest('终止成功');
        } else {
            return $this->renderRestErr('终止失败，请稍后再试！');
        }
    }

    /**
     * 评价
     */
    public function actionComment($id, $comment) {
        $order = ServiceOrder::find()->where(['id'=>$id, 'user_id'=>Yii::$app->user->id, 'userstatus'=>1])->one();
        if (!$order) {
            return $this->renderRestErr('该订单无法评价！');
        }
        $model = new ServiceOrderComment;
        $model->order_id = $id;
        $model->quality_star = $comment['quality_star'];
        $model->attitude_star = $comment['attitude_star'];
        $model->service_star = $comment['service_star'];
        $model->context = $comment['context'];
        if ($model->save()) {
            $order->userstatus = 2;
            $order->save();
            return $this->renderRest('评价成功');
        } else {
            return $this->renderRestErr('评价失败，请稍后再试！');
        }
    }

    /**
     * 获取微信支付参数
     */
    public function actionWeiXinPayParams($orderId) {
        $order = ServiceOrder::findOne($orderId);
        if (!$order) {
            return $this->renderRestErr('订单不存在！');
        }
        $data = Yii::$app->pay->weiXin(Yii::$app->user->identity->weixin_code, $order->id, $order->amount);
        return $this->renderRest($data);
    }

    /**
     * 添加维修服务订单
     * @param type $data
     * @param type $address
     * @return type
     */
    public function actionServiceAdd($data, $address) {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = new ServiceOrder;
            $model->user_id = Yii::$app->user->id;
            $model->remark = Json::encode($data);
            $model->save();
            $orderAddress = new ServiceOrderAddress();
            $orderAddress->order_id = $model->id;
            $orderAddress->address_id = $address['id'];
            $orderAddress->contact_to = $address['contact_to'];
            $orderAddress->mobile = $address['mobile'];
            $orderAddress->street= $address['street'];
            $orderAddress->mansion= $address['mansion'];
            $orderAddress->building_house_num= $address['building_house_num'];
            $orderAddress->save();
            $transaction->commit();
        } catch(\yii\db\Exception $e) {
            $transaction->rollback();
            return $this->renderRestErr('提交失败');
        }
        return $this->renderRest($model);
    }
}
