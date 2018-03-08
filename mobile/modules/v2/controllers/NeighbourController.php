<?php

namespace mobile\modules\v2\controllers;


use common\models\ar\credit\CreditComplain;
use common\models\ar\fang\FangLoupan;
use common\models\ar\user\AccountAddress;
use common\models\ar\user\AccountFriend;
use Yii;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\helpers\ArrayHelper;
use mobile\modules\v2\models\User;
use common\models\ar\user\Account;
use common\models\ar\user\AccountInvite;
use common\models\ar\system\QrCode;
use yii\db\Query;
use yii\data\ActiveDataProvider;
use yii\filters\HttpCache;

use common\models\ecs\EcsUsers;
use common\models\hll\UserAddressExt;
use common\models\hll\UserAddress;
/**
 * 邻居接口控制器
 * @package api\modules\v1\controllers
 */
class NeighbourController extends ApiController {

//    public $second_cache = 60;
//    public function behaviors()
//    {
//        return ArrayHelper::merge(parent::behaviors(), [
//            [
//                'class' => HttpCache::className(),
//                'only' => ['talk', 'view', 'index'],
//                'lastModified' => function () { // 设置 Last-Modified 头
//                    return time() + $this->second_cache;
//                },
//                'cacheControlHeader' => 'Cache-Control: public, max-age='.$this->second_cache,
//            ],
//        ]);
//    }

    /**
     *
     * @SWG\Get(path="/neighbour/index",
     *     tags={"neighbour"},
     *     summary="我的友邻列表",
     *     description="已关注邻居列表信息",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response="200",
     *         description="成功操作",
     *         examples={{"name":"zhangsan"},{"name":"zhangsan"}},
     *         @SWG\Schema(ref="#/definitions/ApiResponse")
     *     ),
     *   @SWG\Response(response=401, description="参数异常"),
     *
     * )
     *
     */
    public function actionIndex() {
        $response = new ApiResponse();
        //我的社区
        $info['communitys'] = UserAddress::getCommunitysStatByUser(Yii::$app->user->id);
        $communityIds = ArrayHelper::getColumn($info['communitys'],"id");
        //我的友邻
        $info['neighbours'] =  AccountFriend::getNeighboursByUser(Yii::$app->user->id,$communityIds);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     *
     * @SWG\Get(path="/neighbour/view",
     *     tags={"neighbour"},
     *     summary="友邻详细信息",
     *     description="获取友邻详细信息",
     *     consumes={"application/json"},
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
     *        description = "用户ID",
     *        required = false,
     *        type = "integer",
     *        format="int64"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="成功操作",
     *         examples={{"name":"zhangsan"},{"name":"zhangsan"}},
     *         @SWG\Schema(ref="#/definitions/ApiResponse")
     *     ),
     *   @SWG\Response(response=401, description="Unauthorized"),
     *   @SWG\Response(response=400, description="Invalid ID supplied"),
     *   @SWG\Response(response=404, description="Order not found"),
     *
     *   security={{
     *     "api_key":{}
     *   }}
     * )
     *
     */
    public function actionView($userId=0) {

        $response = new ApiResponse();
        //判断userId是否存在,不存在返回错误 参数异常
        if(empty($userId)){
            $response->data = new ApiData(102,'用户不能为空');
            return $response;
        }
        $user = EcsUsers::getUser($userId);

        if(!$user) {
            $response->data = new ApiData(101,'用户未找到');
            return $response;
        }
        $info=['nickName'=>$user['nickname'],'avatar'=>Account::getAvatar($user['headimgurl']),'remarkName'=>'','lables'=>[],'isFollow'=>false,'commonCommunitys'=>[]];
        //昵称
        if(!$info['nickName']) {
            $info['nickName'] = $user['user_name'];
        }
        //技能
        $info['lables'] = EcsUsers::getUserSkills($userId);

        //是否关注 备注
        $model = AccountFriend::find()->where(['account_id' => Yii::$app->user->id])->andwhere(['friend_id' => $userId, 'status' => 4])->asArray()->one();
        if ($model) {
            $info['isFollow'] = true;
            $info['remarkName'] = $model['remark_name'];
        }
        //求与邻居共同的小区
        $info['commonCommunitys'] = UserAddress::getIntersectCommunitys(Yii::$app->user->id,$userId);
        $response->data = new ApiData();
        $response->data->info = $info;

        return $response;
    }

