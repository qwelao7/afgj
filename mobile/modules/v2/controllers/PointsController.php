<?php
namespace mobile\modules\v2\controllers;

use common\models\ecs\EcsUsers;
use mobile\components\ApiController;
use common\models\hll\HllUserPoints;
use common\models\hll\HllUserPointsLog;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use Yii;
use yii\base\Exception;
use yii\db\Query;
use common\components\WxTmplMsg;
/**
 * Created by PhpStorm.
 * User: kaikai.qin
 * Date: 2016/11/4
 * Time: 9:22
 */
class PointsController extends ApiController
{

    //我的积分
    public function actionIndex(){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $info = EcsUsers::getUser($user_id,['t2.nickname','t2.headimgurl']);
        $rank_points = HllUserPoints::getUserPointsByCommunity($user_id);
        $rank_name = (new Query())->select(['rank_name','special_rank'])->from('ecs_user_rank')
            ->where(['>=','max_points',$rank_points['common']])->andWhere(['<=','min_points',$rank_points['common']])->one();
        $info['rank_name'] = $rank_name['rank_name'];
        $info['special_rank'] = $rank_name['special_rank'];
        $info['all_points'] = $rank_points['total'];
        $info['other_points'] = $rank_points;
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //获取记录
    public function actionIncome()
    {
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $page = f_get('page',1);
        $word = 'acquire';
        $database = HllUserPoints::getAcquireOrUserPointsByMonth($word, $user_id);

        $info = $this->getDataPage($database,$page);

        $info['period_stat'] = HllUserPoints::getMonth(array_unique(array_column($info['list'],'period')),$user_id);
        $info['list'] = HllUserPoints::getImage($info['list']);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //所有记录
    public function actionAll()
    {
        $response = new ApiResponse();
        $page = f_get('page',1);
        $user_id = Yii::$app->user->id;
        $database = HllUserPoints::getAllPointsByMonth($user_id);

        $info = $this->getDataPage($database,$page);

        $info['period_stat'] = HllUserPoints::getMonth(array_unique(array_column($info['list'],'period')),$user_id);
        $info['list'] = HllUserPoints::getImage($info['list']);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //使用记录
    public function actionExpend()
    {
        $response = new ApiResponse();
        $page = f_get('page',1);
        $user_id = Yii::$app->user->id;
        $word = 'used';
        $database = HllUserPoints::getAcquireOrUserPointsByMonth($word,$user_id);

        $info = $this->getDataPage($database,$page);

        $info['period_stat'] = HllUserPoints::getMonth(array_unique(array_column($info['list'],'period')),$user_id);
        $info['list'] = HllUserPoints::getImage($info['list']);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //过期积分
    public function actionExpire(){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $data = HllUserPoints::getPastPointsByMonth($user_id);
        $response->data = new ApiData();
        $response->data->info = $data;
        return $response;
    }

    /**
     * 积分兑换地址
     * @return ApiResponse
     */
    public function actionGetDuibaUrl() {
        $response = new ApiResponse();

        $userId = Yii::$app->user->id;
        $points = HllUserPoints::getUserPoints($userId,16396,4);

        $response->data = new ApiData();
        $response->data->info = Yii::$app->duiba->getAutoLoginUrl($userId, $points);
        return $response;
    }

    /**
     * 友元分享类型
     * @return ApiResponse
     * @throws Exception
     */
    public function actionSendPointType(){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $list = HllUserPoints::getSendPoint($user_id);

        $response->data = new ApiData();
        $response->data->info = $list;
        return $response;
    }

    /**
     * 分享友元
     * @return ApiResponse
     */
    public function actionSendPoint(){
        $response = new ApiResponse();
        $data['point_type'] = f_get('point_type');
        $data['user_id'] = Yii::$app->user->id;
        $data['to_user_id'] = f_get('to_user_id');
        $data['community_id'] = f_get('community_id');
        $data['business_id'] = f_get('business_id');
        $data['expire_time'] = f_get('expire_time','2018-05-31 23:59:59');
        $data['point'] = f_get('point');
        $data['unique_id'] = uniqid('hll_point_');
        $user_name = EcsUsers::getUser($data['user_id'],['t2.nickname','t2.headimgurl','t2.openid','t1.user_id']);
        $to_user_name = EcsUsers::getUser($data['to_user_id'],['t2.nickname','t2.headimgurl','t2.openid','t1.user_id']);
        $data['user_img'] = $user_name['headimgurl'];
        $data['to_user_img'] = $to_user_name['headimgurl'];
        $data['remark'] = '赠送'.$to_user_name['nickname'];
        $data['to_remark'] = $user_name['nickname'].'赠送您';
        $data['category'] = 'share';
        $data['type'] = HllUserPointsLog::EXPEND_POINT_TYPE;
        $data['to_type'] = HllUserPointsLog::INCOME_POINT_TYPE;
        $data['scenes'] = HllUserPointsLog::$scenes_type[3];

        try{
            HllUserPoints::sharePoints($data);
            $response->data = new ApiData();
            $left_point = HllUserPoints::getUserPoints($data['user_id']);
            WxTmplMsg::PointChangeNotice($user_name,$data['point'],$left_point,'分享友元',4);
            $left_point = HllUserPoints::getUserPoints($data['to_user_id']);
            WxTmplMsg::PointChangeNotice($to_user_name,$data['point'],$left_point,'分享友元',4);
        }catch (Exception $e){
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }
}