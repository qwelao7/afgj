<?php

namespace mobile\modules\v2\controllers;

use common\models\ar\fang\FangCommunityActivity;
use common\models\ar\fang\FangCommunityActivityAccount;
use common\models\ar\message\MessageComment;
use common\models\ar\message\MessagePraise;
use common\models\ar\message\MessageVote;
use common\models\ar\message\MessageVoteQuestion;
use common\models\ar\user\AccountAuth;
use common\models\ecs\EcsWechatUser;
use common\models\hll\Bbs;
use common\models\hll\HllBbsUser;
use Yii;
use mobile\components\ApiController;
use common\models\ar\user\AccountAddress;
use common\models\ar\user\Account;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use common\models\ar\system\Area;
use yii\helpers\ArrayHelper;
use yii\filters\HttpCache;
use yii\data\ActiveDataProvider;
use common\components\WxTmplMsg;
use yii\db\Query;
use common\models\hll\UserAddressExt;
use common\models\ecs\EcsUsers;
use common\models\ar\message\Message;
use common\models\ar\message\MessageNotification;

use common\models\SpringActivity;
/**
 * BBS 论坛
 * @package api\modules\v2\controllers
 */
class ForumController extends ApiController
{

    public $second_cache = 60;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => HttpCache::className(),
                'only' => ['picInfo', 'article'],
                'lastModified' => function () { // 设置 Last-Modified 头
                    return time() + $this->second_cache;
                },
                'cacheControlHeader' => 'Cache-Control: public, max-age=' . $this->second_cache,
            ],
        ]);
    }

    /**
     * 社团简介
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionSummary()
    {

        $response = new ApiResponse();
        $data = Yii::$app->request->get();
        $account_id = Yii::$app->user->id;
        $bbsId = (new Query())->select(['id'])->from('hll_bbs')
            ->where(['loupan_id'=>$data['bbsId'],'valid'=>1,'is_main'=>1])->scalar();
        $page = $data['page'];
        if (!$bbsId) {
            $response->data = new ApiData(101, 'bbsId不能空');
            return $response;
        }

        //当前用户是否参加
        $cur = HllBbsUser::find()->select(['account_id','user_role','bbs_id'])
            ->where(['bbs_id'=>$bbsId,'valid'=>1,'status'=>[2,3],'account_id'=>$account_id])->asArray()->one();
        if($cur == null){
            $cur['user_role'] = 0;
            $cur['bbs_id'] = $bbsId;
            $cur['account_id'] = $account_id;
        }
        //根据bbsId查找社群信息
        $bbs = Bbs::getBbs($bbsId);
        if (!$bbs) {
            $response->data = new ApiData(102, '该BBS不存在');
            return $response;
        }
        //根据bbsId查找社群成员
        $sql = HllBbsUser::getUserListByBbs($bbsId);

        //对数据进行分页处理
        $info = $this->getDataPage($sql,$page);
        $info['bbs'] = $bbs;
        $info['cur'] = $cur;
        if($info['pagination']['total'] > 0){
            if($info['list']!=[]){
                foreach($info['list'] as &$item){
                    $item['islocked'] = HllBbsUser::isLocked($item['banned_time']);
                }
            }
            $response->data = new ApiData();
            $response->data->info = $info;
            return $response;
        }else{
            $response->data = new ApiData('110', '无相关数据');
            $response->data->info = $info;
            return $response;
        }
    }

    /**
     *  社区新鲜事列表
     * @param $bbsId
     * @param $page
     * @return mixed
     * @author zend.wang
     * @date  2016-09-01 13:00
     */
    public function actionList($bbsId)
    {
        $response = new ApiResponse();

        if (!$bbsId) {
            $response->data = new ApiData(101, 'bbsId不能空');
            return $response;
        }

        $bbs = Bbs::getBbs($bbsId, ['bbs_name']);
        if (!$bbs) {
            $response->data = new ApiData(102, '该BBS不存在');
            return $response;
        }

        $userId = Yii::$app->user->id;
        $cur = 3; //默认
        $user = HllBbsUser::find()->select(['user_role', 'status'])->where(['bbs_id' => $bbsId, 'account_id' => $userId, 'valid' => 1])->one();
        if($user['user_role'] == 2 && $user['status'] == 2) $cur = 2; //副版主
        if($user['user_role'] == 1 && $user['status'] == 2) $cur = 1;  //版主

        $dataProvider = Message::createCommunityEventDataProvider($bbsId);

        if ($dataProvider && $dataProvider->count > 0) {
            $list = $dataProvider->getModels();
            foreach ($list as &$event) {
                $event = Message::generateCommunityEventDatail($event['id'], $userId);
                $event['role'] = HllBbsUser::find()->select(['user_role'])->where(['bbs_id' => $bbsId, 'account_id' => $event['account_id']])->scalar();
                $event['self'] = ($userId == $event['account_id']) ? true : false;
            }
            $pagination['total'] = $dataProvider->getTotalCount();//总数
            $pagination['pageCount'] = $dataProvider->getPagination()->getPageCount();
            $response->data = new ApiData();
            $response->data->info['title'] = $bbs;
            $response->data->info['list'] = $list;
            $response->data->info['cur'] = $cur;
            $response->data->info['pagination'] = $pagination;

        } else {
            $response->data = new ApiData(1, '无相关数据');
            $response->data->info['title'] = $bbs;
            $response->data->info['cur'] = $cur;
        }

        return $response;
    }

    /**
     *  社区新鲜事详情
     * @param $msgId
     * @return mixed
     * @author meizijiu
     */
    public function actionDetail($id)
    {
        $response = new ApiResponse();
        $isPraise = false;
        if(!$id){
            $response->data = new ApiData(101,'缺少参数！');
            return $response;
        }

        $userId = Yii::$app->user->id;

        $msg = Message::generateCommunityEventDatail($id, $userId);
        if (!$msg) {
            $response->data = new ApiData(102, 'msg不存在');
            return $response;
        }

        $comment = (new Query())->select(['content', 'creater', 'created_at'])->from('message_comment')
            ->where(['message_id' => $id])->orderBy(['created_at' => SORT_DESC])->all();
        if ($comment) {
            foreach ($comment as &$item) {
                $item['created_at'] = date("m-d H:i", strtotime($item['created_at']));
                $item['accountInfo'] = EcsUsers::getUser($item['creater'],['t2.nickname','t2.headimgurl']);
            }
        }
        $isowner = false;
        if($msg['account_id'] == $userId){
            $isowner = true;
        }
        $praise = (new Query())->select(['id','creater', 'created_at'])->from('message_praise')
            ->where(['message_id' => $id])->orderBy(['created_at' => SORT_DESC])->all();
        if ($praise) {
            foreach ($praise as &$item) {
                $item['created_at'] = date("m-d H:i", strtotime($item['created_at']));
                $item['accountInfo'] = EcsUsers::getUser($item['creater'],['t2.nickname','t2.headimgurl']);
                if($item['creater'] == $userId) $isPraise = true;
            }
        }
        $cur = HllBbsUser::getUserStatus($msg['bbs_id'],['t1.user_role'],$userId);
        $to_cur = HllBbsUser::getUserStatus($msg['bbs_id'],['t1.user_role'],$msg['account_id']);

        $response->data = new ApiData();
        $response->data->info['msg'] = $msg;
        $response->data->info['comment'] = $comment;
        $response->data->info['praise'] = $praise;
        $response->data->info['isPraise'] = $isPraise;
        $response->data->info['isOwner'] = $isowner;
        $response->data->info['to_cur'] = $to_cur;
        $response->data->info['cur'] = $cur;
        return $response;
    }

    /**
     * 退出社团论坛
     */
    public function actionQuit()
    {
        $response = new ApiResponse();
        $bbsId = Yii::$app->request->get('bbsId');

        if (!$bbsId) {
            $response->data = new ApiData(101, 'bbsId不能空');
            return $response;
        }

        $bbs = Bbs::getBbs($bbsId, ['id']);
        if (!$bbs) {
            $response->data = new ApiData(102, '该BBS不存在');
            return $response;
        }


        $userId = Yii::$app->user->id;
        $result = Bbs::quitBbs($bbs['id'], $userId);


        if (!$result) {
            $response->data = new ApiData(103, '退出失败');
            return $response;
        }

        $user = Yii::$app->user->id;
        $response->data = new ApiData();
        $response->data->info = $user;

        return $response;
    }

    /**
     * 查看黑名单/设置副社长
     */
    public function actionBlockOrSet(){
        $response = new ApiResponse();
        $bbsId = Yii::$app->request->get('bbsId');
        $type = Yii::$app->request->get('type');

        if (!$bbsId) {
            $response->data = new ApiData(101, 'bbsId不能空');
            return $response;
        }

        $bbs = Bbs::getBbs($bbsId, ['id']);
        if (!$bbs) {
            $response->data = new ApiData(102, '该BBS不存在');
            return $response;
        }
        if($type == 1){
            $query = HllBbsUser::getUserListByBbs($bbsId,$field =null,$status = 4);
            $info['list'] = $query->all();
            if(!$info['list']){
                $response->data = new ApiData(1, '无相关数据！');
                return $response;
            }
            $response->data = new ApiData();
            $response->data->info = $info;
            return $response;
        }
        if($type == 2){
            $page = Yii::$app->request->get('page');
            $query = HllBbsUser::getUserListByBbs($bbsId,$field=null,$status=null,$user_role = [2,3]);
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => []
            ]);
            if ($dataProvider && $dataProvider->count > 0) {
                if ($page > $dataProvider->getPagination()->getPageCount()) {
                    $info['list'] = [];
                    $info['pagination']['total'] = $dataProvider->getTotalCount();
                    $info['pagination']['pageSize'] = $dataProvider->getPagination()->pageSize;
                    $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
                    $response->data = new ApiData();
                    $response->data->info = $info;
                    return $response;
                }
                $response->data = new ApiData();
                $model = $dataProvider->getModels();
                $info['list'] = $model;
                $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
                $info['pagination']['pageSize'] = $dataProvider->getPagination()->pageSize;
                $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
            }else {
                $response->data = new ApiData('110', '无相关数据');
                $info['list'] = [];
                $info['pagination']['total'] = 0;
                $info['pagination']['pageSize'] = 0;
                $info['pagination']['pageCount'] = 1;
            }
            $response->data->info = $info;
            return $response;
        }
    }

    /**
     * @param type 1-解禁、移除黑名单 2-设置副社长 3-废除副社长
     * 解禁社团成员、移出黑名单、取消/设置副社长
     */
    public function actionRemove()
    {
        $response = new ApiResponse();
        $bbsId = Yii::$app->request->get('bbsId');
        $account_id = Yii::$app->request->get('account_id');
        $user_role = Yii::$app->request->get('user_role');
        $type = Yii::$app->request->get('type');

        if (!$bbsId) {
            $response->data = new ApiData(101, 'bbsId不能空');
            return $response;
        }

        $bbs = Bbs::getBbs($bbsId, ['id']);
        if (!$bbs) {
            $response->data = new ApiData(102, '该BBS不存在');
            return $response;
        }

        $admin = HllBbsUser::find()->select(['user_role'])
            ->where(['bbs_id'=>$bbsId,'account_id'=>Yii::$app->user->id])->one();
        if($admin->user_role >= $user_role){
            $response->data = new ApiData(104, '您无权对其解禁！');
            return $response;
        }

        $result = Bbs::ChangeBbs($bbs['id'], $account_id, $type);

        if (!$result) {
            $response->data = new ApiData(103, '解禁失败');
            return $response;
        }
        $response->data = new ApiData();
        $response->data->info = $account_id;
        return $response;
    }

    /**
     * 解散社团
     */
    public function actionDestroy(){
        $response = new ApiResponse();
        $bbsId = Yii::$app->request->get('bbsId');

        if (!$bbsId) {
            $response->data = new ApiData(101, 'bbsId不能空');
            return $response;
        }

        $bbs = Bbs::getBbs($bbsId, ['id']);
        if (!$bbs) {
            $response->data = new ApiData(102, '该BBS不存在');
            return $response;
        }
        $result = Bbs::Destory($bbsId);

        if (!$result) {
            $response->data = new ApiData(103, '解散失败');
            return $response;
        }
        $response->data = new ApiData();
        $response->data->info = $bbsId;
        return $response;
    }

    /**
     * 加入社团论坛
     * @param $bbsId 社团ID
     * @param bool $fromUser 邀请人ID 默认false
     * @return ApiResponse
     * @author zend.wang
     * @date  2016-09-01 13:00
     */
    public function actionJoin($fromUser = false)
    {
        $response = new ApiResponse();
        $bbsId = Yii::$app->request->get('bbsId');

        if (!$bbsId) {
            $response->data = new ApiData(101, 'bbsId不能空');
            return $response;
        }

        $bbs = Bbs::getBbs($bbsId, ['id', 'join_way']);
        if (!$bbs) {
            $response->data = new ApiData(102, '该BBS不存在');
            return $response;
        }

        if (!$fromUser && $bbs['join_way'] == 3) {
            $response->data = new ApiData(103, '该BBS需邀请才能加入');
            return $response;

        }
        if ($bbs['join_way'] == 4) {
            $response->data = new ApiData(103, '该BBS不对外公开');
            return $response;
        }

        $userId = Yii::$app->user->id;
        $result = Bbs::joinBbs($bbs, $userId, $fromUser);


        if (!$result['state']) {
            $response->data = new ApiData(104, $result['msg']);
            return $response;
        }

        $user = HllBbsUser::getUserByBbsSingle($bbsId);
        $response->data = new ApiData();
        $response->data->info = $user;

        return $response;
    }

    /**
     * 创建新鲜事
     * @param $data (bbsId, loupanId, title, attachment_type)
     * @param 可选 (imgs,attachment_content_id)
     * @return ApiResponse
     * @author meizijiu
     */
    public function actionCreate()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->post('data');
        if (!$data || !isset($data['attachment_type']) || !isset($data['content']) || !isset($data['bbs_id'])) {
            $response->data = new ApiData(101, '参数缺失');
            return $response;
        }
        $userId = Yii::$app->user->id;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = new Message();
            if($model->load($data, '')) {
                $model->message_type = 4;
                $model->account_id = $userId;
                $model->publish_status = 1;
                $model->publish_time = date('Y-m-d H:i:s', time());
                if($model->validate() && $model->save()) {
                    $smsInfo = Yii::$app->cache->get('smsInfo');

                    if(!empty($smsInfo['mobiles']) && $model->attachment_type > 1) {
                        switch($model['attachment_type']) {
                            case 4:
                                $result = Yii::$app->sms->send($smsInfo['mobiles'], 'voteInform', ['name' => $smsInfo['name'],
                                                                                                    'title' => $smsInfo['title'],
                                                                                                         'bbsname' => $smsInfo['bbsName']]);
                                if ($result) {
                                    static::saveMessageNotification($smsInfo,$userId);
                                    Yii::$app->cache->delete('smsInfo');
                                    $response->data = new ApiData(0, '提交成功');
                                } else {
                                    $response->data = new ApiData(1, '发送失败');
                                }
                                break;
                            case 5:
                                $result = Yii::$app->sms->send($smsInfo['mobiles'], 'actInform', ['name' => $smsInfo['name'],
                                                                                                    'title' => $smsInfo['title'],
                                                                                                    'bbsname' => $smsInfo['bbsName']]);
                                if ($result) {
                                    static::saveMessageNotification($smsInfo,$userId);
                                    Yii::$app->cache->delete('smsInfo');
                                    $response->data = new ApiData(0, '提交成功');
                                } else {
                                    $response->data = new ApiData(1, '发送失败');
                                }
                                break;
                        }
                    }else {
                        $response->data = new ApiData(0, '提交成功');
                    }
                    //新春活动
                    $task_id = 3;
                    $spring = new SpringActivity();
                    $spring->triggerSendTemplate($userId, $task_id);

                    $transaction->commit();
                }
            }
        } catch (\yii\db\Exception $e) {
            $transaction->rollback();
            $response->data = new ApiData(102, '提交失败');
            return $response;
        }
        return $response;
    }

    /**
     * 图片详情
     * @params $id (message_id)
     */
    public function actionPicInfo()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->get();
        if (!$data || empty($data['id'])) {
            $response->data = new ApiData(1, 'id参数缺失');
            return $response;
        }

        $result = (new Query())->select(['attachment_content'])->from('message')->where(['id' => $data['id'], 'valid' => 1, 'publish_status' => [1, 2], 'attachment_type' => 1])->one();
        if (!$result) {
            $response->data = new ApiData(101, '数据不存在');
            return $response;
        }

        $val = explode(',', $result['attachment_content']);
        $response->data = new ApiData();
        $response->data->info = $val;

        return $response;
    }

    /**
     * 文章详情
     * @params $id article_id
     */
    public function actionArticle()
    {
        $response = new ApiResponse();

        $data = Yii::$app->request->get();
        if (!$data || empty($data['id'])) {
            $response->data = new ApiData(1, 'id参数缺失');
            return $response;
        }

        $result = (new Query())->select(['t1.title', 't1.content', 't1.created_at', 't2.user_name'])->from('message_article as t1')->leftJoin('ecs_admin_user as t2', 't2.user_id = t1.creater')->where(['t1.id' => $data['id'], 't1.valid' => 1])->one();
        if (!$result) {
            $response->data = new Apidata(101, '数据不存在');
            return $response;
        } else {
            $response->data = new ApiData();
            $response->data->info = $result;
        }

        return $response;
    }

    /**
     * 楼盘消息列表页
     */
    public function actionNews()
    {
        $response = new ApiResponse();
        $loupanid = Yii::$app->request->get('id');
        
        $dataProvider = Message::newsDataProvider($loupanid);

        if ($dataProvider && $dataProvider->count > 0) {
            $list = $dataProvider->getModels();
            foreach ($list as &$event) {
                $event = Message::generateLoupanEventDatail($event['id']);
            }
            $pagination['total'] = $dataProvider->getTotalCount();//总数
            $pagination['pageCount'] = $dataProvider->getPagination()->getPageCount();
            $response->data = new ApiData();
            $response->data->info['list'] = $list;
            $response->data->info['pagination'] = $pagination;

        } else {
            $response->data = new ApiData(1, '无相关数据');
        }

        return $response;
    }

    /**
     * 楼盘成长日志
     */
    public function actionLists()
    {
        $response = new ApiResponse();

        $data = (new Query())->select(['id', 'name', 'thumbnail', 'address', 'avg_price', 'tag'])->from('fang_loupan')
            ->where(['<', 'status', '4'])->orderBy(['sort' => SORT_ASC])->all();

        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['tag'] = explode(',', $v['tag']);
                $journalCount = Message::find()->where(['loupan_id' => $v['id'], 'valid' => 1, 'message_type' => 3, 'publish_status' => 2])->count();
                if (empty($journalCount)) {
                    unset($data[$k]);
                }
            }

            $response->data = new ApiData();
            $response->data->info['list'] = $data;
        } else {
            $response->data = new ApiData(111, '无相关数据');
        }

        return $response;
    }

    /**
     * 楼盘日日志详情
     */
    public function actionDetails($id)
    {
        $response = new ApiResponse();

        $now = f_date(time());
        $msgList = (new Query())->select(['t1.id', 't1.title', 't1.content', 't1.publish_time', 't1.attachment_content', 't1.attachment_type','t2.logo_pic'])
            ->from('message as t1')->leftJoin('hll_community_ext as t2','t2.loupan_id = t1.loupan_id')
            ->where(['t1.loupan_id' => $id, 't1.valid' => 1, 't1.message_type' => 3, 't1.publish_status' => 2,'t2.valid'=>1])
            ->andWhere(['<', 't1.publish_time', $now])
            ->orderBy(['t1.publish_time' => SORT_DESC]);

        //对数据进行分页处理
        $dataProvider = new ActiveDataProvider([
            'query' => $msgList,
            'pagination' => []
        ]);

        if($dataProvider && $dataProvider->count > 0) {
            $response->data = new ApiData();
            $list = $dataProvider->getModels();

            foreach ($list as $key => $value) {
                $list[$key]['publish_time'] = f_sub($value['publish_time'], 5, '');
                if ($value['attachment_type'] == 1) {
                    $list[$key]['attachment_content'] = explode(",", $value['attachment_content']);
                } else {
                    $list[$key]['attachment_content'] = "";
                }
            };

            $info['list'] = $list;
            $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
            $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
            $response->data->info=$info;
        }else {
            $response->data = new ApiData(100, '无相关数据');
        }

        return $response;
    }

    /**
     * 新鲜事点赞
     * @param $mId 新鲜事id
     * @param $type 1-点赞 2-取消点赞
     * @return ApiResponse
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function actionPraise() {
        $response = new ApiResponse();
        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');
        $cur = Yii::$app->user->id;
        $message = Message::findOne(['id'=>$id, 'valid'=>1, 'publish_status'=>[1,2]]);

        if(empty($id) || empty($type)) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $transaction = Yii::$app->db->beginTransaction();

        if($type == 1) {
            try {
                $model = new MessagePraise();
                $model->message_id = $id;
                if($model->save()) {
                    if($message) {
                        $message->praise_num += 1;
                        if($message->save()) {
                            $transaction->commit();
                            $response->data = new ApiData();
                            $response->data->info['praise'] = $model;
                            $response->data->info['num'] = $message->praise_num;
                            $response->data->info['user'] = EcsUsers::getUser($cur,['t2.nickname','t2.headimgurl']);
                        }else {
                            $response->data = new ApiData(103, '保存失败');
                        }
                    }else {
                        $response->data = new ApiData(105, '数据不存在');
                    }
                }else {
                    $response->data = new ApiData(101, '创建失败');
                }
                return $response;
            }catch (\yii\db\Exception $e) {
                $transaction->rollback();
                $response->data = new ApiData(102, '提交失败');
                return $response;
            }

        }else if($type == 2) {
            try {
                $query = MessagePraise::findOne(['message_id'=>$id, 'creater'=>$cur]);
                $p_id = $query->id;
                if($query->delete()) {
                    $message->praise_num -= 1;
                    if($message->save()) {
                        $transaction->commit();
                        $response->data = new ApiData();
                        $response->data->info['id'] = $p_id;
                        $response->data->info['num'] = $message->praise_num;
                    }else {
                        $response->data = new ApiData(103, '保存失败');
                    }
                }
                return $response;
            }catch (\yii\db\Exception $e) {
                $transaction->rollback();
                $response->data = new ApiData(102, '提交失败');
                return $response;
            }
        }
    }

    /**
     * 新鲜事评论
     * @return ApiResponse
     */
    public function actionComment() {
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        $data['message_id'] = Yii::$app->request->post('id');
        $data['content'] = Yii::$app->request->post('content');

        if(!$data || empty($data['message_id'] || empty($data['content']))) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $model = new MessageComment();
        if($model->load($data, '')) {
            if($model->validate() && $model->save()) {
                $message = Message::findOne(['id'=>$data['message_id'], 'valid'=>1, 'publish_status'=>[1,2]]);
                $message->comment_num += 1;
                if($message->save()) {
                    $response->data = new ApiData();
                    $cur = EcsUsers::getUser($userId,['t2.nickname','t2.headimgurl']);
                    $response->data->info['user'] = $cur;
                    $response->data->info['comment'] = $model;
                }
            }else {
                $response->data = new ApiData(101, '创建失败');
            }
        }else {
            $response->data = new ApiData(102, '数据错误');
        }
        return $response;
    }

    /**
     * 删帖
     * @param $mId
     * @return ApiResponse
     */
    public function actionDeletePost($mId) {
        $response = new ApiResponse();

        if(empty($mId)) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $query = Message::findOne(['id'=>$mId, 'publish_status'=>[1,2], 'valid'=>1]);
        if($query) {
            $query->valid = 0;
            if($query->save()) {
                $response->data = new ApiData();
            }else {
                $response->data = new ApiData(102, '删除失败');
            }
        }else {
            $response->data = new ApiData(101, '数据错误');
        }

        return $response;
    }

    /**
     * 禁言
     * @return ApiResponse
     */
    public function actionSilence() {
        $response = new ApiResponse();

        $data = Yii::$app->request->post('data');
        $cur = Yii::$app->user->id;
        $time = time();

        if(!$data || empty($data['m_id']) || empty($data['banned_time'])) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $model = Message::find()->select(['bbs_id', 'account_id'])->where(['id'=>$data['m_id'], 'valid'=>1])->one();
        if($model) {
            $data['account_id'] = $model->account_id;
            $data['bbs_id'] = $model->bbs_id;
        }else {
            $response->data = new ApiData(103, '数据错误');
            return $response;
        }

        $result = HllBbsUser::findOne(['bbs_id'=>$data['bbs_id'], 'account_id'=>$data['account_id'], 'status'=>3, 'valid'=>1]);
        if($result) {
            $enough = strtotime($result->banned_time);
            if($enough > $time) {
                $response->data = new ApiData(104, '已被禁言');
                return $response;
            }
        }

        $data['banned_time'] = date('Y-m-d H:i:s', $time + 3600*24*intval($data['banned_time']));
        $query = HllBbsUser::findOne(['bbs_id'=>$data['bbs_id'], 'account_id'=>$data['account_id'],'valid'=>1]);
        if($query) {
            if($query->status == 4) {
                $response->data = new ApiData(105, '该用户已被拉黑');
            }else {
                $query->status = 3;
                $query->banned_time = $data['banned_time'];
                $query->ban_admin_id = $cur;
                if($query->save()) {
                    $response->data = new ApiData();
                    $response->data->info = $query->banned_time;
                }else {
                    $response->data = new ApiData(102, '禁言失败');
                }
            }
        }else {
            $response->data = new ApiData(101, '数据错误');
        }

        return $response;
    }

    /**
     * 拉黑
     * @return ApiResponse
     */
    public function actionBlock() {
        $response = new ApiResponse();

        $m_id = Yii::$app->request->post('m_id');
        $data = array();

        if(!$m_id) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $model = Message::find()->select(['bbs_id', 'account_id'])->where(['id'=>$m_id, 'valid'=>1])->one();
        if($model) {
            $data['account_id'] = $model->account_id;
            $data['bbs_id'] = $model->bbs_id;
        }else {
            $response->data = new ApiData(103, '数据错误');
            return $response;
        }

        $result = HllBbsUser::findOne(['bbs_id'=>$data['bbs_id'], 'account_id'=>$data['account_id'], 'status'=>4, 'valid'=>1]);
        if($result) {
            $response->data = new ApiData(104, '已被拉黑');
            return $response;
        }

        $query = HllBbsUser::findOne(['bbs_id'=>$data['bbs_id'], 'account_id'=>$data['account_id'], 'status'=>[2,3],'valid'=>1]);
        if($query) {
            $query->status = 4;
            if($query->save()) {
                $response->data = new ApiData();
            }else {
                $response->data = new ApiData(102, '拉黑失败');
            }
        }else {
            $response->data = new ApiData(101, '数据错误');
        }

        return $response;
    }

    /**
     * 用户状态
     * @param $bbsId
     * @return ApiResponse
     */
    public function actionStatus($bbsId) {
        $response = new ApiResponse();
        $cur = Yii::$app->user->id;
        $time = time();

        if(!$bbsId || empty($bbsId)) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $query = HllBbsUser::getUserStatus($bbsId, ['t1.status', 't1.banned_time']);
        if($query) {
            switch($query['status']) {
                case 1: $response->data = new ApiData(102, '身份待审核');
                        break;
                case 2: $response->data = new ApiData();
                        break;
                case 3: if(strtotime($query['banned_time']) <= time()) {
                            $response->data = new ApiData();
                        }else {
                            $time = date('Y-m-d H:i:s',time());
                            $date1=date_create($time);
                            $date2=date_create($query['banned_time']);
                            $diff = date_diff($date1,$date2);
                            $day = $diff->days;
                            if($diff->h){
                                $day++;
                            }
                            $response->data = new ApiData(103, '被禁言');
                            $response->data->info = $day;
                        }
                        break;
                case 4: $response->data = new ApiData(104, '被拉黑');
                        break;
            }
        }else {
            $response->data = new ApiData(101, '数据错误');
        }

        return $response;
    }

    /** 创建时的附件
     * @param $type 2--活动 1--投票
     * @param $id (投票/活动)id
     * @return ApiResponse
     */
    public function actionCreateAttach($type, $id) {
        $response = new ApiResponse();

        if(!$type || !$id) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        if($type == 1) {
            $query = MessageVote::getInfo($id,['id', 'title', 'deadline', 'thumbnail']);
            if(!$query) {
                $response->data = new ApiData(101, '数据错误');
            }else {
                $response->data = new ApiData();
                $response->data->info = $query;
            }
        }else if($type == 2) {
            $query = FangCommunityActivity::getInfo($id);
            if(!$query) {
                $response->data = new ApiData(101, '数据错误');
            }else {
                $response->data = new ApiData();
                $response->data->info = $query;
            }
        }else {
            $response->data = new ApiData(100, '参数错误');
        }
        
        return $response;
    }

    /**
     * 社团用户列表(用户简介)
     * @param $bbsId
     * @param bool $date
     * @return ApiResponse
     */
    public function actionUserList($bbsId, $date=false) {
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        $beginDate= date('Y-m-01', strtotime(date("Y-m-d")));
        $endDate = date('Y-m-d', strtotime("$beginDate +1 month -1 day"));
        $quota = array('1'=>50, '2'=>30, '3'=>10);

        if(!$bbsId) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        if($date) {
            $time = time();
        }else {
            $time = null;
        }

        $role = HllBbsUser::find()->select(['user_role'])->where(['bbs_id'=>$bbsId, 'account_id'=>$userId, 'status'=>2, 'valid'=>1])->scalar();
        $remainder = MessageNotification::find()->where(['account_id'=>$userId, 'send_way'=>1, 'send_result'=>2, 'valid'=>1])
                                                ->andWhere(['between', 'send_time', $beginDate, $endDate])->count();
        $remainder = $quota[$role] - $remainder;

        $field = ['t1.account_id','t1.user_role','t2.nickname', 't2.headimgurl'];
        $query = (new Query())->select($field)
            ->from("hll_bbs_user as t1")
            ->leftJoin("ecs_wechat_user as t2", 't2.ect_uid = t1.account_id')
            ->leftJoin('ecs_users as t3','t3.user_id = t1.account_id')
            ->where(['t1.bbs_id' => $bbsId, 't1.valid' => 1,'t1.status'=>[2,3]])
            ->andWhere(['<>','t3.mobile_phone',' '])
            ->orderBy(['t1.user_role'=>SORT_ASC,'t1.account_id'=>SORT_DESC]);
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10
            ]
        ]);

        if ($dataProvider && $dataProvider->count > 0) {
            $model = $dataProvider->getModels();
            $response->data = new ApiData();
            $info['list'] = $model;
            $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
            $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
            $info['date'] = $time;
            $info['remainder'] = $remainder;
            $response->data->info = $info;
        }else {
            $response->data = new ApiData('110', '无相关数据');
            $response->data->info['date'] = $time;
        }

        return $response;
    }

    /**
     * 创建活动
     * @return ApiResponse
     */
    public function actionCreateAct() {
        $response = new ApiResponse();
        $key = "smsUsers";

        $data = Yii::$app->request->post('data');
        $cur = Yii::$app->user->id;

        if(!$data || empty($data['address']) || empty($data['begin']) || empty($data['signup_end']) || empty($data['name'])) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $model = new FangCommunityActivity();
        if($model->load($data, '')) {
            if($model->validate() && $model->save()) {
                $response->data = new ApiData();
                $response->data->info = $model->id;
                if(!empty($data['sms'])) {
                    $smsInfo['mobiles'] = EcsUsers::find()->select(['mobile_phone'])->where(['user_id'=>$data['sms']])->andWhere(['<>', 'mobile_phone', ''])->asArray()->all();
                    $smsInfo['mobiles'] = array_column($smsInfo['mobiles'], 'mobile_phone');
                    $smsInfo['name'] = EcsWechatUser::find()->select(['nickname'])->where(['ect_uid'=>$cur])->scalar();
                    $smsInfo['title'] = $data['name'];
                    $smsInfo['bbsName'] = Bbs::find()->select(['bbs_name'])->where(['id'=>$model->loupan_id])->scalar();
                    Yii::$app->cache->set('smsInfo', $smsInfo);
                }
            }else {
                Yii::$app->cache->delete('smsInfo');
                $response->data = new ApiData(103, '创建失败');
            }
        }else {
            $response->data = new ApiData(101, '数据错误');
        }

        return $response;
    }

    /**
     * 创建投票
     */
    public function actionCreateVote(){
        $response = new ApiResponse();
        $data = Yii::$app->request->post('data');
        $bbsId = Yii::$app->request->post('bbsId');
        $account_id = Yii::$app->request->post('account_id');
        $cur = Yii::$app->user->id;
        $size = sizeof($data);
        $vote = $data[$size-1];
        if(empty($vote['title']) || empty($vote['deadline'])){
            $response->data = new ApiData(1,'参数异常');
            return $response;
        }

        try{
            $model = new MessageVote();
            if($model->load($vote,'') && $model->save()){
                array_pop($data);
                $result = MessageVoteQuestion::saveVoteQuestion($data,$model->id);
                if($result){
                    if($account_id){
                        $smsInfo['mobiles'] = EcsUsers::find()->select(['mobile_phone'])->where(['user_id'=>$account_id])->andWhere(['<>', 'mobile_phone', ''])->asArray()->all();
                        $smsInfo['mobiles'] = array_column($smsInfo['mobiles'], 'mobile_phone');
                        $smsInfo['name'] = EcsWechatUser::find()->select(['nickname'])->where(['ect_uid'=>$cur])->scalar();
                        $smsInfo['title'] = $model->title;
                        $smsInfo['bbsName'] = Bbs::find()->select(['bbs_name'])->where(['id'=>$bbsId])->scalar();
                        Yii::$app->cache->set('smsInfo', $smsInfo);
                    }

                    $info['id'] = $model->id;
                    $response->data = new ApiData();
                    $response->data->info = $info;
                }else{
                    $response->data = new ApiData(3,'投票问题保存异常！');
                }
            }else{
                $response->data = new ApiData(2,'投票保存异常！');
            }
        }catch (\Exception $e){
            var_dump($e->getMessage());
            $response->data = new ApiData(5,'保存异常！');
        }
        return $response;
    }

    /**
     * 保存发送短信记录
     * @param $data
     * @param $userId
     * @throws \yii\db\Exception
     */
    public static function saveMessageNotification($data, $userId) {
        $content = $data['name'].'在'.$data['bbsName'].'小区发起了"'.$data['title'].'",特邀您参与!';
        foreach ($data['mobiles'] as $key => $item) {
            $arr[$key]['account_id'] = $userId;
            $arr[$key]['content'] = $content;
            $arr[$key]['send_way'] = 1;
            $arr[$key]['to_url'] = $item;
            $arr[$key]['send_time'] = date('Y-m-d H:i:s', time());
            $arr[$key]['send_result'] = 2;
        }

        Yii::$app->db->createCommand()->batchInsert('message_notification', ['account_id','content','send_way','to_url','send_time','send_result'], $arr)->execute();
    }

    /**
     * 活动详情
     * @param $id
     * @param null $fields
     * @return ApiResponse
     */
    public function actionActDetail($id, $fields=null) {
        $response = new ApiResponse();

        if(empty($id)) {
            $response->data = new ApiData(100,'参数错误');
            return $response;
        }

        $userId = Yii::$app->user->id;
        if(!$fields) {
            $fields = ['loupan_id','thumbnail','name','signup_end','begin','address','person_num','fee','content','pics'];
        }

        $val = FangCommunityActivity::find()->select($fields)->where(['id'=>$id, 'valid'=>1])->asArray()->one();
        if($val) {
            $signup_end = strtotime($val['signup_end']);
            $time = time();
            if($signup_end > $time){
                $val['isactive'] = true;
            }else{
                $val['isactive'] = false;
            }
            $val['has_join'] = (bool)FangCommunityActivityAccount::findOne(['activity_id'=>$id,'valid'=>1, 'account_id'=>$userId]);
            $val['pics'] = explode(',', $val['pics']);
            if($val['thumbnail'] == '' || empty($val['thumbnail'])) {
                $val['thumbnail'] = Yii::$app->params['defaultActImg'];
            }
            if($val['person_num'] != '无限制') {
                $val['rest'] = FangCommunityActivityAccount::find()->where(['activity_id'=>$id,'valid'=>1])->count();
                $val['rest'] = $val['person_num'] - intval($val['rest']);
            }else {
                $val['rest'] = '∞';
            }

            $list = (new Query())->select(['t1.account_id','t1.created_at','t2.nickname','t2.headimgurl','t3.user_role'])
                ->from(['fang_community_activity_account as t1'])
                ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.account_id')
                ->leftJoin('hll_bbs_user as t3','t3.account_id = t1.account_id')
                ->where(['t1.activity_id'=>$id,'t1.valid'=>1,'t3.bbs_id'=>$val['loupan_id']])->all();
            $response->data = new ApiData();
            $response->data->info['val'] = $val;
            $response->data->info['list'] = $list;
        }else {
            $response->data = new ApiData(101,'数据错误');
        }

        return $response;
    }

    /**
     * 报名/取消报名活动
     * @param $id
     * @param $type
     * @return ApiResponse
     */
    public function actionActEnroll($id, $type) {
        $response = new ApiResponse();

        if(empty($id) || empty($type)) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $userId = Yii::$app->user->id;

        //报名
        if($type == 1) {
            $count = FangCommunityActivityAccount::find()->where(['activity_id'=>$id, 'valid'=>1])->count();
            $val = FangCommunityActivity::find()->select(['person_num','loupan_id'])->where(['id'=>$id,'valid'=>1])->one();
            if($val->person_num != '无限制') {
                if($count == intval($val->person_num)) {
                    $response->data = new ApiData(104,'报名人数已满');
                    return $response;
                }
            }
            $model = FangCommunityActivityAccount::find()->where(['activity_id'=>$id,'account_id'=>$userId, 'valid'=>0])->one();
            if($model){
                $model->valid = 1;
            }else{
                $model = new FangCommunityActivityAccount();
                $model->activity_id = $id;
                $model->account_id = $userId;
            }
            if($model->save()) {
                $account = (new Query())->select(['t1.user_role','t1.account_id','t2.nickname','t2.headimgurl'])
                    ->from('hll_bbs_user as t1')->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.account_id')
                    ->where(['t1.bbs_id'=>$val->loupan_id,'t1.account_id'=>$userId,'t1.valid'=>1])->one();
                $response->data = new ApiData();
                $response->data->info = $account;
            }else {
                $response->data = new ApiData(102, '报名失败');
            }
        }else if($type == 2) {
        //取消报名
            $val = FangCommunityActivityAccount::findOne(['activity_id'=>$id, 'account_id'=>$userId, 'valid'=>1]);
            if($val) {
                $val->valid = 0;
                if($val->save()) {
                    $response->data = new ApiData();
                    $response->data->info = $userId;
                }else {
                    $response->data = new ApiData(103, '取消报名失败');
                }
            }else {
                $response->data = new ApiData(101, '数据错误');
            }
        }

        return $response;
    }

}