    /**
     *
     * @SWG\Post(path="/neighbour/remarks",
     *     tags={"neighbour"},
     *     summary="修改友邻备注",
     *     description="修改友邻备注",
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
     *        in = "formData",
     *        name = "userId",
     *        description = "待修改友邻ID",
     *        required = true,
     *        type = "integer",
     *        format="int64"
     *     ),
     *     @SWG\Parameter(
     *        in = "formData",
     *        name = "content",
     *        description = "修改备注的内容",
     *        required = true,
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
    public function actionRemarks() {

        $response = new ApiResponse();
        $data = Yii::$app->request->post();

        //校验: 数据是否合法,当前传入参数用户是否被关注
        $userId = $data['userId'];
        $content = $data['content'];
        if (empty($userId) || empty($content)) {
            $response->data = new ApiData(101, '数据不能为空');
            return $response;
        }

        $result = EcsUsers::getUser($userId);
        if (!$result) {
            $response->data = new ApiData(102, '友邻ID不存在');
            return $response;
        }

        //更新account_friend
        $currentUserId = Yii::$app->user->id;
        $model = AccountFriend::find()->where(['account_id' => $currentUserId])->andwhere(['friend_id' => $userId, 'status' => 4])->one();
        if (!$model) {
            $response->data = new ApiData(102, '修改备注失败');
        }else {
            list($first_letter,$display_order) = f_firstLetter($content);
            $model->first_letter = $first_letter;
            $model->display_order = $display_order;
            $model->remark_name = $content;
            $result = $model->save();
            if($result) {
                $response->data = new ApiData(0, '修改备注成功');
            } else{
                $response->data = new ApiData(102, '修改备注失败');
            }
        }
        //返回结果
        return $response;
    }
    /**
     *
     * @SWG\Get(path="/neighbour/talk",
     *     tags={"neighbour"},
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
    public function actionTalk($userId) {
        $response = new ApiResponse();
        $toAvatar = EcsUsers::find()->select(['avatar'])->where(['id'=>$userId])->scalar();
        $password = 'abc123';
        $response->data = new ApiData(0);
        $response->data->info =['uid'=>(string)Yii::$app->user->id,
            'appkey'=>Yii::$app->im->appkey,'password'=>$password,'touid'=>$userId,
            'avatar'=>Yii::$app->user->identity->avatar,
            'toAvatar'=>$toAvatar];
        return $response;
    }
    /**
     *
     * @SWG\Post(path="/neighbour/invite",
     *     tags={"neighbour"},
     *     summary="邀请友邻",
     *     description="邀请友邻",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "access_token",
     *        description = "访问令牌",
     *        required = false,
     *        type = "string"
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
    public function actionInvite() {
        $response = new ApiResponse();
        $response->data = new ApiData(0);

        list($invitationSuccessCount,$invitationSuccessMoney) = AccountInvite::getInvitationStatByUserId(Yii::$app->user->id,2);
        $info['invite_code'] = Yii::$app->user->id;//邀请码
        $info['qr_pic'] = QrCode::generateInvitationFriendQrcode(Yii::$app->user->id);//二维码图片
        $info['num'] = $invitationSuccessCount;//邀请成功人数
        $info['money'] = $invitationSuccessMoney;//返利总额

        $response->data->info =$info;
        return $response;
    }
    /**
     *
     * @SWG\Post(path="/neighbour/invitelog",
     *     tags={"neighbour"},
     *     summary="邀请友邻日志",
     *     description="邀请友邻日志",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "access_token",
     *        description = "访问令牌",
     *        required = false,
     *        type = "string"
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
    public function actionInvitelog() {
        $response = new ApiResponse();
        $response->data = new ApiData(0);
        $info = AccountInvite::getInvitationListByUserId(Yii::$app->user->id,2);
        $response->data->info = $info;
        return $response;
    }
    /**
     *
     * @SWG\Post(path="/neighbour/appeal",
     *     tags={"neighbour"},
     *     summary="投诉友邻",
     *     description="投诉友邻",
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
     *        in = "formData",
     *        name = "userId",
     *        description = "待投诉用户ID",
     *        required = true,
     *        type = "integer",
     *        format="int64"
     *     ),
     *     @SWG\Parameter(
     *        in = "formData",
     *        name = "content",
     *        description = "投诉详情",
     *        required = true,
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
    public function actionAppeal() {

        $response = new ApiResponse();
        $data = Yii::$app->request->post();
        //校验: 数据是否合法,
        $userId = $data['userId'];
        $content = $data['content'];
        $pic = $data['picpath'];
        if (empty($userId) or empty($content)) {
            $response->data = new ApiData(101, '数据不能为空');
            return $response;
        }
        $result = EcsUsers::find()->where(['user_id' => $userId])->asArray()->one();
        if (!$result) {
            $response->data = new ApiData(102, '友邻ID不存在');
            return $response;
        }
        //获取对当前邻居的最新投诉,确保比如5分钟内,只能投诉一次.频繁操作,提醒用户稍后投诉
        $currentUserId = Yii::$app->user->id;
        $result = CreditComplain::find()->select('created_at')
            ->where(['account_id'=>$currentUserId, 'complain_account_id'=>$userId])
            ->orderBy(['created_at'=>SORT_DESC])->asArray()->one();
        $time = time() - strtotime($result['created_at']);
        if($result && $time<300) {
            $response->data = new ApiData(103, '频繁操作,请五分钟后再试！');
            return $response;
        }else {
            //添加投诉信息
            //返回结果
            $model = new CreditComplain();
            $model->account_id = $currentUserId;
            $model->creater = $currentUserId;
            $model->complain_pic =$pic;
            $model->complain_account_id = $userId;
            $model->complain_content = $content;
            if($model->save()) {
                $response->data = new ApiData(0, '投诉成功，等待受理！');
                return $response;
            }else {
                $response->data = new ApiData(104, '操作失败！');
                return $response;
            }
        }
    }
    
}

