<?php
namespace mobile\modules\v2\controllers;

use common\models\ar\user\Account;
use common\models\ecs\EcsWechatUser;
use common\models\hll\ItemSharingThanks;
use common\models\hll\RideSharing;
use Yii;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use common\models\ecs\EcsUsers;
use common\models\ecs\EcsAccountLog;
use common\models\hll\ItemSharing;
use common\models\hll\ItemSharingPraise;
use common\models\hll\ItemSharingComment;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use common\components\WxTmplMsg;
use common\models\hll\HllUserPoints;
/**
 * 借用控制器
 * Created by PhpStorm.
 * User: kaikai.qin
 * Date: 2016/10/9
 * Time: 9:11
 */
class BorrowingController extends ApiController
{
    /**
     * 借用列表
     * @method get
     * @param  $id int 小区编码
     * @param  $type int 分类编号
     * @return ApiResponse
     * @author kaikai.qin
     * Date: 2016/10/9
     */
    public function actionIndex()
    {
        $response = new ApiResponse();

        $id = Yii::$app->request->get('id');
        $type = Yii::$app->request->get('type');
        $page = Yii::$app->request->get('page');
        $userId = Yii::$app->user->id;

        $sql = (new Query())->select(['t1.id', 't1.created_at', 't1.borrow_item_type', 't1.item_desc', 't1.item_pics',
            't1.item_pics', "(t1.thanks_num*0.01) as thanks_num", 't1.praise_num', 't1.comment_num', 't2.nickname', 't2.headimgurl'])
            ->from('hll_item_sharing as t1')
            ->leftJoin('ecs_wechat_user as t2', 't2.ect_uid = t1.account_id')
            ->where(['t1.valid' => 1, 't1.share_type' => 1, 't1.status' => 2, 't1.community_id' => $id]);
        //分类选择
        if ($type) {
            $type = trim($type);
            $sql->andWhere(['t1.borrow_item_type' => $type])->orderBy(['t1.created_at' => SORT_DESC]);
        } else {
            $sql->orderBy(['t1.created_at' => SORT_DESC]);
        }
        //对数据进行分页处理
        $info = $this->getDataPage($sql,$page);
        if($info['pagination']['total'] > 0){
            if($info['list']!=[]){
                foreach ($info['list'] as &$item) {
                    $item['borrow_item_type'] = ItemSharing::getBorrowType($item['borrow_item_type']);
                    if ($item['item_pics'] != null && $item['item_pics'] != '') {
                        $item['item_pics'] = explode(',', $item['item_pics']);
                    }
                    $item['isPraise'] = (bool)itemSharingPraise::find()->where(['is_id' => $item['id'], 'creater' => $userId])->count();
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
     * 借用信息详情
     * @method get
     * @param  $id int 借用信息编号
     * @return ApiResponse
     * @author kaikai.qin
     * Date: 2016/10/9
     */
    public function actionDetail()
    {
        $response = new ApiResponse();
        $id = Yii::$app->request->get('id');
        $userId = Yii::$app->user->id;

        $sql = (new Query())->select(['t1.sell_item_price', 't1.created_at', 't1.borrow_item_type', 't1.item_desc', 't1.item_pics', 't2.nickname', 't2.headimgurl', 't2.ect_uid'])
            ->from('hll_item_sharing as t1')
            ->leftJoin('ecs_wechat_user as t2', 't2.ect_uid = t1.account_id')
            ->where(['t1.id' => $id])->one();
        $sql['type'] = $sql['borrow_item_type'];
        $sql['borrow_item_type'] = ItemSharing::getBorrowType($sql['borrow_item_type']);
        if ($sql['item_pics'] != null) {
            $sql['item_pics'] = explode(',', $sql['item_pics']);
        }
        $sql['isPraise'] = (bool)ItemSharingPraise::find()->where(['is_id' => $id, 'creater' => $userId])->count();
        $info['desc'] = $sql;
        //获取感激列表
        $info['thanks'] = ItemSharingThanks::getThanksBySharingId($id);
        //获取点赞列表
        $info['praise'] = ItemSharingPraise::getPraiseBySharingId($id);

        //获取留言列表
        $info['comment'] = ItemSharingComment::getCommentBySharingId($id);

        //本人是否已经点过赞
        $result = ItemSharingPraise::find()->where(['is_id' => $id, 'creater' => Yii::$app->user->id])->count();
        if ($result) {
            $info['desc']['isPraise'] = true;
        } else {
            $info['desc']['isPraise'] = false;
        }
        $info['desc']['isOwner'] = ($userId == $sql['ect_uid']) ? true : false;

        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 发布借用信息
     * @method post
     * @param borrow_item_type int 物品类型
     * @param community_id int 可借小区
     * @param account_id int 用户
     * @param item_desc string 物品描述
     * @param  item_pics string 上传图片
     * @return ApiResponse
     * @author kaikai.qin
     * Date: 2016/10/9
     */
    public function actionCreate()
    {
        $response = new ApiResponse();
        $account_id = Yii::$app->user->id;
        $data = Yii::$app->request->post('data');
        $data['account_id'] = $account_id;
        $data['share_type'] = 1;
        $model = new ItemSharing();
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
     * 感谢物主,给物主送积分
     * @param $id int 借用信息编号
     * @return ApiResponse
     * @author kaikai.qin
     * Date: 2016/10/9
     */
    public function actionThanks()
    {

        $response = new ApiResponse();
        $account_id = Yii::$app->user->id;
        $data = Yii::$app->request->post('data');
        $itemSharing = ItemSharing::findOne($data['id']);
        if (!$itemSharing) {
            $response->data = new ApiData(112, '无相关数据');
            return $response;
        }
        $community_id = [$itemSharing->community_id,0];
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
                $user_id = ItemSharing::find()
                    ->where(['id' => $data['id'], 'valid' => 1])
                    ->select(['account_id'])->scalar();
                $result = EcsAccountLog::log_account_change($community_id,$account_id, $user_id, 0, 0, 0, $data['thanks_point']);
                if ($result) {
                    //保存此次感谢语句与感谢积分
                    $model = new ItemSharingThanks();
                    $model->content = $data['thanks_word'];
                    $model->thanks_point = $data['thanks_point'];
                    $model->is_id = $data['id'];
                    //感谢数目增加
                    $itemSharing->thanks_num += $data['thanks_point'];
                    if ($model->save()) {
                        $itemSharing->save();
                        $trans->commit();
                        $response->data = new Apidata(0, '保存成功');
                        WxTmplMsg::thanksAccountNotice($model->id, $user_id, 2); //userPoints 用户当前友元总额(分)
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
     * 留言
     * @param $id int 物品编号
     * @return ApiResponse
     * @author kaikai.qin
     * Date: 2016/10/9
     */
    public function actionComment()
    {
        $response = new ApiResponse();
        $id = Yii::$app->request->post('id');
        $content = Yii::$app->request->post('content');
        $userId = Yii::$app->user->id;
        $trans = Yii::$app->db->beginTransaction();
        try {
            //添加留言记录
            $model = new ItemSharingComment();
            $model->is_id = $id;
            $model->content = $content;
            if ($model->save()) {
                //留言数加1
                $itemSharing = ItemSharing::findOne($id);
                $itemSharing->comment_num += 1;
                if ($itemSharing->save()) {
                    $trans->commit();
                    $response->data = new Apidata(0, '操作成功！');
                    $back['user'] = EcsUsers::getUser($userId, ['t2.nickname', 't2.headimgurl']);
                    $back['comment_time'] = date('m-d H:i', strtotime($model->created_at));
                    $back['comment_content'] = $model->content;
                    $response->data->info = $back;
                } else {
                    $response->data = new Apidata(111, '保存失败！');
                }
            } else {
                $response->data = new Apidata(113, '保存失败！');
            }
        } catch (\yii\db\Exception $e) {
            $trans->rollBack();
            $response->data = new Apidata(110, '操作失败！');
        }
        return $response;
    }

    /**
     * 点赞
     * @param $id int 物品编号
     * @return ApiResponse
     * @author kaikai.qin
     * Date: 2016/10/9
     */
    public function actionPraise()
    {
        $response = new ApiResponse();
        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');  //1-点赞 2-取消点赞
        $userId = Yii::$app->user->id;

        $trans = Yii::$app->db->beginTransaction();
        try {
            if ($type == 1) {
                //添加点赞记录
                $model = new ItemSharingPraise();
                $model->is_id = $id;
                //$model->creater = $userId;
                if ($model->save()) {
                    //点赞加1
                    $itemSharing = ItemSharing::findOne($id);
                    $itemSharing->praise_num += 1;
                    if ($itemSharing->save()) {
                        $trans->commit();
                        $response->data = new Apidata(0, '操作成功！');
                        $back['user'] = EcsUsers::getUser($userId, ['t2.nickname', 't2.headimgurl']);
                        $back['praise_time'] = date('m-d H:i', strtotime($model->created_at));
                        $back['praise_id'] = $model->id;
                        $response->data->info = $back;
                    } else {
                        $response->data = new Apidata(111, '保存失败！');
                    }
                } else {
                    $response->data = new Apidata(113, '保存失败！');
                }
            } else if ($type == 2) {
                $query = ItemSharingPraise::find()->where(['is_id' => $id, 'creater' => $userId])->one();
                if (!$query) {
                    $response->data = new ApiData(115, '数据不存在');
                } else {
                    $p_id = $query->id;
                    if ($query->delete()) {
                        $itemSharing = ItemSharing::findOne($id);
                        $itemSharing->praise_num -= 1;
                        if ($itemSharing->save()) {
                            $trans->commit();
                            $response->data = new Apidata(0, '操作成功！');
                            $response->data->info = $p_id;
                        } else {
                            $response->data = new Apidata(111, '保存失败！');
                        }
                    } else {
                        $response->data = new ApiData(116, '删除失败');
                    }
                }
            } else {
                $response->data = new ApiData(114, '参数错误');
            }
        } catch (\yii\db\Exception $e) {
            $trans->rollBack();
            $response->data = new Apidata(110, '操作失败！');
        }
        return $response;
    }

    /**
     * 私聊
     * @param $id int 共享物品Id
     * @return ApiResponse
     */
    public function actionTalk()
    {
        $response = new ApiResponse();
        $id = Yii::$app->request->get('id');
        $type = Yii::$app->request->get('type');
        $userId = ItemSharing::find()->select(['account_id'])->where(['id' => $id])->scalar();
        $response->data = new ApiData(0);
        $response->data->info = $userId;

        WxTmplMsg::userAdvisoryNotification($id, $type);
        return $response;
    }

    /**
     * 感谢用户详情
     */
    public function actionThankInfo() {
        $response = new ApiResponse();

        $id = Yii::$app->request->get('id');

        if(!$id) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $data = ItemSharing::findOne(['id'=>$id, 'status'=>2, 'valid'=>1]);
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

}