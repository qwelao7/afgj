<?php

namespace mobile\modules\v2\controllers;


use common\models\ar\user\AccountFriend;
use common\models\ecs\EcsUsers;
use common\models\ecs\EcsWechatUser;
use common\models\ar\user\AccountSkill;
use Yii;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\filters\HttpCache;
use yii\db\Query;
use common\models\ecs\EcsSessions;
use common\models\SpringActivity;
/**
 * 用户接口控制器
 * @package api\modules\v1\controllers
 */
class UserController extends ApiController
{
//    public $second_cache = 60;
//    public function behaviors()
//    {
//        return ArrayHelper::merge(parent::behaviors(), [
//            [
//                'class' => HttpCache::className(),
//                'only' => ['skill'],
//                'lastModified' => function () { // 设置 Last-Modified 头
//                    return time() + $this->second_cache;
//                },
//                'cacheControlHeader' => 'Cache-Control: public, max-age='.$this->second_cache,
//            ],
//        ]);
//    }

    /**
     *
     * @SWG\Get(path="/user/info",
     *     tags={"user"},
     *     summary="获取用户信息",
     *     description="获取用户信息,默认获取当前登录用户,也可以获取指定用户信息",
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
    public function actionInfo($userId = 0)
    {
        $limit = 4;
        $curUserId = Yii::$app->user->id;

        $response = new ApiResponse();

        if (!$userId) $userId = $curUserId;
        $user = EcsUsers::getUser($userId);
        if (!$user) {
            $response->data = new ApiData(101, '用户未找到');
            return $response;
        }

        $skills = AccountSkill::find()->where(['account_id' => $userId])->select('skill')->orderBy(['updated_at' => SORT_DESC])->limit($limit)->asArray()->all();
        $skills = array_column($skills, 'skill');

        $response->data = new ApiData();
        $response->data->info['list'] = $user;
        $response->data->info['skills'] = $skills;

        return $response;
    }

    /**
     *
     * @SWG\Get(path="/user/follow",
     *     tags={"user"},
     *     summary="关注",
     *     description="用户关注指定邻居",
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
     *        description = "关注用户ID",
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
     *
     * )
     *
     */
    public function actionFollow($userId)
    {

        $response = new ApiResponse();
        //userid 是否存在 不存在报错
        if (empty($userId)) {
            $response->data = new ApiData(101, 'userid不能空');
            return $response;
        }

        //ecs_users表中 是否有userId 关注用户不存在
        $result = EcsUsers::getUser($userId);
        if (!$result) {
            $response->data = new ApiData(102, '关注用户不存在');
            return $response;
        }
        $currentUserId = Yii::$app->user->id;
        $result = AccountFriend::follow($currentUserId, $userId);
        if ($result) {
            //新春活动
            $task_id = 2;
            $spring = new SpringActivity();
            $spring->triggerSendTemplate($currentUserId, $task_id);

            $response->data = new ApiData(0, '成功关注');
        } else {
            $response->data = new ApiData(106, '关注失败');
        }
        return $response;
    }

    /**
     *
     * @SWG\Get(path="/user/unfollow",
     *     tags={"user"},
     *     summary="取消关注",
     *     description="用户取消关注指定邻居",
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
     *        description = "关注用户ID",
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
     *
     * )
     *
     */
    public function actionUnfollow($userId)
    {

        $response = new ApiResponse();
        //userid 是否存在 不存在报错
        if (empty($userId)) {
            $response->data = new ApiData(101, 'userid不能空');
            return $response;
        }

        //ecs_users表中 是否有userId 关注用户不存在
        $result = EcsUsers::getUser($userId);
        if (!$result) {
            $response->data = new ApiData(102, '关注用户不存在');
            return $response;
        }
        $currentUserId = Yii::$app->user->id;
        $result = AccountFriend::unfollow($currentUserId, $userId);
        if ($result) {
            $response->data = new ApiData(0, '取消关注成功');
        } else {
            $response->data = new ApiData(106, '取消关注失败');
        }
        return $response;

    }
    
    /**
     * 更新用户信息
     */
    public function actionUpdate()
    {
        $response = new ApiResponse();

        $fields = ['sex', 'mobile_phone', 'nickname', 'headimgurl'];
        $data = Yii::$app->request->post();
        $userId = Yii::$app->user->id;

        $model = EcsUsers::findOne($userId);
        $wechatUser = EcsWechatUser::find()->where(['ect_uid' => $userId])->one();
        if ($model && $data) {
            foreach ($data as $key => $val) {
                if (!in_array($key, $fields, true)) {
                    $response->data = new ApiData(108, '非法字段');
                    return $response;
                }
            }
            //更新手机号
            if (!empty($data['mobile_phone'])) {
                $isExistUser = EcsUsers::findOne(['mobile_phone'=>$data['mobile_phone']]);
                if (!$isExistUser && $model->load($data, '')) {
                    if ($model->validate()) {
                        $result = $model->save();
                        if ($result) {
                            $response->data = new ApiData(0, '更新成功');
                        } else {
                            $response->data = new ApiData(110, '更新失败');
                        }
                    } else {
                        $response->data = new ApiData(111, implode(',', $model->getFirstErrors()));
                    }
                } else {
                    $response->data = new ApiData(112, '该号码已存在');
                }
            } else {
                $wechatUser->load($data, '');
                if($wechatUser->validate()) {
                    if($wechatUser->save()) {
                        $response->data = new ApiData(0, '更新成功');
                    }else {
                        $response->data = new ApiData(110, '更新失败');
                    }
                }
            }

            //更新性别
            if(isset($data['sex'])) {
                $data['sex'] = intval($data['sex']);
                $wechatUser->load($data, '');
                if ($wechatUser->validate() && $wechatUser->save()) {
                    $model->load($data, '');
                    if($model->validate() && $model->save()) {
                        $response->data = new ApiData(0, '更新成功');
                    }else {
                        $response->data = new ApiData(110, '更新失败');
                    }
                }
            }

        } else {
            $response->data = new ApiData(102, '无相关数据');
        }

        return $response;
    }

