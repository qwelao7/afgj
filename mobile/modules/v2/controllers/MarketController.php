<?php
namespace mobile\modules\v2\controllers;

use Yii;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use common\models\hll\ItemSharing;
use common\models\hll\ItemSharingPraise;
use common\models\hll\ItemSharingComment;
use common\models\ecs\EcsUsers;
use common\models\ecs\EcsAccountLog;
use yii\data\ActiveDataProvider;
use yii\db\Query;
/**
 * 小市控制器
 * Created by PhpStorm.
 * User: kaikai.qin
 * Date: 2016/10/9
 * Time: 9:11
 */
class MarketController extends ApiController
{
    /**
     * 小市列表
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
        $keywords = Yii::$app->request->get('keywords');

        $cur = Yii::$app->user->id;
        if($type == 0) $type = '';

        $fields = ['t1.id','t1.sell_item_type','t1.sell_item_price','t1.item_desc','t1.item_pics','t1.thanks_num','t1.praise_num','t1.comment_num','t1.created_at','t2.nickname', 't2.headimgurl', 't2.ect_uid'];

        $sql = (new Query())->select($fields)
            ->from('hll_item_sharing as t1')
            ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.account_id')
            ->where(['t1.valid'=>1,'t1.share_type'=>2,'t1.status'=>2,'t1.community_id'=>$id]);
        //分类选择
        $type = trim($type);
        if($type){
            $sql->andWhere(['t1.sell_item_type'=>$type]);
        }
        //搜索功能
        $keywords = trim($keywords);
        if($keywords){
            $sql->andWhere(['like','t1.item_desc',$keywords])->orderBy(['t1.created_at'=>SORT_DESC]);
        }else {
            $sql->orderBy(['t1.created_at'=>SORT_DESC]);
        }
        //对数据进行分页处理
       $info = $this->getDataPage($sql,$page);
        if($info['pagination']['total'] > 0){
            if($info['list']!=[]){
                foreach($info['list'] as &$item) {
                    $item['sell_item_type'] = ItemSharing::getSellType($item['sell_item_type']);
                    if($item['item_pics'] != null) {
                        $item['item_pics'] = explode(',', $item['item_pics']);
                    }
                    $item['isPraise'] = (bool)itemSharingPraise::find()->where(['is_id'=>$item['id'], 'creater'=>Yii::$app->user->id])->count();
                    $item['isOwner'] = ($cur == $item['ect_uid'])?true:false;
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
     * 物品详情
     * @method get
     * @param  $id int 物品编号
     * @return ApiResponse
     * @author kaikai.qin
     * Date: 2016/10/9
     */
    public function actionDetail($id)
    {
        $response = new ApiResponse();

        $cur = Yii::$app->user->id;

        $sql = (new Query())->select(['t1.created_at','t1.id','t1.sell_item_type','t1.sell_item_price','t1.item_desc','t1.item_pics','t2.nickname','t2.headimgurl', 't2.ect_uid'])
            ->from('hll_item_sharing as t1')
            ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.account_id')
            ->where(['t1.id'=>$id])->one();
        $sql['type'] = $sql['sell_item_type'];
        $sql['sell_item_type'] = ItemSharing::getSellType($sql['sell_item_type']);
        if($sql['item_pics'] != null) {
            $sql['item_pics'] = explode(',', $sql['item_pics']);
        }
        $info['desc'] = $sql;
        //获取点赞列表
        $info['praise'] = ItemSharingPraise::getPraiseBySharingId($id);
        //获取留言列表
        $info['comment'] = ItemSharingComment::getCommentBySharingId($id);
        //本人是否已经点过赞
        $info['desc']['isPraise'] = (bool)itemSharingPraise::find()->where(['is_id'=>$id, 'creater'=>Yii::$app->user->id])->count();

        $info['desc']['isOwner'] = ($cur == $sql['ect_uid'])?true:false;

        $response->data = new ApiData();
        $response->data->info=$info;
        return $response;
    }

    /**
     * 发布物品信息
     * @method post
     * @param type int 物品类型
     * @param loupan_id int 交易小区
     * @param pirce int 物品价格
     * @param account_id int 用户
     * @param desc string 物品描述
     * @param  picture string 上传图片
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
        $data['share_type'] = 2;
        $model = new ItemSharing();
        if($model->load($data,'')){
            if($model->save()) {
                $response->data = new Apidata(0, '保存成功');
            }else {
                $response->data = new ApiData(110, '保存失败');
            }
        }else{
            $response->data = new ApiData(112, '数据装载失败');
        }
        return $response;
    }

    /**
     * 放回用户的小区号
     * @return ApiResponse
     */
    public function actionCommunitys()
    {
        $response = new ApiResponse();
        $account_id = Yii::$app->user->id;
        $list = (new Query())->select(['t1.community_id', 't2.name'])
            ->from('hll_user_address as t1')->distinct()
            ->leftJoin('hll_community as t2', 't2.id = t1.community_id')
            ->where(['t1.account_id' => $account_id, 't1.valid' => 1,'t1.owner_auth'=>1])
            ->orderBy(['t1.is_default'=>SORT_ASC,'t1.community_id' => SORT_ASC])->all();
        if (!$list) {
            $response->data = new ApiData(110, '无相关数据');
            return $response;
        }
        $response->data = new ApiData();
        $response->data->info = $list;
        return $response;
    }
}