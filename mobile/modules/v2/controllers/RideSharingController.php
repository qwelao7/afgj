<?php

namespace mobile\modules\v2\controllers;

use common\models\ar\user\AccountAuth;
use common\models\ecs\EcsAccountLog;
use common\models\hll\AccountCar;
use common\models\hll\Bbs;
use common\models\hll\CarBrand;
use common\models\hll\CarFactory;
use common\models\hll\CarSeries;
use common\models\hll\HllBbsUser;
use common\models\hll\HllUserCar;
use common\models\hll\HllUserCarNotification;
use common\models\hll\RideSharing;
use common\models\hll\RideSharingCustomer;
use common\models\hll\HllServiceAgreementSign;
use Yii;
use mobile\components\ApiController;
use common\models\ar\user\AccountAddress;
use common\models\ar\user\Account;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use common\models\ar\system\Area;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\filters\HttpCache;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use common\models\hll\UserAddress;
use common\models\ecs\EcsUsers;
use common\models\ar\message\Message;
use common\components\WxTmplMsg;
use common\models\hll\HllUserPoints;
/**
 * 顺风车
 * @package api\modules\v2\controllers
 */
class RideSharingController extends ApiController
{
    /**
     * 顺丰车信息列表
     * @method get
     * @param  $id int 小区编码
     * @author zend.wang
     * @date  2016-09-28 13:00
     */
    public function actionIndex()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->get();
        if (!$data || empty($data['id'])) {
            $response->data = new ApiData(100, '参数缺失');
            return $response;
        }

        $cur = Yii::$app->user->id;

        $time = time();
        $fields = ['t1.user_id', 't1.user_name', 't1.mobile_phone', 't2.nickname', 't2.headimgurl'];

        //置顶消息
        $val = RideSharing::find()->where(['loupan_id' => $data['id'], 'account_id' => $cur, 'valid' => 1])
            ->andWhere(['>', 'go_time', date("Y-m-d H:i:s", $time - 600)])
            ->orderBy(['go_time' => SORT_DESC])->asArray()->all();
        $result = (new Query())->select(['t1.*'])->from('hll_ride_sharing as t1')->leftJoin('hll_ride_sharing_customer as t2', 't2.rs_id = t1.id')
            ->where(['t1.valid' => 1, 't1.loupan_id' => $data['id'], 't2.status' => [1, 3], 't2.account_id' => $cur, 't2.valid' => 1])
            ->andWhere(['>', 't1.go_time', date("Y-m-d H:i:s", $time - 600)])->all();

        $top['info'] = null;
        //发布者
        if ($val) {
            foreach ($val as &$item) {
                $item['desc'] = EcsUsers::getUser($item['account_id'], $fields);
                $item['car'] = HllUserCar::infoById($item['car_id']);
                $item['isInitiator'] = true;
            }
        } else {
            $val = [];
        }
        //乘客预约
        if ($result) {
            foreach ($result as &$item) {
                $item['desc'] = EcsUsers::getUser($item['account_id'], $fields);
                $item['car'] = HllUserCar::infoById($item['car_id']);
                $item['isInitiator'] = false;
            }
        } else {
            $result = [];
        }

        $items = [];
        array_push($items, $val, $result);
        $top['info'] = $items;

        //列表剔除的id数组
        $exId = [0];
        foreach ($top['info'] as &$item) {
            if($item) {
                foreach($item as &$li) {
                    array_push($exId, intval($li['id']));
                }
            }
        }
        //列表消息
        $query = (new Query())->select('*')->from('hll_ride_sharing')->where(['loupan_id' => $data['id'], 'valid' => 1])
            ->andWhere(['not in', 'id', $exId])
            ->andWhere(['>', 'go_time', date("Y-m-d H:i:s", $time)])
            ->orderBy(['go_time' => SORT_ASC]);

