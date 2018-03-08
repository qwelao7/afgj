<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\base\Exception;

/**
 * This is the model class for table "hll_user_points".
 *
 * @property string $id
 * @property integer $user_id
 * @property integer $point
 * @property integer $remain_point
 * @property integer $can_shared
 * @property integer $type
 * @property string $expire_time
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllUserPoints extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static $types = [
        'system' => 'http://pub.huilaila.net/default-icon.png',
        'admin' => 'http://pub.huilaila.net/default-icon.png',
        'defaultImg' => 'http://pub.huilaila.net/e679c2a73997f7b5ee41c532fc46cc21.png',
    ];

    public static function tableName()
    {
        return 'hll_user_points';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'point', 'creater', 'updater', 'valid'], 'integer'],
            [['expire_time', 'created_at', 'updated_at', 'period'], 'safe'],
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
            'point' => 'Point',
            'expire_time' => 'Expire Time',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //每月过期积分情况
    // status 0-未过期  1-已过期
    public static function getPastPointsByMonth($user_id)
    {
        $currTime = time();
        $list = (new Query())->select(["date_format(expire_time,'%Y-%m-%d') as show_time","sum(point) as point",
        "if(unix_timestamp(expire_time) > $currTime,1,0) as status",'expire_time'])
            ->from('hll_user_points')
            ->where(['user_id' => $user_id, 'valid' => 1])->andWhere(['>', 'point', 0])
            ->groupBy(['expire_time'])->orderBy('expire_time desc')->all();

        foreach ($list as &$item) {
            //通用积分
            $common_point = (new Query())->select(["sum(point) as point"])->from('hll_user_points')
                ->where(['user_id' => $user_id, 'valid' => 1,'community_id'=>0,
                    'business_id'=>0,'point_type'=>1,'expire_time'=>$item['expire_time']])->one();
            if($common_point['point'] > 0){
                $item['ext']['所有小区'][] = ['name'=>'通用','point'=>$common_point['point']];
            }
            //指定小区体验消费或社群活动
            $community_point = (new Query())->select(["sum(point) as point",'community_id','point_type'])->from('hll_user_points')
                ->where(['user_id' => $user_id, 'valid' => 1,'business_id'=>0,'expire_time'=>$item['expire_time']])
                ->andWhere(['<>','point_type',1])
                ->groupBy(['community_id','point_type'])->all();

            foreach($community_point as $val){
                if($val['point'] > 0){
                    $community_name = (new Query())->select(['name'])->from('hll_community')
                        ->where(['id'=>$val['community_id'],'valid'=>1])->scalar();
                    if($val['point_type'] == 2){
                        $item['ext'][$community_name][] = ['name'=>'社区消费','point'=>$val['point']];
                    }else{
                        $item['ext'][$community_name][] = ['name'=>'社群活动','point'=>$val['point']];
                    }
                }
            }
            //指定小区的商家积分
            $business_point = (new Query())->select(["sum(point) as point",'community_id','business_id'])->from('hll_user_points')
                ->where(['user_id' => $user_id, 'valid' => 1, 'point_type'=>3,'expire_time'=>$item['expire_time']])
                ->groupBy(['community_id','business_id'])->all();

            foreach($business_point as $val){
                if($val['point'] > 0){
                    $community_name = (new Query())->select(['name'])->from('hll_community')
                        ->where(['id'=>$val['community_id'],'valid'=>1])->scalar();
                    $business_name = (new Query())->select(['name'])->from('hll_business')
                        ->where(['id'=>$val['business_id'],'valid'=>1])->scalar();

                    $item['ext'][$community_name][] = ['name'=>$business_name,'point'=>$val['point']];
                }
            }
        }
        return $list;
    }

    //要赠送的友元
    public static function getSendPoint($user_id){
        $time = date("Y-m-d H:i:s",time());
        $list = (new Query())->select(['expire_time',"sum(point) as point", 'community_id','business_id'])
            ->from('hll_user_points')
            ->where(['user_id' => $user_id, 'valid' => 1])->andWhere(['>', 'point', 0])
            ->andWhere(['>','expire_time',$time])
            ->groupBy(['expire_time','community_id','business_id'])->orderBy('expire_time desc')->all();
        foreach ($list as &$item) {
            $item['point'] = intval($item['point']);
            if ($item['community_id'] == 0) {
                $item['name'] = '自有';
            } else {
                $item['name'] = (new Query())->select(['name'])->from('hll_community')
                    ->where(['id' => $item['community_id']])->scalar();
                if ($item['business_id'] == 0) {
                    $data = (new Query())->select(["sum(point) as point",'point_type','community_id','business_id'])->from('hll_user_points')
                        ->where(['user_id' => $user_id, 'valid' => 1, 'community_id' => $item['community_id'],
                            'business_id' => 0, 'point_type' => 2, 'expire_time' => $item['expire_time']])->one();
                    if ($data['point']) {
                        $item['ext']['体验消费'] = $data;
                    };
                    $data = (new Query())->select(["sum(point) as point",'point_type','community_id','business_id'])->from('hll_user_points')
                        ->where(['user_id' => $user_id, 'valid' => 1, 'community_id' => $item['community_id'],
                            'business_id' => 0, 'point_type' => 4, 'expire_time' => $item['expire_time']])->one();
                    if ($data['point']) {
                        $item['ext']['社群活动'] = $data;
                    };
                } else {
                    $business_name = (new Query())->select(['name'])->from('hll_business')->where(['id' => $item['business_id']])->scalar();
                    $data = (new Query())->select(["sum(point) as point",'point_type','community_id','business_id'])->from('hll_user_points')
                        ->where(['user_id' => $user_id, 'valid' => 1, 'community_id' => $item['community_id'],
                            'business_id' => $item['business_id'], 'point_type' => 3, 'expire_time' => $item['expire_time']])->one();
                    $item['ext'][$business_name] = $data;
                }
            }
            $tmpTime = strtotime($item['expire_time']);
            $item['expire_time'] = f_date($tmpTime, 2);
        }
        return $list;
    }

    //每月积分获取情况
    public static function getAcquireOrUserPointsByMonth($word, $user_id)
    {
        if ($word == 'acquire') {
            $query = (new Query())
                ->select(['item_id', 'category', 'icon', 'scenes', 'period', 'created_at', "CONCAT( '+', point) AS point", 'business_id', 'remark', 'creater'])
                ->from('hll_user_points_log')->where(['user_id' => $user_id, 'valid' => 1, 'type' => 3])
                ->orderBy(['period' => SORT_DESC, 'created_at' => SORT_DESC]);
        } else {
            $query = (new Query())
                ->select(['item_id', 'category', 'icon', 'scenes', 'period', 'created_at', "CONCAT( '-', point) AS point", 'business_id', 'remark', 'creater'])
                ->from('hll_user_points_log')->where(['user_id' => $user_id, 'valid' => 1, 'type' => 1])
                ->orderBy(['period' => SORT_DESC, 'created_at' => SORT_DESC]);
        }

        return $query;
    }

    //每月积分所有情况
    public static function getAllPointsByMonth($userId)
    {
        $query1 = (new Query())
            ->select(['item_id', 'category', 'icon', 'scenes', 'period', 'created_at', "CONCAT( '-', point) AS point", 'business_id', 'remark', 'creater'])
            ->from('hll_user_points_log')->where(['user_id' => $userId, 'valid' => 1, 'type' => 1]);

        $query2 = (new Query())
            ->select(['item_id', 'category', 'icon', 'scenes', 'period', 'created_at', "CONCAT( '+', point) AS point", 'business_id', 'remark', 'creater'])
            ->from('hll_user_points_log')->where(['user_id' => $userId, 'valid' => 1, 'type' => 3]);

        $query1->union($query2, true);
        $query = (new Query())->select(['*'])->from([$query1])->orderBy(['period' => SORT_DESC, 'created_at' => SORT_DESC]);
        return $query;
    }

    public static function getMonth($periodArray, $userId)
    {
        $time_year = date("Y", time());
        $time_month = date("m", time());
        $result = [];
        foreach ($periodArray as $item) {
            $data['period'] = $item;
            $periodYear = intval(substr($item, 0, 4));
            $periodMonth = intval(substr($item, 4));
            if ($periodYear == $time_year) {
                if ($periodMonth == $time_month) {
                    $data['month'] = '本月';
                } else {
                    $data['month'] = $periodMonth . '月';
                }
            } else {
                $data['month'] = $periodYear . '年' . $periodMonth . '月';
            }
            $acquire_point = HllUserPoints::statIncomePointsByUserAndPeriod($userId, $item);
            $used_point = HllUserPoints::statExpendPointsByUserAndPeriod($userId, $item);
            $data['acquire_point'] = $acquire_point;
            $data['used_point'] = $used_point;
            array_push($result, $data);
        }
        return $result;
    }

    public static function getImage($infoList)
    {
        foreach ($infoList as &$item) {
            if ($item['icon'] != '') {
                $item['img'] = $item['icon'];
            } else if ($item['category'] == 'goods') {
                $img = (new Query())->select(['goods_thumb'])->from('ecs_goods')
                    ->where(['goods_id' => $item['item_id'], 'is_delete' => 0])->one();
                $item['img'] = $img['goods_thumb'] == null ? HllUserPoints::$types['defaultImg'] : 'http://mall.afguanjia.com/' . $img['goods_thumb'];
            } else if ($item['category'] == 'events') {
                $img = (new Query())->select(['thumbnail'])->from('hll_events')
                    ->where(['id' => $item['item_id'], 'is_delete' => 0])->one();
                $item['img'] = $img['thumbnail'] == null ? HllUserPoints::$types['defaultImg'] : 'http://pub.huilaila.net/' . $img['thumbnail'];
            } else if ($item['scenes'] == 'user_shopping') {
                $img = (new Query())->select(['logo'])->from('hll_business')
                    ->where(['id' => $item['business_id'], 'is_show' => 1])->one();
                $item['img'] = $img['logo'] == null ? HllUserPoints::$types['defaultImg'] : 'http://pub.huilaila.net/' . $img['logo'];
            } else if ($item['scenes'] == 'neighbor') {
                $img = (new Query())->select(['headimgurl'])->from('ecs_wechat_user')
                    ->where(['ect_uid' => $item['business_id'], 'isbind' => 0])->one();
                $item['img'] = $img['headimgurl'] == null ? HllUserPoints::$types['defaultImg'] : $img['headimgurl'];
            } else {
                $item['img'] = HllUserPoints::$types['admin'];
            }
        }
        return $infoList;
    }

    /**
     * 消费友元
     * @param $userId
     * @param $points
     * @param $data
     * @param $community_id
     * @param $business
     * @param $type  1 商品消费  2 商家消费
     * @return string
     * @throws Exception
     */
    public static function expendPoints($userId, $points, $data, $community_id = null, $business, $type = 1)
    {
        $availPoints = static::availPointsByUser($userId, $community_id, $business, 1);

        $userPoints = HllUserPoints::getUserPoints($userId, $community_id,1);

        $adjustPoints = [];
        $community = array_column($availPoints, 'community_id');
        if ($availPoints && $userPoints >= $points) {
            $availPoints = ArrayHelper::map($availPoints, "id", "point");
            foreach ($availPoints as $key => $val) {
                if ($val >= $points) {
                    $adjustPoints[$key] = intval($points);
                    break;
                } else if ($val < $points) {
                    $adjustPoints[$key] = intval($val);
                    $points = $points - $val;
                }
            }
            $db = Yii::$app->db;
            try {

                $sql = "UPDATE hll_user_points SET point=point - :point WHERE id=:id";

                $command = $db->createCommand($sql);
                $num = 0;
                $unique_id = uniqid('hll_point_');

                foreach ($adjustPoints as $key => $val) {
                    $log = new HllUserPointsLog();
                    $log->user_id = $userId;
                    $log->unique_id = $unique_id;
                    $log->point_id = $key;
                    $log->item_id = $data['item_id'];
                    $log->business_id = $data['business_id'];
                    $log->community_id = $community[$num];
                    $log->point = intval($val);
                    $log->icon = $data['icon'];
                    $log->remark = $data['remark'];
                    $log->category = $data['category'];
                    $log->type = $data['type'];
                    $log->scenes = $data['scenes'];
                    $log->order_id = $data['order_id'];
                    $log->save();
                    //type=1时，先不将友元加给商家，支付完成后再加
                    if($type == 2){
                        //获取当前商家所对应的的小区积分
                        $business = intval(static::addPointToBusiness($val, $data['business_id'], $community[$num]));
                        if(!$business){
                            throw new Exception('database error',101);
                        }

                        $business_log = new HllPointBusinessLog();
                        $business_log->user_id = $userId;
                        $business_log->business_id = intval($data['business_id']);
                        $business_log->community_id = intval($community[$num]);
                        $business_log->point = $val;
                        $business_log->left_points = $business;
                        $business_log->type = 1;
                        $business_log->change_reason = $data['change_reason'];
                        $business_log->unique_id = $unique_id;
                        $business_log->save();
                    }

                    $command->bindParam(":point", $val, \PDO::PARAM_INT);
                    $command->bindParam(":id", $key, \PDO::PARAM_INT);
                    $command->execute();

                    $num++;
                }
                return $unique_id;
            } catch (Exception $e) {
                throw new Exception($e->getMessage(), 102);
            }
        } else {
            throw new exception("友元余额不足", 101);
        }
    }

    /**
     * 活动支付友元
     * @param $userId
     * @param $points
     * @param $data
     * @param null $community_id
     * @return string
     * @throws Exception
     */
    public static function eventsPoints($userId, $points, $data, $community_id = null)
    {

        $availPoints = static::availPointsByUser($userId, $community_id, null,2);

        $userPoints = HllUserPoints::getUserPoints($userId, $community_id,2);

        $adjustPoints = [];
        $community = array_column($availPoints, 'community_id');
        if ($availPoints && $userPoints >= $points) {
            $availPoints = ArrayHelper::map($availPoints, "id", "point");
            foreach ($availPoints as $key => $val) {
                if ($val >= $points) {
                    $adjustPoints[$key] = intval($points);
                    break;
                } else if ($val < $points) {
                    $adjustPoints[$key] = intval($val);
                    $points = $points - $val;
                }
            }
            $db = Yii::$app->db;
            try {

                $sql = "UPDATE hll_user_points SET point=point - :point WHERE id=:id";

                $command = $db->createCommand($sql);
                $num = 0;
                $unique_id = uniqid('hll_point_');
                foreach ($adjustPoints as $key => $val) {
                    $log = new HllUserPointsLog();
                    $log->user_id = $userId;
                    $log->unique_id = $unique_id;
                    $log->point_id = $key;
                    $log->item_id = $data['item_id'];
                    $log->business_id = 0;
                    $log->community_id = $community[$num];
                    $log->point = intval($val);
                    $log->icon = $data['icon'];
                    $log->remark = $data['change_reason'];
                    $log->category = $data['category'];
                    $log->type = $data['change_type'];
                    $log->scenes = $data['scenes'];
                    if ($log->save()) {
                        //查询对方记录
                        $time = (new Query())->select(['expire_time','business_id','point_type'])
                            ->from('hll_user_points')->where(['id' => $key])->one();
                        $income = HllUserPoints::find()->where(['expire_time' => $time['expire_time'], 'valid' => 1,'point_type'=>$time['point_type'],
                            'user_id' => $data['creater'], 'community_id' => $community[$num],'business_id'=>$time['business_id']])->one();
                        if (!$income) {
                            $income = new HllUserPoints();
                            $income->user_id = $data['creater'];
                            $income->expire_time = $time['expire_time'];
                            $income->community_id = $community[$num];
                            $income->point = 0;
                            $income->point_type = $time['point_type'];
                            $income->business_id = $time['business_id'];
                            $income->save();
                        }
                        $income_log = new HllUserPointsLog();
                        $income_log->user_id = $data['creater'];
                        $income_log->unique_id = $unique_id;
                        $income_log->item_id = $data['item_id'];
                        $income_log->point_id = $income->id;
                        $income_log->business_id = 0;
                        $income_log->community_id = $community[$num];
                        $income_log->point = intval($val);
                        $income_log->icon = $data['icon'];
                        $income_log->remark = $data['income_reason'];
                        $income_log->category = $data['category'];
                        $income_log->type = $data['income_type'];
                        $income_log->scenes = $data['scenes'];
                        $income_log->save();
                        if ($income_log->save()) {
                            $income->point += $val;
                            $income->save();
                        }
                        $command->bindParam(":point", $val, \PDO::PARAM_INT);
                        $command->bindParam(":id", $key, \PDO::PARAM_INT);
                        $command->execute();
                    }
                    $num++;
                }
                return $unique_id;
            } catch (Exception $e) {
                throw new Exception($e->getMessage(), 102);
            }
        } else {
            throw new exception("友元余额不足", 101);
        }
    }

    /**
     * 赠送友元
     * @params $userId 赠送者id
     * @params $toUserId 被赠送者id
     * @params $points 友元数目
     * @params $data 友元参数
     * @throws Exception
     * @author zend.wang
     * @time 2016-11-25 15:00
     */
    public static function givePoints($userId, $toUserId, $points, $data)
    {

        $availPoints = static::availPointsByUser($userId, $data['community_id'],null,3);

        $adjustPoints = [];
        $userPoints = HllUserPoints::getUserPoints($userId, $data['community_id'],3);
        $community = array_column($availPoints, 'community_id');
        if ($availPoints && $userPoints >= $points) {
            $availPoints = ArrayHelper::map($availPoints, "id", "point");
            foreach ($availPoints as $key => $val) {
                if ($val >= $points) {
                    $adjustPoints[$key] = intval($points);
                    break;
                } else if ($val < $points) {
                    $adjustPoints[$key] = intval($val);
                    $points = $points - $val;
                }
            }
            $db = Yii::$app->db;
            try {

                $sql = "UPDATE hll_user_points SET point=point - :point WHERE id=:id";
                $command = $db->createCommand($sql);
                $num = 0;
                foreach ($adjustPoints as $key => $val) {
                    //赠送记录
                    $give_log = new HllUserPointsLog();
                    $give_log->user_id = $userId;
                    $give_log->unique_id = $data['unique_id'];
                    $give_log->point_id = $key;
                    $give_log->item_id = $toUserId;
                    $give_log->category = $data['category'];
                    $give_log->point = intval($val);
                    $give_log->icon = $data['to_user_img'];
                    $give_log->remark = $data['remark'];
                    $give_log->scenes = $data['scenes'];
                    $give_log->type = $data['type'];
                    $give_log->community_id = $community[$num];
                    $give_log->save();
                    //查询对方记录
                    $pointsTime = (new Query())->select(['expire_time','business_id','point_type'])
                        ->from('hll_user_points')->where(['id' => $key])->one();
                    $toUserPoints = HllUserPoints::find()->where(['expire_time' => $pointsTime['expire_time'], 'valid' => 1,'point_type'=>$pointsTime['point_type'],
                        'user_id' => $toUserId, 'community_id' => $community[$num],'business_id'=>$pointsTime['business_id']])->one();
                    if (!$toUserPoints) {
                        $toUserPoints = new HllUserPoints();
                        $toUserPoints->user_id = $toUserId;
                        $toUserPoints->expire_time = $pointsTime['expire_time'];
                        $toUserPoints->community_id = $community[$num];
                        $toUserPoints->point = 0;
                        $toUserPoints->point_type = $pointsTime['point_type'];
                        $toUserPoints->business_id = $pointsTime['business_id'];
                        $toUserPoints->save();
                    }
                    //获取记录
                    $income_log = new HllUserPointsLog();
                    $income_log->user_id = $toUserId;
                    $income_log->unique_id = $data['unique_id'];
                    $income_log->item_id = $userId;
                    $income_log->point_id = $toUserPoints->id;
                    $income_log->category = $data['category'];
                    $income_log->point = intval($val);
                    $income_log->icon = $data['user_img'];
                    $income_log->remark = $data['to_remark'];
                    $income_log->scenes = $data['scenes'];
                    $income_log->type = $data['to_type'];
                    $income_log->community_id = $community[$num];
                    $income_log->save();
                    $command->bindParam(":point", $val, \PDO::PARAM_INT);
                    $command->bindParam(":id", $key, \PDO::PARAM_INT);
                    $command->execute();
                    //给对方添加友元
                    $toUserPoints->point += $val;

                    if ($toUserPoints->save()) {
                        $num++;
                        continue;
                    } else {
                        throw new Exception('修改获取人积分记录失败！', 103);
                    }
                }
            } catch (Exception $e) {
                throw new Exception($e->getMessage(), 102);
            }
        } else {
            throw new exception("友元余额不足", 101);
        }
    }

    /**
     * 分享友元
     * @param $data
     * @throws Exception
     */
    public static function sharePoints($data){
        if($data['point_type'] == 1){
            $availPoints = (new Query())->select(["id", "point", "community_id"])
                ->from('hll_user_points')
                ->where(['user_id' => $data['user_id'], 'valid' => 1,'expire_time' => $data['expire_time'], 'point_type'=>1])
                ->andWhere([">", "expire_time", date("Y-m-d H:i:s")])
                ->andWhere([">", "point", 0])->all();
        }else{
            $availPoints = (new Query())->select(["id", "point", "community_id"])
                ->from('hll_user_points')
                ->where(['user_id' => $data['user_id'], 'valid' => 1,'expire_time' => $data['expire_time'],
                'point_type'=>$data['point_type'],'community_id'=>$data['community_id'],'business_id'=>$data['business_id']])
                ->andWhere([">", "expire_time", date("Y-m-d H:i:s")])
                ->andWhere([">", "point", 0])->all();
        }
        //判断所选积分是否
        $point_num = array_sum(array_column($availPoints,'point'));

        $adjustPoints = [];
        $community = array_column($availPoints, 'community_id');
        if ($availPoints && intval($point_num) >= intval($data['point'])) {
            $availPoints = ArrayHelper::map($availPoints, "id", "point");
            foreach ($availPoints as $key => $val) {
                if ($val >= $data['point']) {
                    $adjustPoints[$key] = intval($data['point']);
                    break;
                } else if ($val < $data['point']) {
                    $adjustPoints[$key] = intval($val);
                    $data['point'] = $data['point'] - $val;
                }
            }
            $db = Yii::$app->db;
            try {

                $sql = "UPDATE hll_user_points SET point=point - :point WHERE id=:id";
                $command = $db->createCommand($sql);
                $num = 0;
                foreach ($adjustPoints as $key => $val) {
                    //赠送记录
                    $give_log = new HllUserPointsLog();
                    $give_log->user_id = $data['user_id'];
                    $give_log->unique_id = $data['unique_id'];
                    $give_log->point_id = $key;
                    $give_log->item_id = $data['to_user_id'];
                    $give_log->category = $data['category'];
                    $give_log->point = $val;
                    $give_log->icon = $data['to_user_img'];
                    $give_log->remark = $data['remark'];
                    $give_log->scenes = $data['scenes'];
                    $give_log->type = $data['type'];
                    $give_log->community_id = $community[$num];
                    $give_log->save();
                    //查询对方记录
                    $pointsTime = (new Query())->select(['expire_time','business_id','point_type'])
                        ->from('hll_user_points')->where(['id' => $key])->one();
                    $toUserPoints = HllUserPoints::find()->where(['expire_time' => $pointsTime['expire_time'], 'valid' => 1,'point_type'=>$pointsTime['point_type'],
                        'user_id' => $data['to_user_id'], 'community_id' => $community[$num],'business_id'=>$pointsTime['business_id']])->one();
                    if (!$toUserPoints) {
                        $toUserPoints = new HllUserPoints();
                        $toUserPoints->user_id = $data['to_user_id'];
                        $toUserPoints->expire_time = $pointsTime['expire_time'];
                        $toUserPoints->community_id = $community[$num];
                        $toUserPoints->point = 0;
                        $toUserPoints->point_type = $pointsTime['point_type'];
                        $toUserPoints->business_id = $pointsTime['business_id'];
                        $toUserPoints->save();
                    }
                    //获取记录
                    $income_log = new HllUserPointsLog();
                    $income_log->user_id = $data['to_user_id'];
                    $income_log->unique_id = $data['unique_id'];
                    $income_log->item_id = $data['user_id'];
                    $income_log->point_id = $toUserPoints->id;
                    $income_log->category = $data['category'];
                    $income_log->point = $val;
                    $income_log->icon = $data['user_img'];
                    $income_log->remark = $data['to_remark'];
                    $income_log->scenes = $data['scenes'];
                    $income_log->type = $data['to_type'];
                    $income_log->community_id = $community[$num];
                    $income_log->save();
                    $command->bindParam(":point", $val, \PDO::PARAM_INT);
                    $command->bindParam(":id", $key, \PDO::PARAM_INT);
                    $command->execute();
                    //给对方添加友元
                    $toUserPoints->point += $val;

                    if ($toUserPoints->save()) {
                        $num++;
                        continue;
                    } else {
                        throw new Exception('修改获取人积分记录失败！', 103);
                    }
                }
            } catch (Exception $e) {
                throw new Exception($e->getMessage(), 102);
            }
        } else {
            throw new exception("友元余额不足", 101);
        }
    }

    /**
     * 兰园门禁卡
     * @param $userId
     * @param $points
     * @param $data
     * @param $community_id
     * @return string
     * @throws Exception
     */
    public static function LanYuanExpendPoints($userId, $points, $data, $community_id = 16396)
    {
        $query = (new Query())->select(["id", "point", "community_id"])
            ->from('hll_user_points')
            ->where(['user_id' => $userId, 'valid' => 1])
            ->andWhere([">", "expire_time", date("Y-m-d H:i:s")])
            ->andWhere([">", "point", 0]);
        $availPoints = $query->andWhere(['community_id'=>$community_id])
            ->andWhere(['point_type' => 2]) // 2 -> 体验消费友元
            ->orderBy("expire_time asc")->all();

        $userPoints = (new Query())->from('hll_user_points')
            ->where(['user_id' => $userId, 'valid' => 1])
            ->andWhere(['community_id' => $community_id,'point_type'=>2])
            ->andWhere(['>', 'expire_time', date("Y-m-d H:i:s")])
            ->sum('point');

        $adjustPoints = [];
        $community = array_column($availPoints, 'community_id');
        if ($availPoints && $userPoints >= $points) {
            $availPoints = ArrayHelper::map($availPoints, "id", "point");
            foreach ($availPoints as $key => $val) {
                if ($val >= $points) {
                    $adjustPoints[$key] = intval($points);
                    break;
                } else if ($val < $points) {
                    $adjustPoints[$key] = intval($val);
                    $points = $points - $val;
                }
            }
            $db = Yii::$app->db;
            try {

                $sql = "UPDATE hll_user_points SET point=point - :point WHERE id=:id";

                $command = $db->createCommand($sql);
                $num = 0;
                $unique_id = $data['unique_id'];

                foreach ($adjustPoints as $key => $val) {
                    $log = new HllUserPointsLog();
                    $log->user_id = $userId;
                    $log->unique_id = $unique_id;
                    $log->point_id = $key;
                    $log->item_id = $data['item_id'];
                    $log->business_id = $data['business_id'];
                    $log->community_id = $community[$num];
                    $log->point = intval($val);
                    $log->icon = $data['icon'];
                    $log->remark = $data['remark'];
                    $log->category = $data['category'];
                    $log->type = $data['type'];
                    $log->scenes = $data['scenes'];
                    $log->order_id = $data['order_id'];
                    $log->period = intval(date("Ym",time()));
                    $log->creater = $userId;
                    $log->save(false);
                    //获取当前商家所对应的的小区积分
                    $business = intval(static::addPointToBusiness($val, $data['business_id'], $community[$num]));
                    if(!$business){
                        throw new Exception('database error',101);
                    }

                    $business_log = new HllPointBusinessLog();
                    $business_log->user_id = $userId;
                    $business_log->business_id = intval($data['business_id']);
                    $business_log->community_id = intval($community[$num]);
                    $business_log->point = $val;
                    $business_log->left_points = $business;
                    $business_log->type = 1;
                    $business_log->change_reason = $data['change_reason'];
                    $business_log->unique_id = $unique_id;
                    $business_log->period = intval(date("Ym",time()));
                    $business_log->creater = $userId;
                    $business_log->save(false);
                    $command->bindParam(":point", $val, \PDO::PARAM_INT);
                    $command->bindParam(":id", $key, \PDO::PARAM_INT);
                    $command->execute();

                    $num++;
                }
                return $unique_id;
            } catch (Exception $e) {
                throw new Exception($e->getMessage(), 102);
            }
        } else {
            throw new exception("友元余额不足", 101);
        }
    }

    /**
     * 获取用户可用积分
     * @params $userId
     * @params $community_id
     * @params $business
     * @params $type -> 1 -- 商品/商家消费 2 -- 活动消费 3 -- 赠送友元
     * @return array
     */
    public static function availPointsByUser($userId, $community_id, $business = null, $type = 1)
    {
        $query = (new Query())->select(["id", "point", "community_id"])
            ->from('hll_user_points')
            ->where(['user_id' => $userId, 'valid' => 1])
            ->andWhere([">", "expire_time", date("Y-m-d H:i:s")])
            ->andWhere([">", "point", 0]);

        if($type == 1){ //商品/商家消费if($business){
            $availPoints = $query->andFilterWhere(['community_id'=>$community_id])
                ->andWhere(['business_id'=>[$business,0]])
                ->andWhere(['point_type' => [1,2,3]])//1-> 通用友元 2 -> 体验消费友元 3 -> 商家友元
                ->orderBy(['business_id'=>SORT_DESC,'point_type'=>SORT_DESC,'expire_time'=>SORT_ASC])->all();
        }else if($type == 2){ //活动消费
            $availPoints = $query->andFilterWhere(['community_id' => $community_id])
                ->andWhere(['point_type' => [1,4]])//1-> 通用友元 4-> 活动消费友元
                ->orderBy(['point_type'=>SORT_DESC,'expire_time'=> SORT_ASC])->all();

        }else if($type == 3){ //赠送友元
            $availPoints = $query->andWhere(['community_id'=>$community_id])
                ->andWhere(['point_type' => 1])//1-> 通用友元
                ->orderBy("expire_time desc")->all();
        }else{
            $availPoints = $query->orderBy("expire_time asc")->all();
        }
        
        return $availPoints;
    }

    /**
     * 获取用户指定周期友元收入
     *
     * @param $userId
     * @param $period
     * @return int
     * @author zend.wang
     * @time 2017-03-06 15:00
     */
    public static function statIncomePointsByUserAndPeriod($userId, $period)
    {

        $result = (new Query())->select(["SUM(point) as point"])->from('hll_user_points_log')
            ->where(['user_id' => $userId, 'valid' => 1, 'period' => $period, 'type' => 3])->one();

        return ($result['point'] != null) ? $result['point'] : 0;
    }

    /**
     * 获取用户指定周期友元消费
     *
     * @param $userId
     * @param $period
     * @return int
     * @author zend.wang
     * @time 2017-03-06 15:00
     */
    public static function statExpendPointsByUserAndPeriod($userId, $period)
    {

        $result = (new Query())->select(["SUM(point) as point"])->from('hll_user_points_log')
            ->where(['user_id' => $userId, 'valid' => 1, 'period' => $period, 'type' => 1])->one();

        return ($result['point'] != null) ? $result['point'] : 0;
    }

    /**
     * 获取用户各个项目的积分
     * @param $userId
     * @return array
     */
    public static function getUserPointsByCommunity($userId)
    {
        $community = (new Query())->select(['community_id'])->from('hll_user_points')
            ->where(['user_id' => $userId, 'valid' => 1])
            ->andWhere(['>', 'expire_time', date("Y-m-d H:i:s")])
            ->andWhere(['>', 'point', 0])
            ->distinct()->column();

        $data = [];
        if (!$community) {
            $data['common'] = 0;
            $data['total'] = 0;
            return $data;
        } else {
            foreach ($community as $item) {
                if ($item == 0) {
                    $data['common'] = static::getUserPoints($userId, $item,0);
                } else {
                    $community_name = (new Query())->select(['name'])
                        ->from('hll_community')->where(['id' => $item])->scalar();
                    $data[$community_name] = static::getUserPoints($userId, $item,0);
                }
            }
            $data['total'] = array_sum($data);
        }
        if (!array_key_exists('common', $data)) {
            $data['common'] = 0;
        }
        if (!array_key_exists('total', $data)) {
            $data['total'] = 0;
        }
        return $data;
    }

    /**
     * 根据business获取community
     * @param $business_id
     * @return int
     */
    public static function getCommunityByBusiness($business_id)
    {
        $community = (new Query())->select(['community_id'])->from('hll_point_community_business')
            ->where(['business' => $business_id, 'valid' => 1])
            ->andWhere(['<>','community_id',0])->column();

        array_push($community, '0');

        return $community;

    }

    /**
     * 获取活动可用友元的小区
     * @param $accept_point_community_id 活动可用友元的小区
     * @param $community_id 活动从属小区
     * @return null
     */
    public static function getCommunityByEvents($accept_point_community_id, $community_id)
    {
        if ($accept_point_community_id == '0') {
            return 0;
        }
        $community = explode(',', $accept_point_community_id);
        if (in_array($community_id, $community)) {
            $index = array_search($community_id, $community);
            unset($community[$index]);
            array_unshift($community, (string)$community_id);
        }
        if (!in_array(0, $community)) {
            array_push($community, '0');
        }
        return $community;
    }

    /**
     * 小区与商家的友元记录表
     * @param $point
     * @param $business_id
     * @param $community_id
     * @return mixed
     */
    public static function addPointToBusiness($point, $business_id, $community_id)
    {
        $business = HllPointCommunityBusiness::findOne(['business' => $business_id, 'community_id' => $community_id, 'valid' => 1]);
        if ($business) {
            $business->income_point += $point;
            $business->point += $point;
            $new_point = $business->point;
        } else if(!$business && $community_id == 0) {
            $business = new HllPointCommunityBusiness();
            $business->business = $business_id;
            $business->community_id = $community_id;
            $business->income_point = $point;
            $business->point = $point;
            $new_point = $point;
        }else{
            return false;
        }
        $business->save();
        return $new_point;
    }

    /**
     * 用户可用友元数
     * @params userId
     * @params communityId
     * @params  type  1 -> 商品/商家消费 2 -> 活动消费 3 -> 赠送友元
     */
    public static function getUserPoints($userId, $communityId = null, $type = 1, $business=null)
    {
        $query = (new Query())->from('hll_user_points')
            ->where(['user_id' => $userId, 'valid' => 1]);

        if ($communityId !== null) {
            $query->andWhere(['community_id' => $communityId]);

            if ($type == 1) {
                if($business){
                    $query->andWhere(['point_type'=>[1,2,3]]) //1-> 通用友元 3 -> 商家友元 2 -> 体验消费友元
                    ->andWhere(['business_id'=>[$business,0]]);
                }else{
                    $query->andWhere(['point_type' => [1, 2]]); //  1-> 通用友元 2 -> 体验消费友元
                }
            }
            else if ($type == 2){
                $query->andWhere(['point_type' => [1, 4]]);// 1-> 通用友元 4 -> 活动消费友元
            }
            else if ($type == 3) {
                $query->andWhere(['point_type' => 1]); // 1-> 通用友元
            }else{
                $query->andWhere(['point_type' => [1,2,3,4]]);
            }
        }

        $result = $query->andWhere(['>', 'expire_time', date("Y-m-d H:i:s")])
            ->sum('point');

        return !empty($result) ? intval($result) : 0;
    }

    /**
     * 消费退还友元
     * @param $point_log_id
     * @return bool
     */
    public static function payPointsBack($point_log_id){
        $unique_id = uniqid('hll_point_');
        $fields = ['user_id', 'point','remark', 'business_id', 'period','point_id',
            'scenes','icon','category','order_id','community_id','item_id'];
        $point_log = HllUserPointsLog::find()->select($fields)
            ->where(['id'=>$point_log_id])->asArray()->one();
        if($point_log){
            try{
                $point_log['type'] = 3;
                $point_log['remark'] .= '消费退回';
                $point_log['unique_id'] = $unique_id;
                $new_point_log = new HllUserPointsLog();
                if($new_point_log->load($point_log,'') && $new_point_log->save()){
                    $point = HllUserPoints::findOne($point_log['point_id']);
                    if($point){
                        $point->point += $point_log['point'];
                        $point->save();
                        return true;
                    }else{
                        return false;
                    }
                }else{
                    throw new Exception('hll_points_log add error',111);
                }
            }catch (Exception $e){
                return false;
            }
        }
        else{
            return false;
        }
    }
}
