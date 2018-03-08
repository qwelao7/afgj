<?php

namespace mobile\modules\v2\controllers;
use common\models\ar\message\MessageNotification;
use common\models\hll\ItemUnlock;
use common\models\hll\UserAddress;
use common\models\hll\ItemSharing;
use common\models\hll\ItemUnlockRequirement;
use Yii;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\helpers\ArrayHelper;
use yii\db;

/**
 * 我为小区添便利 解锁活动
 * @package api\modules\v2\controllers
 */
class UnlockController extends ApiController
{

    private $communityId;//小区ID，默认平治东苑
    private $msg;
    private $message;
    private $imessage;

    public function init() {
        parent::init();
        $this->communityId = 17184;
        $this->msg = '亲爱的用户,本活动只有平治东苑业主方能参加,请您先添加并认证房产,谢谢!';
        $this->message = '亲爱的业主朋友,只有平治东苑的业主才可领取此物品。';
        $this->imessage = '亲爱的业主朋友,只有平治东苑的业主才可借用此物品。';
    }

    /**
     * 活动首页
     * @method GET
     * @author zend.wang
     * @date  2016-11-16 13:00
     */
    public function actionIndex() {

        $items = ItemUnlock::getItemsByCommunityId($this->communityId);

        $response = new ApiResponse();

        if($items) {
            $response->data = new ApiData();
            $response->data->info = $items;
        } else {
            $response->data = new ApiData(101,'活动物品为空');
        }

        return $response;
    }
    /**
     * 解锁,已经开展活动的小区用户一天解锁一次
     * @method GET
     * @param $id  int 活动物品Id
     * @author zend.wang
     * @date  2016-11-16 13:00
     */
    public function actionUnlock() {
        $id = Yii::$app->request->post('id');
        $community = Yii::$app->request->post('community');
        if(!$community || $community == '') {
            $community = $this->communityId;
        }

        $userId = Yii::$app->user->id;
        $response = new ApiResponse();

        $result = static::actionAuth();
        switch($result) {
            case 0:
                $response->data = new ApiData(103,'需要认证');
                break;
            case 1:
                $query = UserAddress::getUserCommunitys($userId,['t2.name']);
                $query = array_column($query, 'name');
                foreach ($query as $key=>$item) {
                    $data[] = [$userId, '2', $item];
                }

                Yii::$app->db->createCommand()->batchInsert(ItemUnlockRequirement::tableName(), ['user_id', 'requirement_type', 'requirement_content'], $data)->execute();
                $response->data = new ApiData(102,'等待开通');
                break;
            case 2:
                if( !ItemUnlock::isUnlockItem($community,$userId)) {
                    $response->data = new ApiData(101,'已经使用');
                } else {
                    $result = ItemUnlock::unlockItem($id,$community,$userId);
                    if($result) {
                        if($result['status'] == 1) {
                            $response->data = new ApiData(100, '解锁进行中');
                        }else if($result['status'] == 2) {
                            $response->data = new ApiData();
                        }
                        $response->data->info = $result;
                    } else {
                        $response = new ApiResponse(502,'解锁保存异常');
                    }
                }
                break;
        }
        return $response;
    }
    /**
     * 领取活动物品
     * @method GET
     * @param $id  int 活动物品Id
     * @author kaikai.qin
     * @date  2016-11-16 13:00
     */
    public function actionClaim() {
        $response = new ApiResponse();
        $id = Yii::$app->request->post('id');
        $userId = Yii::$app->user->id;

        $result = static::actionAuth();
        switch($result) {
            case 0:
                $response->data = new ApiData(101,'需要认证');
                $response->data->info = $this->msg;
                break;
            case 1:
                $response->data = new ApiData(102,'等待开通');
                $response->data->info = $this->message;
                break;
            case 2:
                $model = ItemUnlock::findOne($id);
                if(!$model){
                    $response->data = new ApiData(103, '无此物品编号');
                }else{
                    $response->data = new ApiData();
                }
                break;
        }
        return $response;
    }
    /**
     * 借用活动物品
     * @method GET
     * @param $id  int 活动物品Id
     * @author kaikai.qin
     * @date  2016-11-16 13:00
     */
    public function actionBorrow($id) {
        $response = new ApiResponse();

        $result = static::actionAuth();
        switch($result) {
            case 0:
                $response->data = new ApiData(101,'需要认证');
                $response->data->info = $this->msg;
                break;
            case 1:
                $response->data = new ApiData(102,'等待开通');
                $response->data->info = $this->imessage;
                break;
            case 2:
                $id = ItemUnlock::find()->select(['sharing_id'])->where(['id'=>$id, 'valid'=>1, 'item_status'=>[3,4]])->one();
                if(!$id){
                    $response->data = new ApiData(103, '无此借用物品编号');
                }else{
                    $response->data = new ApiData();
                    $response->data->info = $id;
                }
                break;
        }
        return $response;
    }
    /**
     * 收集用户需求
     * @method POST
     * @param $requirement_type  int 需求类型
     * @param $requirement_content  string 需求内容
     * @author kaikai.qin
     * @date  2016-11-16 13:00
     */
    public function actionSuggest() {
        $response = new ApiResponse();
        $result = static::actionAuth();

        switch($result){
            case 0:
                $response->data = new ApiData(101,'需要认证');
                $response->data->info = $this->msg;
                break;
            case 1:case 2:
                $type = Yii::$app->request->post('type');
                $data = Yii::$app->request->post('data');
                $model = new ItemUnlockRequirement();
                if($type==1){
                    $model->requirement_type = 1;
                }elseif($type==2){
                    $model->requirement_type = 2;
                }
                $model->requirement_content = $data;
                $model->user_id = Yii::$app->user->id;
                if($model->save()){
                    $num = ItemUnlockRequirement::find()->where(['requirement_type'=>2,'requirement_content'=>$data])->count();
                    $response->data = new ApiData();
                    $response->data->info = $num;
                }else{
                    var_dump($model->errors);
                    $response->data = new ApiData(101, '提交失败！');
                }
                break;
        }
        return $response;
    }
    //确认领取物品
    public function actionReceive() {
        $response = new ApiResponse();
        $result = static::actionAuth();
        switch($result){
            case 0:
                $response->data = new ApiData(101,'需要认证');
                $response->data->info = $this->msg;
                break;
            case 1:
                $response->data = new ApiData(102,'等待开通');
                $response->data->info = $this->imessage;
                break;
            case 2:
                $userId= Yii::$app->user->id;
                $id = Yii::$app->request->post('id');
                $model = ItemUnlock::findOne($id);
                $trans = Yii::$app->db->beginTransaction();
                try{
                    //创建借用
                    $sharing = new ItemSharing();
                    $sharing->community_id = $this->communityId;
                    $sharing->account_id = $userId;
                    $sharing->share_type = 1;
                    $sharing->borrow_item_type = 1;
                    $sharing->item_desc = $model->item_desc;
                    $sharing->item_pics = $model->item_pics;
                    if($sharing->save()){
                        //保存物品信息
                        $model->item_status = 4;
                        $model->receiver_id = $userId;
                        $model->sharing_id = $sharing->id;
                        if($model->save()){
                            $trans->commit();
                            $response->data = new ApiData(0, '领取成功！');
                        }else{
                            $response->data = new ApiData(101, '保存失败！');
                        }
                    }else{
                        $response->data = new ApiData(102, '创建借用失败！');
                    }
                }catch (\yii\db\Exception $e){
                    $trans->rollBack();
                    $response->data = new Apidata(103, '操作失败');
                }
                break;
        }
        return $response;
    }
    /**
     * 用户认证
     * @param $userId
     * @return int
     */
    public function actionAuth($userId=0) {
        if($userId == 0) {
            $userId = Yii::$app->user->id;
        }

        $result = 0; // 不是业主

        $communityIds = UserAddress::getUserCommunitys($userId,['t1.community_id', 't2.name']);

        if($communityIds) {
            $communityIds = ArrayHelper::getColumn($communityIds,'community_id');
            if(!in_array($this->communityId,$communityIds)) {
                $result = 1;  //未拥有该房产的业主
            }else {
                $result = 2;  //拥有该房产
            }
        }

        return $result;
    }
}
