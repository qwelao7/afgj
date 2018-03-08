<?php
namespace mobile\modules\v2\controllers;

use common\models\ecs\EcsUsers;
use common\models\hll\HllZhima;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use Yii;
use yii\db\Query;
use mobile\components\ApiController;
class ZhimaController extends ApiController
{
    /**
     * Created by PhpStorm.
     * User: nancy
     * Date: 2017/4/8
     * Time: 9:47
     */

    /**
     * 芝麻积分
     * @return ApiResponse
     */
    public function actionIndex(){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $zm_score = (new Query())->select(['zm_score'])->from('hll_zhima')
            ->where(['user_id'=>$user_id,'valid'=>1])->scalar();
        if(!$zm_score){
            $response->data = new ApiData(101, '未查询到结果');
        }else{
            $response->data = new ApiData();
            $info['zm_score'] = $zm_score;
            $info['level'] = HllZhima::getScoreLevel(intval($zm_score));
            $response->data->info = $info;
        }
        return $response;
    }

    /**
     * 保存用户信息
     * @params name 用户名
     * @params idcard 身份证号
     * @params redirectUrl 重定向地址
     * @return ApiResponse
     */
    public function actionIdentify(){
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $name = f_get('name',' ');
        $identification = f_get('idcard',' ');
        $user = EcsUsers::findOne($user_id);
        if(!$user){
            $response->data = new ApiData(101, '用户不存在');
            return $response;
        }
        $user->real_name = $name;
        $user->identification = $identification;

        // 修改重定向地址
        $to_url_type = f_get('to_url', 'a');
        $eventId = f_get('event_id', '');
        $to_url = $to_url_type . ':' . $eventId;

        $url = Yii::$app->zhima->getUserAuthorizeUrl($name,$identification,$user_id, $to_url);
        if (empty($url)) {
            $response->data = new ApiData(103, '芝麻认证失败');
            return $response;
        }

        if($user->save()){
            $response->data = new ApiData();
            $response->data->info['url'] = $url;
        }else{
            $response->data = new ApiData(102, '信息保存失败');
        }
        return $response;
    }
}