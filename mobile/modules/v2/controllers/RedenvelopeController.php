<?php
namespace mobile\modules\v2\controllers;

use common\models\ecs\EcsUsers;
use common\models\hll\HllRedEnvelopeDetail;
use common\models\hll\Redenvelope;
use common\models\hll\RedenvelopeDetail;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\filters\Cors;
use yii\filters\HttpCache;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use mobile\modules\v2\models\User;
use yii\data\ActiveDataProvider;
/**
 * 红包
 * Class RedenvelopeController
 * @package mobile\modules\v2\controllers
 */
class RedenvelopeController extends ApiController  {
    public $second_cache = 30;
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => f_params('origins'),//定义允许来源的数组
                    'Access-Control-Request-Method' => ['GET','POST','PUT','DELETE', 'HEAD', 'OPTIONS'],//允许动作的数组
                ],
            ],
            [
                'class' => HttpCache::className(),
                'only' => ['result'],
                'lastModified' => function () { // 设置 Last-Modified 头
                    return time() + $this->second_cache;
                },
                'cacheControlHeader' => 'Cache-Control: public, max-age='.$this->second_cache,
            ],
        ]);
    }
    /**
     * 红包
     * @author zend.wang
     * @date  2016-10-27 13:00
     */
    public function actionIndex() {

        $response = new ApiResponse();

        $result = Redenvelope::getRedenvelope();
        if(!$result) {
            $response->data = new ApiData(1,'数据不存在');
            return $response;
        }

        $info = ['id'=>$result['id'],'startTime'=>$result['startTime'],'endTime'=>$result['endTime']];

        $userId = Yii::$app->user->id;
        $join_hash_key = "redenvelope_join_hash_{$info['id']}";
        $redis = Yii::$app->redis;

        if($redis->hexists($join_hash_key,$userId)) {
            $response->data = new ApiData(3,'已抢到红包');
            $response->data->info = $info;
            return $response;
        }

        $token_num = Redenvelope::getJoinNumByReId($result['id']);

        if($token_num && $token_num >= $result['total_num']) {
            $response->data = new ApiData(2,'红包抢光了');
            //$response->data->info = $info;
            return $response;
        }

        $response->data = new ApiData(0);
        $response->data->info = $info;
        return $response;
    }

    public function actionResult($id) {

        $response = new ApiResponse();
        $join_hash_key = "redenvelope_join_hash_{$id}";
        $userId = Yii::$app->user->id;
        $redis = Yii::$app->redis;
        $id =$redis->hget($join_hash_key,$userId);
        if(!$id) {
            $response->data = new ApiData(1,'参数异常');
            return $response;
        }
        $data = RedenvelopeDetail::findOne($id);

        if($data['user_id'] && $data['user_id'] != $userId) {
            $response->data = new ApiData(2,'当前用户未参与活动');
            return $response;
        }

        $user = EcsUsers::getUser($userId,['t2.nickname','t2.headimgurl']);
        $info=['remoney'=>$data->remoney,'headimgurl'=>$user['headimgurl'],'nickname'=>$user['nickname']];
        $wxConfigs = Yii::$app->util->getWeixinConfig();
        $info['wx']=$wxConfigs;
        $response->data = new ApiData(0);
        $response->data->info = $info;
        return $response;
    }
    public function actionJoin() {

        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        $result = Redenvelope::join($userId);
        $response->data = new ApiData($result[0],$result[1]);
        return $response;
    }

    /**
     * 红包列表
     * @return ApiResponse
     */
    public function actionList(){
        $response = new ApiResponse();

        $info['id'] = RedEnvelopeDetail::getAllEnvelopeId();
        $info['lastId'] = $info['id'][0];
        $info['query'] = RedenvelopeDetail::getLastEnvelopeDetail($info['lastId']['reid']);
        $info['id'] = array_splice($info['id'],1);
        $response->data = new ApiData('0','操作成功');
        $response->data->info = $info;
        return $response;
    }

    public function actionDetail($id){
        $response = new ApiResponse();
        $info['query'] = RedenvelopeDetail::getEnvelopeDetail($id);
        $response->data = new ApiData('0','操作成功');
        $response->data->info = $info;
        return $response;
    }

    public function actionHasLucky() {
        $response = new ApiResponse();

        $time = date('Y-m-d H:i:s', time());

        $val = Redenvelope::find()->where(['retype'=>1, 'share_return'=>1, 'valid'=>1])
                                    ->andWhere(['<', 'start_time', $time])
                                    ->andWhere(['>', 'end_time', $time])->orderBy(['id'=>SORT_DESC])->one();
        $val = (bool)$val;
        $response->data = new ApiData();
        $response->data->info = $val;
        return $response;
    }

}
