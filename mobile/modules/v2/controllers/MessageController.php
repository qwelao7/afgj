<?php
namespace mobile\modules\v2\controllers;

use common\models\ar\admin\Admin;
use common\models\ar\fang\FangHouse;
use common\models\ar\message\MessageChat;
use common\models\ar\user\AccountAddress;
use common\models\ar\user\Account;
use common\models\ar\user\AccountFriend;
use common\models\hll\HllBbsUser;
use Yii;
use mobile\components\ApiController;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use mobile\modules\v2\models\User;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use common\models\ar\fang\FangLoupan;
use common\models\ar\message\Message;
use common\components\Util;
use yii\filters\HttpCache;


use common\models\ecs\EcsUsers;
use common\models\hll\UserAddressExt;
use common\components\WxTmplMsg;

/**
 * 消息操作接口
 *
 * Class CommunityController
 * @package api\modules\v1\controllers
 */
class MessageController extends ApiController
{
//    public $second_cache = 60;
//    public function behaviors()
//    {
//        return ArrayHelper::merge(parent::behaviors(), [
//            [
//                'class' => HttpCache::className(),
//                'only' => ['index', 'send'],
//                'lastModified' => function () { // 设置 Last-Modified 头
//                    return time() + $this->second_cache;
//                },
//                'cacheControlHeader' => 'Cache-Control: public, max-age='.$this->second_cache,
//            ],
//        ]);
//    }

    /**
     *
     * @SWG\Get(path="/message/index",
     *     tags={"message"},
     *     summary="来信首页",
     *     description="来信首页",
     *     consumes={"application/x-www-form-urlencoded","application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "access_token",
     *        description = "访问令牌",
     *        required = false,
     *        type = "string"
     *     ),
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "loupanId",
     *        description = "楼盘ID",
     *        required = true,
     *        type = "integer",
     *        format="int64"
     *     ),
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "page",
     *        description = "第几页默认1",
     *        required = false,
     *        type = "integer",
     *        format="int64"
     *     ),
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "per-page",
     *        description = "每页数默认20",
     *        required = false,
     *        type = "integer",
     *        format="int64"
     *     ),
     *     @SWG\Parameter(
     *        in = "formData",
     *        name = "keywords",
     *        description = "搜索关键字",
     *        required = false,
     *        type = "string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="成功操作",
     *         examples={{"name":"zhangsan"},{"name":"zhangsan"}},
     *         @SWG\Schema(ref="#/definitions/ApiResponse")
     *     ),
     *   @SWG\Response(response=401, description="参数异常"),
     *   @SWG\Response(response=404, description="用户不存在"),
     *
     *   security={{
     *     "api_key":{}
     *   }}
     * )
     *
     */
    public function actionIndex()
    {
        $response = new ApiResponse();
        $response->data = new ApiData();
        $userId = Yii::$app->user->id;
        $now = date('Y-m-d H:i:s', time());

        //用户住房所在楼盘
        $loupans = FangLoupan::find()->select(['id', 'name', 'thumbnail'])->indexBy('id')->asArray()->all();
        //获取最新的一条楼盘资讯
        $lastNews = Message::find()->select(['title', 'loupan_id', 'publish_time'])
            ->where(['message_type' => [1, 2], 'valid' => 1, 'publish_status' => [1, 2]])->andWhere(['<', 'publish_time', $now])
            ->orderBy(['publish_time' => SORT_DESC])->asArray()->one();

        if ($lastNews) {
            $lastNews['publish_time'] = Util::formatTime($lastNews['publish_time']);
            $lastNews['loupan_desc'] = $loupans[$lastNews['loupan_id']];
            $response->data->info['news'] = $lastNews;
        } else {
            $response->data->info['news'] = false;
        }
        //用户论坛
        $bbs = HllBbsUser::getBbsListByUser($userId);
        if ($bbs) {
            $response->data->info['bbs'] = $bbs;
        } else {
            $response->data->info['bbs'] = false;
        }
        $response->data->info['user'] = EcsUsers::getUser(Yii::$app->user->id, ['t1.user_id', 't1.user_name']);

        return $response;
    }

    /**
     *
     * @SWG\Get(path="/message/send",
     *     tags={"message"},
     *     summary="同友邻私聊",
     *     description="同友邻私聊",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "access_token",
     *        description = "访问令牌",
     *        required = false,
     *        type = "string"
     *     ),
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "userId",
     *        description = "对话的友邻ID",
     *        required = true,
     *        type = "integer",
     *        format="int64"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="成功操作",
     *         examples={{"name":"zhangsan"},{"name":"zhangsan"}},
     *         @SWG\Schema(ref="#/definitions/ApiResponse")
     *     ),
     *   @SWG\Response(response=401, description="参数异常"),
     *   @SWG\Response(response=404, description="用户不存在"),
     *
     *   security={{
     *     "api_key":{}
     *   }}
     * )
     *
     */
    public function actionSend($userId, $type)
    {
        $response = new ApiResponse();

        $cur = Yii::$app->user->id;

        $toUser = EcsUsers::getUser($userId);
        $toNick = ($toUser['nickname'])?$toUser['nickname']:$toUser['user_name'];
        if (!$toUser) {
            $response->data = new ApiData(1, '参数异常');
            return $response;
        }
        $currUser = EcsUsers::getUser(Yii::$app->user->id);
        $response->data = new ApiData(0);
        $response->data->info = ['uid' => "{$currUser['user_id']}",
            'appkey' => Yii::$app->im->appkey, 'password' => 'abc123', 'touid' => "{$toUser['user_id']}",
            'avatar' => EcsUsers::getAvatar($currUser['headimgurl']),
            'toAvatar' => EcsUsers::getAvatar($toUser['headimgurl']),
            'toNickname' => $toNick];
        return $response;
    }

    /**
     *
     * @SWG\Get(path="/message/send",
     *     tags={"message"},
     *     summary="删除私聊会话",
     *     description="删除私聊会话",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "access_token",
     *        description = "访问令牌",
     *        required = false,
     *        type = "string"
     *     ),
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "userId",
     *        description = "对话的友邻ID",
     *        required = true,
     *        type = "integer",
     *        format="int64"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="成功操作",
     *         examples={{"name":"zhangsan"},{"name":"zhangsan"}},
     *         @SWG\Schema(ref="#/definitions/ApiResponse")
     *     ),
     *   @SWG\Response(response=401, description="参数异常"),
     *   @SWG\Response(response=404, description="用户不存在"),
     *
     *   security={{
     *     "api_key":{}
     *   }}
     * )
     *
     */
    public function actionCancel($userId)
    {
        $response = new ApiResponse();
        $toUser = User::findOne(['id' => $userId, 'status' => 1]);
        if (!$toUser) {
            $response->data = new ApiData(1, '参数异常');
            return $response;
        }
        $messageChatModel = MessageChat::findOne(['account' => Yii::$app->user->id, 'to_account_id' => $userId, 'valid' => 1]);
        if ($messageChatModel) {
            $messageChatModel->valid = 0;
            $messageChatModel->save();
            $response->data = new ApiData(0);
        } else {
            $response->data = new ApiData(4, '记录不存在');
        }
        return $response;
    }
}
