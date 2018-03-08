<?php
namespace mobile\modules\v2\controllers;

use common\models\ar\admin\Admin;
use common\models\ar\fang\FangHouse;
use common\models\ar\system\QrCode;
use common\models\ar\user\AccountAddress;
use common\models\ar\user\Account;
use common\models\ar\user\AccountFriend;
use common\models\hll\Community;
use common\models\hll\HllBbsUser;
use common\models\hll\HllCommunityPubInfo;
use common\models\hll\HllCommunityPubInfoFeedback;
use Yii;
use mobile\components\ApiController;
use yii\base\Exception;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use mobile\modules\v2\models\User;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\filters\HttpCache;

use common\models\ecs\EcsUsers;
use common\models\hll\Bbs;
use common\models\hll\UserAddress;
/**
 * 社区操作接口
 *
 * Class CommunityController
 * @package api\modules\v1\controllers
 */
class CommunityController extends ApiController
{
    /**
     *
     * @SWG\Get(path="/community/contacts",
     *     tags={"community"},
     *     summary="社区通讯录",
     *     description="社区通讯录",
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
    public function actionContacts($loupanId, $keywords = '')
    {
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;

        //公共信息
        $info['pub_info'] = HllCommunityPubInfo::getCommunityInfo($loupanId,1);
        //小区名
        $name = Bbs::find()->select('bbs_name')->where(['loupan_id'=>$loupanId, 'is_main'=>1, 'valid'=>1])->scalar();

        //社团列表
        $forumList = Bbs::bbsList($loupanId);
        foreach($forumList as &$item) {
            $item['state'] = Bbs::followState(Yii::$app->user->id, $item['id']);
        }
        $info['forum'] = $forumList;

        //显示本人房产
        $userAddress = UserAddress::getHouseByUserId($userId,$loupanId);

        $info['user'] = $userAddress;
        //根据楼盘好查找本小区里的所有住户
        $query = UserAddress::getUserByLoupanId($userId,$loupanId,$keywords);
        //每页显示10数据
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => []
        ]);
        //对数据进行分页处理
        if ($dataProvider && $dataProvider->count > 0) {
            $response->data = new ApiData();
            $list = $dataProvider->getModels();
            //循环处理每条记录的关注状态
            foreach ($list as &$item) {
                $result = AccountFriend::followStatus($userId, $item['ect_uid']);
                $item['state'] = $result;
                $item['classify'] = $item['group_name'].$item['building_num'];
            }
            $info['list'] = $list;
            $info['name'] = $name;
            $info['pagination']['total'] = $dataProvider->getTotalCount();//总数
            $info['pagination']['pageSize'] = $dataProvider->getPagination()->pageSize;
            $info['pagination']['pageCount'] = $dataProvider->getPagination()->getPageCount();
        }else {
            $info['list'] = [];
            $info['name'] = $name;
            $info['pagination']['total'] = 0;
            $info['pagination']['pageSize'] = 0;
            $info['pagination']['pageCount'] = 1;
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 常用电话
     * @param $communityId -> community_id
     * @return ApiResponse
     */
    public function actionPhoneCommunity($communityId){
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        //公共信息
        $info['pub_info'] = HllCommunityPubInfo::getCommunityInfo($communityId,1);
        //小区名
        $name = Bbs::find()->select('bbs_name')->where(['loupan_id'=>$communityId, 'is_main'=>1, 'valid'=>1])->scalar();

        $info['name'] = $name;
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 社群列表
     * @param $id -> communtiy_id
     * @return ApiResponse
     */
    public function actionBbsList($id){
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        //社团列表
        $forumList = Bbs::bbsList($id);
        foreach($forumList as &$item) {
            $status= Bbs::followState($userId, $item['id']);
            $item = array_merge($item,$status);
        }
        $info['forum'] = $forumList;
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 添加公共社区信息
     * @param $id
     * @return ApiResponse
     */
    public function actionCreateCommunityInfo(){
        $response = new ApiResponse();

        if(Yii::$app->request->Post('data')){
            $data = Yii::$app->request->Post('data');
            if($data['type_id'] == 6){
                $data['name'] = $data['other'];
            }
            $pub_info = new HllCommunityPubInfo();
            if($pub_info->load($data,'') && $pub_info->save()){
                $response->data = new ApiData();
            }else{
                $response->data = new ApiData(101,$pub_info->getErrors());
            }
        }else{
            $info_type = HllCommunityPubInfo::$info_type;
            $info['id'] = array_keys($info_type);
            $info['name'] = array_values($info_type);
            $response->data = new ApiData();
            $response->data->info = $info;
        }
        return $response;
    }

    /**
     * 社区信息报错
     * @return ApiResponse
     */
    public function actionCommunityInfoFeedback(){
        $response = new ApiResponse();

        if(Yii::$app->request->Post('data')){
            $data = Yii::$app->request->Post('data');
            $pub_info = HllCommunityPubInfo::findOne(['id'=>$data['cpi_id'],'status'=>1,'valid'=>1]);
            if(!$pub_info){
                $response->data = new ApiData(100,'数据错误');
                return $response;
            }
            $trans = Yii::$app->db->beginTransaction();
            try{
                $data['feedback_time'] = date("Y-m-d H:i:d");
                $pub_feedback = new HllCommunityPubInfoFeedback();
                if($pub_feedback->load($data,'')){
                    if($pub_feedback->save(false)){
                        $pub_info->status = 2;
                        if($pub_info->save()){
                            $trans->commit();
                            $response->data = new ApiData();
                        }else{
                            throw new Exception('错误上报失败',102);
                        }
                    }else{
                        throw new Exception('修改信息状态失败',103);
                    }
                }else{
                    throw new Exception('数据加载失败',101);
                }
            }catch (Exception $e){
                $trans->rollBack();
                $response->data = new ApiData($e->getCode(),$e->getName());
            }
        }else{
            $id = f_get('id',0);
            $info['info'] = HllCommunityPubInfo::getCommunityInfo($id,2);
            $info['feedback'] = HllCommunityPubInfo::getCommunityFeedback();
            $response->data = new ApiData();
            $response->data->info = $info;
        }
        return $response;
    }

    /**
     * 新建bbs
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionCreateCommunityBbs(){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        if(f_post('data')){
            $data = f_post('data');
            $Community =Community::findOne(['id'=>$data['community_id'],'valid'=>1]);
            if(!$Community){
                $response->data = new ApiData('无此小区',101);
                return $response;
            }
            $trans = Yii::$app->db->beginTransaction();
            try{
                $Bbs = new Bbs();
                if(empty($data['thumbnail'])){
                    $data['thumbnail'] = 'circle-default-01.png';
                }
                $data['bbs_name'] = $Community->name . '-' . $data['name'];
                $data['loupan_id'] = $data['community_id'];
                $data['is_main'] = 0;
                $data['allow_del'] = 1;
                $data['user_num'] = 1;
                if($Bbs->load($data,'') && $Bbs->save()){
                    $bbs_user = new HllBbsUser();
                    $bbs_user->bbs_id = $Bbs->id;
                    $bbs_user->account_id = $user_id;
                    $bbs_user->user_role = 1;
                    $bbs_user->status = 2;
                    if($bbs_user->save()){
                        $trans->commit();
                        $response->data = new ApiData();
                    }else{
                        throw new Exception($bbs_user->getErrors(),102);
                    }
                }else{
                    throw new Exception($Bbs->getErrors(),103);
                }
            }catch (Exception $e){
                $trans->rollBack();
                $response->data = new ApiData($e->getCode(),$e->getName());
            }
        }else{
            $join_type = Bbs::$join_type;
            $response->data = new ApiData();
            $response->data->info = $join_type;
        }
        return $response;
    }

    /**
     * 编辑详情
     * @param $id
     * @return ApiResponse
     */
    public function actionBbsDetail($id){
        $response = new ApiResponse();

        //社团列表
        $info = (new Query())->select(['id','bbs_name', 'thumbnail', 'announcement',
            'join_way', 'user_num','link_qq','qq_qrcode','link_weixin','weixin_group_master'])
            ->from('hll_bbs')->where(['id'=>$id, 'valid'=>1])->one();
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 社团编辑
     * @return ApiResponse
     */
    public function actionBbsUpdate(){
        $response = new ApiResponse();
        $data = f_post('data');
        $bbs = Bbs::findOne($data['id']);
        if($bbs->load($data,'') && $bbs->save()) {
            $response->data = new ApiData();
        }else{
            $response->data = new ApiData(101,'修改失败');
        }
        return $response;
    }

    /**
     * 解散社团
     * @param $id -> bbsId
     * @return ApiResponse
     */
    public function actionDeleteBbs($id){
        $response = new ApiResponse();
        $bbs = Bbs::findOne($id);
        try{
            if(!$bbs){
                throw new Exception('数据错误',102);
            }
            $user_status = Bbs::followState(Yii::$app->user->id,$id);
            if($user_status['admin_level'] != 1){
                throw new Exception('您无权解散社团',103);
            }
            if($bbs->allow_del == 0){
                throw new Exception('该社团不能解散',101);
            }
            $bbs->valid = 0;
            $bbs->save();
            $response->data = new ApiData();
        }catch (Exception $e){
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }
}
