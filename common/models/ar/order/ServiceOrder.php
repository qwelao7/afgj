<?php

namespace common\models\ar\order;

use common\models\ar\service\ServiceEngageCustomer;
use common\models\ar\service\ServiceQuoteSchedule;
use Yii;
use common\models\ar\service\ServiceQuote;
use common\models\ar\order\ServiceOrderAddress;
use common\models\ar\order\ServiceOrderQuote;
use common\models\ar\fang\FangDecorate;
use common\models\ar\user\Account;
use yii\helpers\ArrayHelper;
use common\models\ar\service\Service;
use yii\web\BadRequestHttpException;

/**
 * This is the model class for table "service_order".
 *
 * @property string $id
 * @property string $unique_code
 * @property integer $user_id
 * @property integer $service_id
 * @property resource $2d_barcode
 * @property resource $2d_barcode_small
 * @property resource $2d_barcode_big
 * @property string $amount
 * @property string $settlement_price
 * @property string $status
 * @property integer $paystatus
 * @property integer $userstatus
 * @property integer $workstatus
 * @property string $created_at
 * @property string $updated_at
 * @property string $order_data
 */
class ServiceOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'service_order';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'service_id', 'paystatus', 'userstatus', 'workstatus'], 'integer'],
            [['amount', 'settlement_price'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['remark'], 'string', 'max' => 5000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'service_id' => '服务',
            'amount' => '订单总额',
            'settlement_price' => '0.011 means 1.1%',
            'service_time' => '服务时间',
            'paystatus' => '付费状态',
            'userstatus' => '对客状态',
            'workstatus' => '工作状态',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'remark' => '备注',
        ];
    }

    /**
     * paystatus所有的状态
     * @var array
     */
    public static $paystatus = [
        0 => ['name' => '未付费'],
        1 => ['name' => '已付费'],
        2 => ['name' => '不需要付费'],
    ];

    /**
     * workstatus所有的状态
     * @var array
     */
    public static $workstatus = [
        0 => ['name' => '待确认'],
        1 => ['name' => '已确认'],
        2 => ['name' => '执行中'],
        3 => ['name' => '已执行'],
        4 => ['name' => '已取消'],
        5 => ['name' => '已终止'],
    ];

    /**
     * userstatus所有的状态
     * @var array
     */
    public static $userstatus = [
        0 => ['name' => '待执行'],
        1 => ['name' => '待评价'],
        2 => ['name' => '已完成'],
        3 => ['name' => '已取消'],
        4 => ['name' => '已终止'],
    ];

    public function getAccount(){
        return $this->hasOne(Account::className(), ['id' => 'user_id']);
    }

	public function getService() {
		return $this->hasOne(Service::className(), ['id' => 'service_id']);
	}

	public function getAddress() {
		return $this->hasOne(ServiceOrderAddress::className(), ['order_id' => 'id']);
	}

	public function getServicetime() {
		return $this->hasOne(ServiceOrderServicetime::className(), ['order_id' => 'id']);
	}

	public function getQuote() {
		return $this->hasMany(ServiceOrderQuote::className(), ['order_id' => 'id'])->joinWith(['servicequote']);
	}

    public function getDecorate() {
        return $this->hasOne(FangDecorate::className(), ['service_id'=>'service_id']);
    }

    /**
     * 添加订单
     */
    public static function add($uid, $serviceId, $quote, $address, $servicetime) {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $orderItem = [];
            $order = new static;
            $order->user_id = $uid;
            $order->service_id = $serviceId;
            $order->amount = ServiceQuote::getTotal($quote);
            $order->service_time = $servicetime;
            $order->save();
            $orderAddress = new ServiceOrderAddress();
            $orderAddress->order_id = $order->id;
            $orderAddress->address_id = $address['id'];
            $orderAddress->contact_to = $address['contact_to'];
            $orderAddress->mobile = $address['mobile'];
            $orderAddress->street = $address['street'];
            $orderAddress->mansion = $address['mansion'];
            $orderAddress->building_house_num = $address['building_house_num'];
            //$orderAddress->detail = $address['area'].$address['detail'];
            $orderAddress->save();
            $items = ServiceQuote::find()->where(['id'=>ArrayHelper::getColumn($quote, 'id')])->indexBy('id')->asArray()->all();
            foreach ($quote as $v) {
                $orderItem[] = [
                    $order->id,
                    $v['id'],
                    $v['num'],
                    $items[$v['id']]['price'],
                ];
            }
            ServiceOrderQuote::getDb()->createCommand()->batchInsert(ServiceOrderQuote::tableName(), ['order_id', 'quote_id', 'quality', 'price'], $orderItem)->execute();
            $transaction->commit();
        } catch(\yii\db\Exception $e) {
            $transaction->rollback(); 
            return false;
        }
        return $order->id;
    }

    /**
     * 添加装修订单
     */
    public static function addDecorate($serviceId, $amount, $settlemet, $quote, $addressList, $area) {
        $transaction = Yii::$app->db->beginTransaction();
        $orderId=0;
        try{
            $order = new ServiceOrder();
            $order->user_id = Yii::$app->user->id;
            $order->service_id = $serviceId;
            $order->amount = $amount;
            $order->settlement_price = $settlemet;
            $order->service_time = date('Y-m-d H:i:s', time());
            if($order->save()){
                $orderId = $order->id;
                $orderAddress = new ServiceOrderAddress();
                $orderAddress->order_id = $orderId;
                $orderAddress->address_id = $addressList['id'];
                $orderAddress->contact_to = $addressList['contact_to'];
                $orderAddress->mobile = $addressList['mobile'];
                $orderAddress->mansion = $addressList['mansion'];
                $orderAddress->building_house_num = $addressList['building_house_num'];
                if($orderAddress->save()) {
                    foreach($quote as $quoteItem){
                        $quoteObj = new ServiceOrderQuote();
                        $quoteObj->order_id = $orderId;
                        $quoteObj->quote_id = $quoteItem['id'];
                        if($quoteItem['price_unit'] == 1) {
                            $quoteObj->price = $quoteItem['price'];
                        }else {
                            $quoteObj->price = (int)$quoteItem['price'] * $area;
                        }
                        $quoteObj->quality = 1;
                        $quoteObj->total_price = $quoteObj->quality * $quoteObj->price;
                        if(!$quoteObj->save()) {
                            throw new BadRequestHttpException("service_order_quote存储失败");
                        }
                    }
                }else {
                    throw new BadRequestHttpException("service_order-adress存储失败");
                }
            }else {
                throw new BadRequestHttpException("service_order存储失败");
            }
            $transaction->commit();
        }catch(\yii\db\Exception $e) {
            $transaction->rollback();
            return false;
        }
        return $orderId;
    }

    /**
     * 添加活动订单(海豚计划 / 博物小课堂)
     * @param $scheduleId 班次id
     * @param $quoteId 报价编号quote_id
     * @param $serviceId 服务编号
     */
    public static function addActivity($scheduleId, $quoteId, $serviceId, $join_num, $mobile) {
        $transaction = Yii::$app->db->beginTransaction();
        $orderId=0;
        try{
            $order = new ServiceOrder();
            $order->user_id = Yii::$app->user->id;
            $order->service_id = $serviceId;
            $order->service_time = date('Y-m-d H:i:s', time());
            if($order->save()) {
                $orderId = $order->id;
                $quote = new ServiceOrderQuote();
                $quote->order_id = $orderId;
                $quote->quote_id = $quoteId;
                $quote->quality = $join_num;
                if($quote->save()) {
                    $schedule = new ServiceOrderSchedule();
                    $schedule->account_id = Yii::$app->user->id;
                    $schedule->order_id = $orderId;
                    $schedule->service_id = $serviceId;
                    $schedule->sqs_id = $scheduleId;
                    $schedule->sdate = date('Y-m-d H:i:s', time());
                    if($schedule->save()) {
                        //海豚计划 (一个人一次且活动人数已预订)
                        if($serviceId == '155') {
                            //博物活动(不限次数)
                            $add = new ServiceOrderAddress();
                            $add->order_id = $orderId;
                            $add->contact_to = Yii::$app->user->identity->nickname;
                            $add->mobile = $mobile;
                            $add->save();
                        }else {
                            $data = ServiceEngageCustomer::find()->where('service_id='.$serviceId)->andWhere('account_id='.Yii::$app->user->id)
                                ->andWhere('valid=1')->one();
                            if($data) {
                                $data->is_join = 1;
                                $data->sqs_id = $scheduleId;
                                $data->save();
                            }
                        }
                    }else {
                        throw new BadRequestHttpException("service_order_schedule存储失败");
                    }
                }else {
                    throw new BadRequestHttpException("service_order_quote存储失败");
                }
            }else {
                throw new BadRequestHttpException("service_order存储失败");
            }
            $transaction->commit();
        }catch(\yii\db\Exception $e) {
            echo $e->getMessage();
            $transaction->rollback();
            return false;
        }
        return $orderId;
    }
}
