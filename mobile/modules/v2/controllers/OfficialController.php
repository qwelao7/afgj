<?php
namespace mobile\modules\v2\controllers;

use common\models\hll\Bbs;
use common\models\hll\Community;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\helpers\ArrayHelper;
use yii\filters\HttpCache;
use yii\db\Query;
use Yii;

/**
 * Created by PhpStorm.
 * User: kaikai.qin
 * Date: 2016/11/4
 * Time: 9:22
 */
class OfficialController extends ApiController
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
     *咨询列表
     */
    public function actionList()
    {
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        //置顶楼盘
        $top_community = (new Query())->select(['t2.thumbnail','t1.community_id','t3.name',"(1) as type"])
            ->from('hll_community_ext as t1')
            ->leftJoin('fang_loupan as t2','t2.id = t1.loupan_id')
            ->leftJoin('hll_community as t3','t3.id = t1.community_id')
            ->where(['t1.valid'=>1,'t2.valid'=>1,'t3.valid'=>1,'t1.is_top'=>1])->one();
        $community = [];
        array_push($community,$top_community);
        //我拥有的社区id
        $my_community_id = (new Query())->select(['community_id'])->from('hll_user_address')
            ->where(['account_id' => $user_id, 'owner_auth' => 1, 'valid' => 1])->distinct()->column();
        //与楼盘绑定的社区id
        $community_id = (new Query())->select(['community_id'])->from('hll_community_ext')
            ->where(['valid' => 1,'is_top'=>0])->column();
        foreach ($my_community_id as $item) {
            if($top_community['community_id'] == $item){
                continue;
            }else{
                if (in_array($item, $community_id)) {
                    $data = Community::getCommunityThumbnailById($item, 1);
                } else {
                    $data = Community::getCommunityThumbnailById($item, 2);
                }
                if ($data != []) {
                    array_push($community, $data);
                }
            }
        }
        $community_id = array_diff($community_id, $my_community_id);
        $community_ext = Community::getCommunityThumbnailById($community_id, 3);
        $community = array_merge($community, $community_ext);
        $response->data = new ApiData();
        $response->data->info = $community;
        return $response;
    }
    
//    public function actionList(){
//        $response = new ApiResponse();
//
//        $time = date('Y-m-d H:i:s',time());
//        $sql = "select l.id,l.name,l.thumbnail,m.title,m.created_at from fang_loupan l left OUTER JOIN
//( SELECT * FROM(select * from message where message_type = 1 and valid = 1 and publish_time <= '".$time."' order by publish_time desc) r group by r.loupan_id) m on l.id = m.loupan_id where l.status != 4 and l.valid = 1 order by m.publish_time desc";
//        $con = Yii::$app->db;
//        $command = $con->createCommand($sql);
//        $query = $command->query();
//        $data = $query->readAll();
//        foreach($data as &$item){
//            $item['created_at'] = strtotime($item['created_at']);
//        }
//        $response->data = new ApiData();
//        $response->data->info = $data;
//        return $response;
//    }

    /**
     * 社区详情
     * @param $id
     * @return ApiResponse
     */
    public function actionDetail($id){
        $response = new ApiResponse();
        $now_date = strtotime(date("Y-m-d",time()));
        $info = (new Query())->select(['t1.bg_pic','t2.name','t1.loupan_id','(1) as type'])->from('hll_community_ext as t1')
        ->leftJoin('hll_community as t2','t2.id = t1.community_id')
        ->where(['t1.community_id'=>$id,'t1.valid'=>1,'t2.valid'=>1])->one();
        if(!$info){
            $info = (new Query())->select(['(id) as loupan_id','name','(2) as type',"('') as bg_pic"])->from('hll_community')->where(['id'=>$id,'valid'=>1])->one();
            if(!$info){
                $response->data = new ApiData(101, '数据错误');
                return $response;
            }
        }
        $end_date = (new Query())->select(['end_date','content'])->from('hll_community_count_down')
            ->where(['community_id' => $id, 'valid' => 1])->one();
        if (!$end_date) {
            $info['end_date'] = 0;
            $info['content'] = '';
        } else {
            $info['end_date'] = (strtotime($end_date['end_date']) <= $now_date) ? 0 : (strtotime($end_date['end_date']) - $now_date) / 86400;
            $info['content'] = $end_date['content'];
        }
        $menu = (new Query())->select(['menu_name', 'menu_pic', 'menu_url', 'need_identify'])->from('hll_community_menu')
            ->where(['community_id' => $id, 'valid' => 1])->orderBy(['menu_order' => SORT_ASC])->all();
        if(!$menu){
            $menu = (new Query())->select(['menu_name', 'menu_pic', 'menu_url'])->from('hll_community_menu')
                ->where(['community_id' => 0, 'valid' => 1])->orderBy(['menu_order' => SORT_ASC])->all();
            foreach($menu as &$item){
                $item['menu_url'] = $item['menu_url'] .$id;
            }
            $info['menu'] = $menu;
        }else{
            $info['menu'] = $menu;
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 文章列表
     * @param $id
     * @param $community_id
     * @return ApiResponse
     */
    public function actionArticleList(){
        $response = new ApiResponse();

        $id = f_get('id',0);
        $community_id = f_get('community_id',0);
        $page = f_get('page',1);

        $query = (new Query())->select(['t2.article_id','t3.thumbnail','t3.title','t3.brief','t3.article_type'])
            ->from('message_article_list as t1')
            ->leftJoin('message_article_list_content as t2','t2.list_id = t1.id')
            ->leftJoin('message_article as t3','t3.id = t2.article_id')
            ->where(['t1.id'=>$id,'t1.valid'=>1,'t2.valid'=>1,'t3.valid'=>1])
            ->orderBy(['t2.article_order'=>SORT_DESC]);
        $info = $this->getDataPage($query,$page);
        if($info['list']){
            foreach($info['list'] as &$item){
                if($item['article_type'] == 2){
                    $item['url'] = (new Query())->select(['content'])->from('message_article')
                        ->where(['id'=>$item['article_id']])->scalar();
                }else{
                    $item['url'] = '';
                }
            }
        }
        $info['community'] = Community::find()->select(['name'])->where(['id'=>$community_id,'valid'=>1])->scalar();
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 文章详情
     * @param $id
     * @return ApiResponse
     */
    public function actionArticleDetail($id){
        $response = new ApiResponse();
        $info = (new Query())->select(['content'])->from('message_article')
            ->where(['id'=>$id,'valid'=>1])->scalar();
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 判断是否有认证的房产
     * @param $id
     * @return ApiResponse
     */
    public function actionCommunityAuth($id){
        $response = new ApiResponse();

        $address = (new Query())->from('hll_user_address')->where(['account_id'=>Yii::$app->user->id,
            'community_id'=>$id,'owner_auth'=>1,'valid'=>1])->count();

        if($address){
            $response->data = new ApiData();
            $response->data->info['hasauth'] = (bool)$address > 0;
        }else{
            $response->data = new ApiData(102,'您没有认证的房产');
        }

        return $response;
    }
}