        //数据分页
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => []
        ]);
        if ($dataProvider && $dataProvider->count > 0) {
            $response->data = new ApiData();
            $list = $dataProvider->getModels();
            //循环处理每条记录的关注状态
            foreach ($list as &$item) {
                $item['info'] = EcsUsers::getUser($item['account_id'], $fields);
                $item['car'] = HllUserCar::infoById($item['car_id']);
            }

            $info['list'] = $list;
            $info['top'] = $top;
            $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
            $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
        } else {
            $response->data = new ApiData(110, '无相关数据');
            $info['top'] = $top;
        }

        $response->data->info = $info;

        return $response;

    }

    /**
     * 顺风车历史列表
     */
    public function actionBack()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->get();
        if (!$data || empty($data['id'])) {
            $response->data = new ApiData(100, '参数缺失');
            return $response;
        }

        $cur = Yii::$app->user->id;

        $time = time();
        $fields = ['t1.user_id', 't1.user_name', 't1.mobile_phone', 't2.nickname', 't2.headimgurl'];

        //列表消息
        $query = (new Query())->select(['t1.*'])->from('hll_ride_sharing as t1')
            ->where(['t1.loupan_id' => $data['id'], 't1.valid' => 1])
            ->andWhere(['<', 't1.go_time', date("Y-m-d H:i:s", $time)])->orderBy(['t1.go_time' => SORT_DESC]);

        //数据分页
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => []
        ]);
        if ($dataProvider && $dataProvider->count > 0) {
            $response->data = new ApiData();
            $list = $dataProvider->getModels();
            //循环处理每条记录的关注状态
            foreach ($list as &$item) {
                $item['info'] = EcsUsers::getUser($item['account_id'], $fields);
                $item['car'] = HllUserCar::infoById($item['car_id']);
                $item['hasThank'] = (bool)RideSharingCustomer::find()->where(['rs_id'=>$item['id'], 'valid'=>1, 'account_id'=>$cur, 'status'=>3])->count();
                $item['hasJoin'] = (bool)RideSharingCustomer::find()->where(['rs_id'=>$item['id'], 'valid'=>1, 'account_id'=>$cur, 'status'=>[1,3]])->count();
            }

            $info['list'] = $list;
            $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
            $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
        } else {
            $response->data = new ApiData(110, '无相关数据');
            return $response;
        }

        $response->data->info = $info;
        return $response;
    }

    /**
     * 顺丰车信息详情
     * @method get
     * @param  $id int 顺丰车信息编号
     * @author zend.wang
     * @date  2016-09-28 13:00
     */
    public function actionDetail()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->get();
        if (!$data || empty($data['id'])) {
            $response->data = new ApiData(100, '参数缺失');
            return $response;
        }

        $cur = Yii::$app->user->id;

        $query = RideSharing::find()->where(['id' => $data['id'], 'valid' => 1])->asArray()->one();

        if (!$query) {
            $response->data = new ApiData(101, '数据不存在');
            return $response;
        } else {
            $fields = ['t1.user_id', 't1.user_name', 't1.mobile_phone', 't2.nickname', 't2.headimgurl'];
            $query['info'] = EcsUsers::getUser($query['account_id'], $fields);
            $query['car'] = HllUserCar::infoById($query['car_id']);

            $members = RideSharingCustomer::find()->select(['account_id', 'customer_num', 'thanks_word', "(thanks_point * 0.01) as thanks_point", 'status'])->where(['rs_id' => $data['id'], 'status' => ['1', '3'], 'valid' => 1])->orderBy(['created_at' => SORT_DESC])->asArray()->all();
            foreach ($members as &$member) {
                $member['info'] = EcsUsers::getUser($member['account_id'], $fields);
                $member['address'] = UserAddress::find()->select(['address_desc'])->where(['account_id'=>$member['account_id'], 'community_id'=>$query['loupan_id'], 'owner_auth'=>1, 'valid'=>1])->one();
            }

            $time = time();
            $params['isInitiator'] = ($query['account_id'] == $cur) ? true : false;
            $params['isEnough'] = (count($members) >= $query['leave_seat']) ? true : false;
            $params['isJoin'] = true;
            $params['time'] = $time;
            $params['call'] = $query['info']['mobile_phone'];
            $params['seats'] = $query['leave_seat'];
            //当前时间是否过了出发时间十分钟
            $params['isThank'] = ($time >= (strtotime($query['go_time']) + 600)) ? true : false;
            $params['hasThank'] = (bool)RideSharingCustomer::find()->where(['rs_id'=>$data['id'], 'account_id'=>$cur, 'status'=>3, 'valid'=>1])->count();

            if (!$params['isInitiator']) {
                $exsit = RideSharingCustomer::find()->where(['rs_id' => $data['id'], 'status' => ['1', '3'], 'valid' => 1, 'account_id' => $cur])->count();
                $params['isJoin'] = ($exsit) ? true : false;
            }

            $response->data = new ApiData();
            $response->data->info['detail'] = $query;
            $response->data->info['members'] = $members;
            $response->data->info['params'] = $params;

            return $response;
        }

    }

    /**
     * 修改当前顺丰车信息状态
     * 车主:取消行程 1 或乘客已满 2
     * @method post
     * @param  $id int 顺丰车信息编号
     * @param  $type int  状态编码 (type == 1 取消行程 2 乘客已满)
     * @author zend.wang
     * @date  2016-09-28 13:00
     */
    public function actionStatus()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->post();
        if (!$data || empty($data['rs_id']) || empty($data['type'])) {
            $response->data = new ApiData(100, '参数缺失');
            return $response;
        }

        $userId = Yii::$app->user->id;

        $query = RideSharing::findOne(['id' => $data['rs_id'], 'account_id' => $userId, 'valid' => 1]);

        if (!$query) {
            $response->data = new ApiData(111, '数据不存在');
            return $response;
        }

        //取消行程
        if ($data['type'] == '1') {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $query->valid = 0;
                if ($query->save()) {
                        WxTmplMsg::rideSharingNotification($query['id'], 1);
                        $vals = RideSharingCustomer::find()->select(['id'])->where(['rs_id' => $data['rs_id'], 'valid' => 1])->asArray()->all();
                        $vals = array_column($vals, 'id');
                        RideSharingCustomer::updateAll(['status' => 2], ['id' => $vals]);
                        $transaction->commit();
                        $response->data = new ApiData(0, '取消成功');
                        return $response;
                }
            } catch (\yii\db\Exception $e) {
                $transaction->rollback();
                $response->data = new ApiData(102, '取消行程失败');
                return $response;
            }
        } else if ($data['type'] == '2') {
            //乘客已满
            $query->leave_seat = 0;
            if ($query->save()) {
                $response->data = new ApiData(0, '操作成功');
            } else {
                $response->data = new ApiData(101, '操作失败');
            }

            return $response;
        }

    }

    /**
     * 发布顺丰车信息
     * @method get or post
     * @param $car_id int 所选择的车辆
     * @param $loupan_id int 楼盘
     * @param $account_id int 用户
     * @param  go_time date 开始时间
     * @param $origin string 出发地点
     * @param  destination string 目的地
     * @param $leave_seat int 剩余座位
     * @param  $wish_message string 提醒乘客
     * @return int
     * @author kaikai.qin
     * @date  2016-09-28 13:00
     */
    public function actionPost()
    {

        $response = new ApiResponse();
        $data = Yii::$app->request->post('data');
        $data['go_time'] = date("Y-m-d H:i:s", $data['go_time'] / 1000);
        $data['account_id'] = Yii::$app->user->id;
        $model = new RideSharing();
        if ($model->load($data, '')) {
            if ($model->save()) {
                $response->data = new Apidata(0, '保存成功');
            } else {
                $response->data = new ApiData(110, '保存失败');
            }
        } else {
            $response->data = new ApiData(112, '数据装载失败');
        }
        return $response;
    }

    /**
     * 感谢车主,给车主送积分
     * @author kaikai.qin
     * @date  2016-09-28 13:00
     */
    public function actionThanks()
    {
        $response = new ApiResponse();
        $account_id = Yii::$app->user->id;
        $data = Yii::$app->request->post('data');
        $query = RideSharingCustomer::findOne(['rs_id' => $data['rs_id'], 'account_id' => $account_id, 'status'=>3]);
        if($query) {
            $response->data = new ApiData(120, '不能重复感谢');
            return $response;
        }

        $model = RideSharingCustomer::findOne(['rs_id' => $data['rs_id'], 'account_id' => $account_id, 'status'=>1]);
        $RideSharing = RideSharing::findOne(['id' => $data['rs_id'], 'valid'=>1]);
        if (!$model || !$RideSharing) {
            $response->data = new ApiData(112, '无相关数据');
            return $response;
        }
        $community_id = [$RideSharing->loupan_id,0];
        $userPoints = HllUserPoints::getUserPoints($account_id,$community_id,3);
        if ($userPoints > 0 && $userPoints < $data['thanks_point']) {
            $response->data = new ApiData(100, '输入友元数量超额!');
            $response->data->info['pay_points'] = $userPoints;
        } elseif ($userPoints == 0) {
            $response->data = new ApiData(101, '友元余额不足!');
        } else {
            $trans = Yii::$app->db->beginTransaction();
            try {
                //扣除乘客的积分
                $driver_id = RideSharing::find()
                    ->where(['id' => $data['rs_id'], 'valid' => 1])
                    ->select(['account_id'])->scalar();
                $result = EcsAccountLog::log_account_change($community_id,$account_id, $driver_id, 0, 0, 0, $data['thanks_point']);
                if ($result) {
                    //保存此次感谢语句与感谢积分
                    $model->thanks_word = $data['thanks_word'];
                    $model->thanks_point = $data['thanks_point'];
                    $model->status = 3;
                    if ($model->save()) {
                        $trans->commit();
                        $response->data = new Apidata(0, '保存成功');
                        WxTmplMsg::thanksAccountNotice($model->id,$driver_id, 1); //userPoints 用户当前友元总额(分)
                    } else {
                        $response->data = new Apidata(110, '保存失败');
                    }
                } else {
                    $response->data = new Apidata(112, '扣款失败');
                }
            } catch (\yii\db\Exception $e) {
                $trans->rollBack();
                $response->data = new Apidata(113, '操作失败');
            }
        }
        return $response;
    }

    /**
     * 获取汽车品牌列表
     * @param $keywords string 查询字段
     * @return array
     * @author kaikai.qin
     * @date  2016-09-30 13:00
     */
    public function actionCarBrand($keywords = '')
    {

        $response = new ApiResponse();
        $query = CarBrand::find()->orderBy(['bfirstletter' => SORT_ASC]);
        //根据关键字搜索
        $keywords = trim($keywords);
        if (strlen($keywords) == 1) {
            $query->andWhere(['bfirstletter'=> $keywords]);
        }else{
            $query->andWhere(['like', 'name', $keywords]);
        }
        //对数据进行分页处理
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => []
        ]);
        if ($dataProvider && $dataProvider->count > 0) {
            $response->data = new ApiData();
            $list = $dataProvider->getModels();
            //循环处理每条记录的关注状态
            $info['list'] = $list;
            $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
            $info['pagination']['pageSize'] = $dataProvider->getPagination()->pageSize;
            $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
            $response->data = new ApiData();
            $response->data->info = $info;
        } else {
            $response->data = new ApiData(110, '无相关数据');
            $info['list'] = [];
            $info['pagination']['total'] = 0;
            $info['pagination']['pageSize'] = 0;
            $info['pagination']['pageCount'] = 1;
            $response->data->info = $info;
        }
        return $response;
    }

    /**
     * 获取指定汽车品牌的车型列表
     * @param $id int 品牌编码
     * @method get
     * @return array
     * @author kaikai.qin
     * @date  2016-09-28 13:00
     */
    public function actionCarSeries()
    {

        $response = new ApiResponse();
        $id = $_GET['id'];
        $list = CarSeries::find()->where(['brand_id' => $id])
            ->select(['id', 'brand_id', 'name', 'firstletter'])->orderBy(['firstletter' => SORT_ASC])
            ->asArray()->all();

        if (!$list) {
            $response->data = new ApiData(110, '无相关数据');
            return $response;
        }
        $response->data = new ApiData();
        $response->data->info['list'] = $list;
        return $response;
    }

    /**
     * 设置车辆信息(品牌、车型、颜色 车牌号)
     * @method post
     * @param brand_id int 品牌编码
     * @param $model_id int 车型编码
     * @param $color string 颜色
     * @param $car_num string 车牌号码
     * @return int
     * @author kaikai.qin
     * @date  2016-09-28 13:00
     */
    public function actionSettingCar()
    {
        $response = new ApiResponse();
        $data = Yii::$app->request->post('data');
        $data['account_id'] = Yii::$app->user->id;
        if(isset($data['car_id'])){
            $model = HllUserCar::find()->where(['id'=>$data['car_id'],'account_id'=>$data['account_id'],'valid'=>1])->one();
            if(!$model){
                $response->data = new ApiData(110, '没有相关车辆信息');
                return $response;
            }
        }else{
            $model = new HllUserCar();
        }
        $trans = Yii::$app->db->beginTransaction();
        try{
            $data['record_km_date'] = date("Y-m-d");
            if ($model->load($data, '')) {
                if ($model->save()) {
                    if(!isset($data['car_id'])){
                        $result = HllUserCarNotification::setCarNotification($data['account_id'],$model->id, 0, $data['buy_date']);
                        if($result > 0){
                            //创建时更新
                            HllUserCarNotification::updateWarnningAll($model->id);

                            $trans->commit();
                            $response->data = new Apidata(0, '保存成功');
                        }
                        else{
                            throw new Exception('车辆提醒保存失败',113);
                        }
                    }
                    else{
                        //编辑时更新
                        HllUserCarNotification::updateWarnningAll($data['car_id']);

                        $trans->commit();
                        $response->data = new Apidata(0, '保存成功');
                    }
                }
                else {
                    throw new Exception('保存失败',111);
                }
            }
            else {
                throw new Exception('数据装载失败',112);
            }
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    /**
     * 获取指定用户的汽车列表
     * @param $account_id int 用户id
     * @method get
     * @return array
     * @author kaikai.qin
     * @date  2016-09-28 13:00
     */
    public function actionAccountCar()
    {

        $response = new ApiResponse();
        $account_id = Yii::$app->user->id;
        $list = HllUserCar::find()->where(['account_id' => $account_id,'valid'=>1])
            ->select(['id', 'brand_id', 'series_id', 'color', 'car_num'])
            ->orderBy(['created_at' => SORT_DESC])->asArray()->all();
        if (!$list) {
            $response->data = new ApiData(110, '无相关数据');
            return $response;
        }
        $time = time();
        $info['list'] = $list;
        $info['time'] = $time;
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 用户预约(参加取消)
     * @params userId, rs_id, type(1-参加, 0-取消)
     */
    public function actionJoin()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->post();
        if (!$data || !isset($data['rs_id']) || !isset($data['type'])) {
            $response->data = new ApiData(100, '参数缺失');
            return $response;
        }

        $cur = Yii::$app->user->id;

        //剩余座位数
        $ride = RideSharing::findOne(['id' => $data['rs_id'], 'valid' => 1]);
        $query = RideSharingCustomer::findOne(['rs_id' => $data['rs_id'], 'account_id' => $cur, 'status' => ['1', '3'], 'valid' => 1]);
        $isShow = RideSharingCustomer::find()->where(['rs_id' => $data['rs_id'], 'status' => ['1', '3'], 'valid' => 1])->count('id');
        //动态删除时是否删除'预约乘客'字符
        $isCancel = ($isShow == 1) ? true : false;
        //动态添加时是否加上'预约乘客'字符
        $isShow = ($isShow > 0) ? false : true;

        //参加
        if ($data['type'] == 1) {
            if (!isset($data['customer_num'])) {
                $response->data = new ApiData(100, 'customer_num参数缺失');
                return $response;
            }

            //剩余座位数是否足够
            if ($data['customer_num'] > $ride['leave_seat']) {
                $response->data = new ApiData(111, '很抱歉,剩余座位数不足');
                $response->data->info = $ride['leave_seat'];
                return $response;
            }

            if ($query) {
                $response->data = new ApiData(101, '您已参加预约!');
                return $response;
            } else {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $model = new RideSharingCustomer();
                    $model->rs_id = $data['rs_id'];
                    $model->account_id = $cur;
                    $model->customer_num = $data['customer_num'];
                    $model->status = 1;
                    if ($model->save()) {
                        $ride->leave_seat -= $data['customer_num'];
                        $ride->save();
                    }
                    $transaction->commit();
                } catch (\yii\db\Exception $e) {
                    $transaction->rollback();
                    $response->data = new ApiData(102, '加入失败');
                    return $response;
                }

                $response->data = new ApiData(0, '加入成功');
                $params['num'] = $data['customer_num'];
                $params['isShow'] = $isShow;
                $params['seats'] = $ride['leave_seat'];
                $params['address'] = UserAddress::find()->select(['address_desc'])->where(['account_id'=>$cur, 'community_id'=>$ride['loupan_id'], 'owner_auth'=>1, 'valid'=>1])->one();

                $fields = ['t1.user_id', 't1.user_name', 't2.nickname', 't2.headimgurl'];
                $response->data->info['user'] = EcsUsers::getUser($cur, $fields);
                $response->data->info['params'] = $params;
                WxTmplMsg::joinRideSharingNotification($model->id);
                return $response;
            }
        } else if ($data['type'] == 0) {
            //取消
            if (!$query) {
                $response->data = new ApiData(103, '您暂未预约行程!');
                return $response;
            } else {
                $query->status = 2;
                if ($query->save()) {
                    $ride->leave_seat += $query['customer_num'];
                    if ($ride->save()) {
                        WxTmplMsg::quitRideSharingNotification($query->id);
                        $response->data = new ApiData(0, '取消成功');
                        $response->data->info['isCancel'] = $isCancel;
                        $response->data->info['seats'] = $ride['leave_seat'];
                        $response->data->info['u_id'] = $cur;
                    }
                } else {
                    $response->data = new ApiData(103, '取消失败');
                }

                return $response;
            }
        } else {
            $response->data = new ApiData(104, '参数错误');
            return $response;
        }
    }

    /**
     * 放回用户的小区号
     * @return ApiResponse
     */
    public function actionAccountCommunity()
    {
        $response = new ApiResponse();
        $account_id = Yii::$app->user->id;
        $list = (new Query())->select(['t1.community_id', 't2.name'])
            ->from('hll_user_address as t1')->distinct()
            ->leftJoin('hll_community as t2', 't2.id = t1.community_id')
            ->where(['t1.account_id' => $account_id, 't1.valid' => 1,'t1.owner_auth'=>1])
            ->orderBy(['t1.is_default'=>SORT_ASC,'t1.community_id' => SORT_ASC])->all();
        if (!$list) {
            $response->data = new ApiData(110, '无相关数据');
            return $response;
        }
        $info['id'] = array_column($list, 'community_id');
        $info['name'] = array_column($list, 'name');
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 感谢车主信息
     */
    public function actionThankInfo() {
        $response = new ApiResponse();

        $id = Yii::$app->request->get('id');

        if(!$id) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $data = RideSharing::findOne(['id'=>$id, 'valid'=>1]);
        if($data) {
            $info = EcsUsers::getUser($data['account_id'], ['t1.user_name','t2.nickname','t2.headimgurl']);
        }else {
            $response->data = new ApiData(101, '数据不存在');
            return $response;
        }

        if($info) {
            $response->data = new ApiData();
            $response->data->info = $info;
        }else {
            $response->data = new ApiData(101, '数据不存在');
            return $response;
        }

        return $response;
    }

    /**
     * 已阅读顺丰车协议
     * @return ApiResponse
     */
    public function actionAgree() {
        $response = new ApiResponse();
        $cur = Yii::$app->user->id;

        $model = new HllServiceAgreementSign();
        $model->user_id = $cur;
        $model->satype = 1;
        if($model->save()) {
            $response->data = new ApiData();
        }else {
            $response->data = new ApiData(103, '创建失败');
        }

        return $response;
    }

    public function actionHasAgree() {
        $response = new ApiResponse();
        $cur = Yii::$app->user->id;

        $query = HllServiceAgreementSign::findOne(['user_id'=>$cur, 'satype'=>1, 'valid'=>1]);
        if($query) {
            $response->data = new ApiData();
        }else {
            $response->data = new ApiData(103, '数据错误');
        }

        return $response;
    }
}
