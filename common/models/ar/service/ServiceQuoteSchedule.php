<?php

namespace common\models\ar\service;

use Yii;
use yii\helpers\ArrayHelper;
use yii\db\Query;

/**
 * This is the model class for table "service_quote_schedule".
 *
 * @property string $id
 * @property integer $service_quote_id
 * @property string $title
 * @property integer $minnum
 * @property integer $maxnum
 * @property string $start_time
 * @property string $end_time
 * @property string $start_date
 * @property string $end_date
 * @property integer $repeatmode
 * @property string $repeatdetail
 * @property string $detail
 * @property string $created_at
 * @property string $updated_at
 * @property integer $valid
 * @property integer $loupan_id
 */
class ServiceQuoteSchedule extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'service_quote_schedule';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['service_quote_id', 'title', 'minnum', 'maxnum', 'start_time', 'end_time', 'start_date', 'end_date', 'repeatdetail'], 'required'],
            [['service_quote_id', 'minnum', 'maxnum', 'repeatmode', 'valid', 'loupan_id'], 'integer'],
            [['start_date', 'end_date', 'created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 100],
            [['start_time', 'end_time'], 'string', 'max' => 5],
            [['repeatdetail'], 'string', 'max' => 1000],
            [['detail'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '服务报价排班编号',
            'service_quote_id' => '服务报价编号',
            'title' => '排班标题',
            'minnum' => '每班服务人数下限',
            'maxnum' => '每班服务人数上限',
            'start_time' => '每班次开始时间：HH:mm',
            'end_time' => '每班次结束时间：HH:mm',
            'start_date' => '开始日期',
            'end_date' => '结束日期，与开始时间最多差1年',
            'repeatmode' => '重复：1、无重复，2、每天，3、每周，4、每月，5每年',
            'repeatdetail' => '重复详情，多个数据之间用逗号分隔：每周的填1-7，表示周一到周日，每月的填1-31，表示1日到31日，每年填0101-1231，表示月日',
            'detail' => '计划详情',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'valid' => '状态：0无效，1有效',
            'loupan_id' => '关联楼盘编号',
        ];
    }

    /**
     * 获取活动排班详情(海豚计划)
     * @param $id 活动service_id
     */
    public static function activityManageInfo($id) {
        $data = ServiceQuote::find()->where('service_id='.$id)->andWhere('valid=1')->select('id,title')->asArray()->all();
        //报名用户所属楼盘
        $loupan = ServiceEngageCustomer::find()->where('service_id='.$id)->andWhere('account_id='.Yii::$app->user->id)->andWhere(['valid'=>1, 'is_join'=>0])
                                                ->select('cust_loupan')->one();
        foreach($data as &$item) {
            $item['info']= ServiceQuoteSchedule::find()->where('service_quote_id='.$item['id'])
                                                    ->andWhere('loupan_id='.$loupan['cust_loupan'])->andWhere('valid=1')
                                                    ->select('title,id,maxnum,start_time, end_time,repeatmode, detail, end_date, start_date, loupan_id')
                                                    ->asArray()->all();
            foreach($item['info'] as &$list) {
                $list['people'] = (new Query())->select(['SUM(join_num) as number'])
                    ->from('service_engage_customer as t1')
                    ->where(['t1.valid'=>1,'t1.service_id'=>$id,'t1.sqs_id'=>$list['id'],'t1.is_join'=>1])
                    ->one();
                if(!$list['people']['number']) $list['people']['number'] = 0;
            }
        }
        return $data;
    }

    /**
     * 获取活动详情
     */
    public static function activityInfo($id) {
        $data = ServiceQuote::find()->where('service_id='.$id)->andWhere('valid=1')->select('id,title')->asArray()->all();
        foreach($data as &$item) {
            $item['info'] = ServiceQuoteSchedule::find()->where('service_quote_id='.$item['id'])
                                                        ->andWhere('valid=1')
                                                        ->select('title,id,maxnum,start_time, end_time,repeatmode, detail, end_date, start_date, loupan_id')
                                                        ->asArray()->all();
            foreach($item['info'] as &$list) {
                $list['people']['number'] = (new Query())->from('service_order_schedule as t1')
                                                        ->leftJoin('service_order as t2', 't1.order_id = t2.id')
                                                        ->where(['t1.valid'=>1, 't1.sqs_id'=>$list['id']])
                                                        ->andWhere(['<', 't2.userstatus', '3'])
                                                        ->count();
            }
        }
        return $data;
    }
}