    /**
     *  获取技能列表(前100个和自己的技能)
     */
    public function actionSkill($userId = 0)
    {
        $limit = 100;

        $response = new ApiResponse();

        if (!$userId) $userId = Yii::$app->user->identity->id;

        $user = AccountSkill::find()->where(['account_id' => $userId])->select('skill')->orderBy(['updated_at' => SORT_DESC])->asArray()->all();
        $user = array_column($user, 'skill');

        $skills = AccountSkill::find()->select(['id', 'skill'])->groupBy('skill')->orderBy('count(1) Desc')->limit($limit)->asArray()->all();

        $response->data = new ApiData();
        $response->data->info['user'] = $user;
        $response->data->info['skills'] = $skills;

        return $response;
    }

    /**
     *  用户退出登录
     */
    public function actionExit()
    {
        $response = new ApiResponse();

        $userId = Yii::$app->user->id;
        $session = EcsSessions::find()->where(['userid'=>$userId])->one();
        if($session) {
            if($session->delete()) {
                $response->data = new ApiData(0, '退出成功');
            }else {
                $response->data = new ApiData(1, '退出失败');
            }
        }else {
            $response->data = new ApiData(101, '用户不存在');
        }

        return $response;

    }

    /**
     * 增加用户技能
     */
    public function actionAddSkill()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->post();
        if (!$data || empty($data['skill'])) {
            $response->data = new ApiData(101, '参数缺失');
            return $response;
        }
        $data['account_id'] = Yii::$app->user->id;

        $model = new AccountSkill();

        if ($model->load($data, '')) {

            if ($model->validate()) {
                if ($model->save()) {
                    $response->data = new ApiData(0, '创建成功');
                } else {
                    $response->data = new ApiData(110, '保存失败');
                }
            } else {
                $response->data = new ApiData(111, implode(',', $model->getFirstErrors()));
            }
        } else {
            $response->data = new ApiData(112, '数据装载失败');
        }

        return $response;
    }

    /**
     * 删除用户技能
     */
    public function actionDeleteSkill()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->post();
        if (!$data || empty($data['skill'])) {
            $response->data = new ApiData(101, '参数缺失');
            return $response;
        }

        $userId = Yii::$app->user->id;

        $model = AccountSkill::find()->where(['account_id' => $userId, 'skill' => $data['skill']])->one();
        if ($model) {
            $model->delete();
            $response->data = new ApiData();
        } else {
            $response->data = new ApiData(102, '无相关数据');
        }

        return $response;
    }

    public function actionTouchNav(){
        $response = new ApiResponse();

        $nav = (new Query())->select(['t1.id', 't1.cid', 't1.name', 't1.url', 't1.pic', 't2.cname'])->from('ecs_touch_nav as t1')
            ->leftJoin('hll_nav_category as t2','t2.id = t1.cid')
            ->where(['t1.ifshow'=>1,'t2.is_show'=>1])->orderBy(['t2.view_order'=>SORT_ASC,'t1.vieworder'=>SORT_ASC])->all();
        $pics = (new Query())->select(['ad_code', 'ad_name', 'ad_link'])->from('ecs_ad')->where(['enabled'=>1])->all();
        if(!$pics){
            $info['pic'] = [];
        }else{
            $info['pic'] = $pics;
        }
        if($nav){
            foreach($nav as $item){
                $info['data'][$item['cname']][] = $item;
            }
            foreach($info['data'] as $key=>$val){
                $info['num'][$key] = sizeof($val);
            }
        }

        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 搜索用户昵称
     * @params $keyword 搜索内容
     * @params $page 页数
     */
    public function actionSearchUser ($keyword) {
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;

        if (!isset($keyword)) {
            $response->data = new ApiData(200, '请输入搜索内容');
            return $response;
        }

        $query = EcsWechatUser::find()->select(['ect_uid', 'nickname', 'headimgurl'])
                ->where(['like', 'nickname', $keyword])
                ->andWhere('ect_uid != ' . $userId)
                ->orderBy(['uid' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => []
        ]);

        if ($dataProvider && $dataProvider->count > 0) {
            $response->data = new ApiData();
            $list = $dataProvider->getModels();

            $info['list'] = $list;
            $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
            $info['pagination']['pageSize'] = $dataProvider->getPagination()->pageSize;
            $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
        }else {
            $info['list'] = [];
            $info['pagination']['total'] = 0;
            $info['pagination']['pageSize'] = 0;
            $info['pagination']['pageCount'] = 1;
        }

        $response->data = new ApiData();
        $response->data->info = $info;

        return $response;
    }
}

