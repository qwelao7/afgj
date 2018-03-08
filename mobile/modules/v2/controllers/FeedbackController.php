<?php
namespace mobile\modules\v2\controllers;

use common\models\hll\DecorateMaintain;
use common\models\hll\DecorateMaterial;
use common\models\hll\DecorateProject;
use common\models\hll\HllFeedbackApply;
use common\models\hll\HllWfCase;
use common\models\hll\HllWfLog;
use common\models\hll\HllWfRate;
use common\models\ecs\EcsUsers;
use Yii;
use yii\base\Exception;
use Yii\db\Query;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use common\components\WxTmplMsg;

class FeedbackController extends ApiController
{

    /**
     * 获取故障材料信息
     * @param $decorate_id
     * @param $cate_id
     * @return ApiResponse
     */
    public function actionDecorateMaterial($decorate_id, $cate_id)
    {
        $response = new ApiResponse();

        $field = ['t1.id', 't2.size', 't2.brand_id', 't2.model_id','t2.type_id'];
        $material = (new Query())->select($field)->from('decorate_material as t1')
            ->leftJoin('decorate_material_goods as t2', 't2.goods_id = t1.goods_id')
            ->where(['t1.decorate_id' => $decorate_id, 't1.valid' => 1, 't2.valid' => 1, 't2.cate_id' => $cate_id])
            ->all();
        if (!$material) {
            $info = [];
        } else {
            $info = [];
            foreach ($material as $item) {
                $brand = DecorateMaterial::getDecorateMaterialGoodsRel($item['brand_id'], 1);
                $model = DecorateMaterial::getDecorateMaterialGoodsRel($item['model_id'], 2);
                $info['name'][] = $brand . ' ' . $model . ' ' . $item['size'];
                $info['material_id'][] = $item['id'];
                $info['type_id'][] = $item['type_id'];
            }
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 提交故障信息
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionMaintainApply()
    {
        $response = new ApiResponse();

        $data = f_post('data', '');
        $data['user_id'] = Yii::$app->user->id;

        $trans = Yii::$app->db->beginTransaction();
        try {
            $status_id = (new Query())->select(['id'])->from('hll_wf_status')
                ->where(['work_id' => 1,'valid' => 1])->orderBy(['id'=>SORT_ASC])->scalar();
            $case = new HllWfCase();
            $case->work_id = 1;
            $case->status_id = $status_id;
            if ($case->save()) {
                $maintain = new DecorateMaintain();
                $data['case_id'] = $case->id;
                if ($maintain->load($data, '') && $maintain->save()) {
                    $flow = (new Query())->select(['id', 'current_status_id', 'next_status_id'])->from('hll_wf_flow')
                        ->where(['work_id' => $case->work_id, 'current_status_id' => $case->status_id, 'valid' => 1])->one();
                    $log = new HllWfLog();
                    $log->case_id = $case->id;
                    $log->user_id = intval($data['user_id']);
                    $log->flow_id = intval($flow['id']);
                    $log->current_status_id = intval($flow['current_status_id']);
                    $log->next_status_id = intval($flow['next_status_id']);
                    if ($log->save()) {
                        $trans->commit();
                        $user_id = (new Query())->select(['user_id'])->from('hll_wf_flow')
                            ->where(['work_id' => $case->work_id, 'current_status_id' => $flow['next_status_id'], 'valid' => 1])->scalar();
                        $user = EcsUsers::getUser($user_id, ['t1.user_id', 't2.openid']);
                        WxTmplMsg::FeedbackNotice($user, $case->work_id);
                        $response->data = new ApiData();
                        $response->data->info = $maintain->id;
                    } else {
                        throw new Exception('保存记录失败', 103);
                    }
                } else {
                    throw new Exception('提交失败', 101);
                }
            } else {
                throw new Exception('创建实例失败', 102);
            }
        } catch (Exception $e) {
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(), $e->getName());
        }
        return $response;
    }

    /**
     * 获取装修的房产
     */
    public function actionDecorateAddress()
    {
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $address = (new Query())->select(['t2.address_desc', 't1.address_id'])->from('decorate_project as t1')
            ->leftJoin('hll_user_address as t2', 't2.id = t1.address_id')
            ->where(['t2.account_id' => $user_id, 't1.valid' => 1, 't2.valid' => 1, 't2.owner_auth' => 1])
            ->orWhere(['t1.is_prototyperoom'=>1,'t1.valid'=>1])->distinct()->all();
        if (!$address) {
            $info = [];
        } else {
            $info['address'] = array_column($address, 'address_desc');
            $info['address_id'] = array_column($address, 'address_id');
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 获取指定房产下的装修
     */
    public function actionDecorateProject($id)
    {
        $response = new ApiResponse();

        $address = (new Query())->select(['title', 'id'])->from('decorate_project')
            ->where(['address_id' => $id, 'valid' => 1])->all();
        if (!$address) {
            $info = [];
        } else {
            $info['title'] = array_column($address, 'title');
            $info['id'] = array_column($address, 'id');
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 获取材料类型
     * @param $id
     * @return ApiResponse
     */
    public function actionDecorateCate($id)
    {
        $response = new ApiResponse();

        $material = (new Query())->select(['t3.name', 't2.cate_id'])->from('decorate_material as t1')
            ->leftJoin('decorate_material_goods as t2', 't2.goods_id = t1.goods_id')
            ->leftJoin('decorate_material_goods_rel as t3', 't3.id = t2.cate_id')
            ->where(['t1.decorate_id' => $id, 't1.valid' => 1, 't2.valid' => 1, 't3.valid' => 1, 't3.type' => 0])
            ->distinct()->all();
        if (!$material) {
            $info = [];
        } else {
            $info['name'] = array_column($material, 'name');
            $info['cate_id'] = array_column($material, 'cate_id');
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 获取故障类型
     * @return ApiResponse
     */
    public function actionMaintainType($id)
    {
        $response = new ApiResponse();

        $material = (new Query())->select(['malfunction'])->from('decorate_material_malfunction')
            ->where(['type_id' => $id, 'valid' => 1])->column();
        if (!$material) {
            $material = [];
        }
        $response->data = new ApiData();
        $response->data->info = $material;
        return $response;
    }

    /**
     * 故障列表
     * @return ApiResponse
     */
    public function actionMaintainList($work_id)
    {
        $response = new ApiResponse();

        if ($work_id == 1) {
            $list = (new Query())->select(['t1.id', 't1.created_at', "CONCAT(t8.name,'故障') as name",
                't5.address_desc', 't3.status_name', 't3.time_limit'])
                ->from('decorate_maintain as t1')
                ->leftJoin('hll_wf_case as t2', 't2.id = t1.case_id')
                ->leftJoin('hll_wf_status as t3', 't3.id = t2.status_id')
                ->leftJoin('decorate_project as t4', 't4.id = t1.decorate_id')
                ->leftJoin('hll_user_address as t5', 't5.id = t4.address_id')
                ->leftJoin('decorate_material as t6', 't6.id = t1.material_id')
                ->leftJoin('decorate_material_goods as t7', 't7.goods_id = t6.goods_id')
                ->leftJoin('decorate_material_goods_rel as t8', 't8.id = t7.cate_id')
                ->where(['t2.work_id' => $work_id, 't1.valid' => 1])->orderBy('t3.id ASC,t2.updated_at DESC')->all();
        } else if ($work_id == 2 || $work_id == 3 || $work_id==4) {
            $list = (new Query())->select(['t1.id', 't1.content', 't3.status_name',
                't1.created_at', 't3.time_limit'])->from('hll_feedback_apply as t1')
                ->leftJoin('hll_wf_case as t2', 't2.id = t1.case_id')
                ->leftJoin('hll_wf_status as t3', 't3.id = t2.status_id')
                ->where(['t1.valid' => 1, 't2.work_id' => $work_id])->orderBy('t3.id ASC,t2.updated_at DESC')->all();
        } else {
            $list = [];
        }
        if ($list) {
            foreach ($list as &$item) {
                if (array_key_exists('content', $item)) {
                    $content = json_decode($item['content']);
                    $item['address_desc'] = $content->fang;
                    $item['name'] = $content->problem;
                    unset($item['content']);
                }
                $item['time'] = intval((time() - strtotime($item['created_at'])) / 3600);
                if ($item['time_limit'] == 0) {
                    $item['over_time'] = 0;
                } else {
                    $item['over_time'] = $item['time'] > $item['time_limit'] ? 1 : 0;
                }
            }
        }
        $response->data = new ApiData();
        $response->data->info = $list;
        return $response;
    }

    /**
     * 故障详情
     * @param $id
     * @return ApiResponse
     */
    public function actionMaintainDetail($id, $work_id)
    {
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        //装修报障
        $detail = static::getDetail($id, $work_id);

        if ($detail) {
            $detail['is_handle'] = 1;
            //本人是否已经处理
            $next_status = (new Query())->select('next_status_id')->from('hll_wf_flow')->where(['work_id'=>$work_id,'current_status_id'=>$detail['status_id']])->scalar();
            if($next_status > $detail['status_id']){
                $flow_count = (new Query())->from('hll_wf_flow')->where(['work_id'=>$work_id,'current_status_id'=>$next_status,'user_id'=>$user_id])->count();
                if($flow_count){
                    $detail['is_handle'] = 0;
                }
            }
        }

        //日志查询
        $flow = (new Query())->select(["date_format(t1.created_at,'%m-%d %H:%i') as time", 't3.flow_name', 't2.nickname', 't1.comment', 't1.img', 't1.creater', 't1.id'])->from('hll_wf_log as t1')
            ->leftJoin('ecs_wechat_user as t2', 't2.ect_uid = t1.user_id')
            ->leftJoin('hll_wf_flow as t3', 't3.id = t1.flow_id')
            ->where(['t1.case_id' => $detail['case_id'], 't1.valid' => 1])
            ->orderBy(['t1.created_at'=>SORT_ASC])->all();
        //日志处理图片
        foreach ($flow as $key => &$item) {
            if ($item['img'] == '') {
                $item['img'] = [];
            } else {
                $item['img'] = explode(',', $item['img']);
            }

            // 除了第一条日志,处理日志查看是否是本次处理
            if ((int)$key > 0) {
                $item['is_self'] = (int)$user_id === (int)$item['creater'];
            } else {
                $item['is_self'] = false;
            }
        }
        $info['detail'] = $detail;
        $info['flow'] = $flow;
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 装修保障用户评价
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionMaintainRate(){
        $response = new ApiResponse();

        $case_id = f_post('case_id', 0);
        $comment = f_post('rate_comment', '');
        $star = f_post('rate_star', 3);
        $img = f_post('img', '');
        $user_id = Yii::$app->user->id;

        $case_status = HllWfCase::findOne(['id' => $case_id, 'valid' => 1]);
        $next_status = (new Query())->select(['next_status_id'])->from('hll_wf_flow')
            ->where(['work_id' => $case_status->work_id, 'current_status_id' => $case_status->status_id])
            ->scalar();

        if ($next_status) {
            $flow = (new Query())->select(['next_status_id', 'current_status_id', "(id) as flow_id"])->from('hll_wf_flow')
                ->where(['work_id' => $case_status->work_id, 'current_status_id' => $next_status,'need_cust'=>1])
                ->one();

            $trans = Yii::$app->db->beginTransaction();
            try {
                if(!$flow){
                    throw new Exception('数据有误',110);
                }
                $case_status->status_id = $next_status;
                if ($case_status->save()) {
                    $rate = new HllWfRate();
                    $rate->user_id = $user_id;
                    $rate->case_id = $case_id;
                    $rate->rate_comment = $comment;
                    $rate->rate_star = $star;
                    $rate->rate_imgs = $img;
                    if($rate->save()){
                        $worker = (new Query())->select(['worker'])->from('hll_wf_work')
                            ->where(['id'=>$case_status->work_id,'valid'=>1])->scalar();
                        $trans->commit();
                        $response->data = new ApiData();
                        $response->data->info = $rate->id;
                        $user = EcsUsers::getUser($worker, ['t1.user_id', 't2.openid']);
                        if($user){
                            WxTmplMsg::DecorateRateShowNotice($user, $case_id);
                        }
                    }else{
                        throw new Exception('保存失败',101);
                    }
                }else{
                    throw new Exception('修改状态失败',102);
                }
            }catch (Exception $e){
                $trans->rollBack();
                $response->data = new ApiData($e->getCode(),$e->getMessage());
            }
        }
        return $response;
    }

    /**
     * 获取流程处理人列表
     * @param $work_id
     * @param $status_id
     * @return ApiResponse
     */
    public function actionNextHandler($work_id, $case_id){
        $response = new ApiResponse();

        $case = HllWfCase::findOne($case_id);
        //获取下一个状态
        $now_status = (new Query())->select(['next_status_id'])->from('hll_wf_flow')
            ->where(['work_id'=>$work_id,'valid'=>1,'current_status_id'=>$case->status_id])->scalar();
        //获取最后状态
        $last_status = (new Query())->select(['current_status_id'])->from('hll_wf_flow')
            ->where(['work_id'=>$work_id,'valid'=>1])->orderBy(['current_status_id'=>SORT_DESC])->one();
        //判断本次操作是否是最后的
        if($now_status == $last_status['current_status_id']){
            $info = [];
        }else{
            $next_status = (new Query())->select(['next_status_id'])->from('hll_wf_flow')
                ->where(['work_id'=>$work_id,'valid'=>1,'current_status_id'=>$now_status])->scalar();
            //判断下次操作是否是最后的
            if($last_status['current_status_id'] == $next_status){
                $info = [];
            }else{
                $info = [];
                //获取下一个处理人的信息
                $next_handler = (new Query())->select(['user_id'])->from('hll_wf_flow')
                    ->where(['work_id'=>$work_id,'valid'=>1,'current_status_id'=>$next_status])->column();
                foreach($next_handler as $item){
                    $user = EcsUsers::getUser($item,['t2.nickname']);
                    $info['user_id'][] = $item;
                    $info['nickname'][] = $user['nickname'];
                }
            }
        }

        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 故障处理
     * @param $case_id
     * @return ApiResponse
     */
    public function actionMaintainOperate()
    {
        $response = new ApiResponse();
        $case_id = f_post('case_id', 0);
        $comment = f_post('comment', '');
        $img = f_post('img', '');
        $user_id = f_post('user_id','');
        $case_status = HllWfCase::findOne(['id' => $case_id, 'valid' => 1]);
        if ($case_status) {
            $next_status = (new Query())->select(['next_status_id'])->from('hll_wf_flow')
                ->where(['work_id' => $case_status->work_id, 'current_status_id' => $case_status->status_id])
                ->scalar();
            if ($next_status) {
                $flow = (new Query())->select(['next_status_id', 'current_status_id', "(id) as flow_id", 'user_id'])->from('hll_wf_flow')
                    ->where(['work_id' => $case_status->work_id, 'current_status_id' => $next_status])
                    ->one();
                $case_status->status_id = $next_status;
                $flow['case_id'] = $case_id;
                $flow['comment'] = $comment;
                $flow['img'] = $img;
                $log = new HllWfLog();
                if ($case_status->save() && $log->load($flow, '') && $log->save()) {
                    if($user_id){
                        foreach($user_id as $item){
                            $user = EcsUsers::getUser($item, ['t1.user_id', 't2.openid']);
                            WxTmplMsg::FeedbackNotice($user, $case_status->work_id);
                        }
                    }else{
                        $next_flow = (new Query())->select(['next_status_id', 'current_status_id','need_cust'])->from('hll_wf_flow')
                            ->where(['work_id' => $case_status->work_id, 'current_status_id' => $flow['next_status_id']])->one();
                        if($next_flow['next_status_id'] == $next_flow['current_status_id'] && $next_flow['need_cust'] == 1){
                            $user = EcsUsers::getUser($case_status->creater, ['t1.user_id', 't2.openid']);
                            WxTmplMsg::DecorateRateNotice($user, $case_id);
                        }
                    }
                    $response->data = new ApiData();
                    $response->data->info = $log->id;
                } else {
                    $response->data = new ApiData(103, '数据保存失败');
                }
            } else {
                $response->data = new ApiData(101, '状态有误');
            }
        } else {
            $response->data = new ApiData(102, '数据有误');
        }
        return $response;
    }

    /**
     * 是否是样板房
     */
    public function actionIsPrototyperoom()
    {
        $response = new ApiResponse();

        $id = f_get('id', 0);

        if ($id == 0) {
            $response->data = new ApiData(100, '参数错误');
        }

        $result = DecorateProject::find()->select(['is_prototyperoom'])->where(['id' => $id, 'valid' => 1])->one();

        $response->data = new ApiData();
        $response->data->info = $result;

        return $response;
    }

    /**
     * 社区反馈信息
     * @param $id
     * @return ApiResponse
     */
    public function actionCommunityFeedback($id)
    {
        $response = new ApiResponse();

        $content = (new Query())->select(['content'])->from('hll_feedback_community')
            ->where(['community_id' => $id, 'valid' => 1])->scalar();
        if ($content) {
            $content = json_decode($content);
        } else {
            $content = [];
        }
        
        $response->data = new ApiData();
        $response->data->info = $content;
        return $response;
    }

    /**
     * 判断用户是否有小区房产
     * @param $id
     * @return ApiResponse
     */
    public function actionCommunityAuth($id)
    {
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $community_id = (new Query())->select(['community_id'])->from('hll_user_address')
            ->where(['account_id' => $user_id, 'owner_auth' => 1, 'valid' => 1])->distinct()->column();

        $name = (new Query())->select(['name'])->from('hll_community')->where(['id'=>$id, 'valid'=>1])->scalar();

        if (in_array($id, $community_id)) {
            $response->data = new ApiData();
        } else {
            $response->data = new ApiData(101, '你没有该小区的房产');
        }

        $response->data->info['name'] = $name;

        return $response;
    }

    public function actionCommunityApply()
    {
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $community_id = f_post('community_id', 0);
        $work_id = f_post('work_id', 0);
        $content = f_post('content', '');

        $trans = Yii::$app->db->beginTransaction();
        try {
            $status_id = (new Query())->select(['id'])->from('hll_wf_status')
                ->where(['work_id' => $work_id,'valid' => 1])->orderBy(['id'=>SORT_ASC])->scalar();
            $case = new HllWfCase();
            $case->work_id = $work_id;
            $case->status_id = $status_id;
            if ($case->save()) {
                $apply = new HllFeedbackApply();
                $apply->community_id = $community_id;
                $apply->user_id = $user_id;
                $apply->case_id = $case->id;
                $apply->content = $content;
                if ($apply->save()) {
                    $flow = (new Query())->select(['id', 'current_status_id', 'next_status_id'])->from('hll_wf_flow')
                        ->where(['work_id' => $case->work_id, 'current_status_id' => $case->status_id, 'valid' => 1])->one();
                    $log = new HllWfLog();
                    $log->case_id = $case->id;
                    $log->user_id = intval($user_id);
                    $log->flow_id = intval($flow['id']);
                    $log->current_status_id = intval($flow['current_status_id']);
                    $log->next_status_id = intval($flow['next_status_id']);
                    if ($log->save()) {
                        $trans->commit();

                        $user_ids = (new Query())->select(['user_id'])->from('hll_wf_flow')
                            ->where(['work_id' => $case->work_id, 'current_status_id' => $flow['next_status_id'], 'valid' => 1])->all();
                        if($user_ids) {
                            foreach ($user_ids as $user_id) {
                                $user = EcsUsers::getUser($user_id['user_id'], ['t1.user_id', 't2.openid']);
                                WxTmplMsg::FeedbackNotice($user, $case->work_id);
                            }
                        }

                        $name = (new Query())->select(['name'])->from('hll_community')->where(['id'=>$community_id, 'valid'=>1])->scalar();
                        $response->data = new ApiData();
                        $response->data->info = $name;
                    } else {
                        throw new Exception('保存记录失败', 103);
                    }
                } else {
                    throw new Exception('保存信息失败', 102);
                }
            } else {
                throw new Exception('新建实例失败', 101);
            }
        } catch (Exception $e) {
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(), $e->getName());
        }
        return $response;
    }

    /**
     * 报障日志列表
     * @param $id
     * @return ApiResponse
     */
    public function actionMaintainLogList($id){
        $response = new ApiResponse();

        $fields = ["date_format(t1.created_at,'%m-%d %H:%i') as time", 't3.flow_name', 't2.nickname', 't1.comment', 't1.img','t2.headimgurl'];
        $flow = HllWfLog::getLogList($id,$fields);

        if ($flow) {
            $info['ask'] = $flow[0];
            array_shift($flow);
            $info['reply'] = $flow;
        } else {
            $info = [];
        }

        $response->data = new ApiData();
        $response->data->info['list'] = $info;
        return $response;
    }

    /**
     * 报障申请表
     * @param $id
     * @return ApiResponse
     */
    public function actionMaintainApplyList($id){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        switch($id){
            case 1:
                $list = (new Query())->select(['t1.failure_cause','t1.failure_pics','t1.contact_name','t1.contact_phone','t3.status_name'])
                    ->from('decorate_maintain as t1')
                    ->leftJoin('hll_wf_case as t2','t2.id = t1.case_id')
                    ->leftJoin('hll_wf_status as t3','t3.id = t2.status_id')
                    ->where(['t1.user_id'=>$user_id,'t1.valid'=>1,'t2.valid'=>1,'t3.valid'=>1])
                    ->orderBy(['t2.created_at' => SORT_DESC])->all();
                if($list){
                    foreach ($list as &$item) {
                        if ($item['failure_pics'] == '') {
                            $item['failure_pics'] = [];
                        } else {
                            $item['failure_pics'] = explode(',', $item['failure_pics']);
                        }
                    }
                }else{
                    $list = [];
                }
                break;
            default:
                $list = (new Query())->select(['t1.content',"(t2.content) as name",'t4.status_name', 't3.created_at', 't3.id'])->from('hll_feedback_apply as t1')
                    ->leftJoin('hll_feedback_community as t2','t2.community_id = t1.community_id')
                    ->leftJoin('hll_wf_case as t3','t3.id = t1.case_id')
                    ->leftJoin('hll_wf_status as t4','t4.id = t3.status_id')
                    ->where(['t1.user_id'=>$user_id,'t1.valid'=>1,'t2.valid'=>1,'t3.valid'=>1,'t4.valid'=>1])
                    ->orderBy(['t3.created_at' => SORT_DESC])->all();
                if($list){
                    foreach($list as &$item){
                        $content = json_decode($item['content']);
                        $name = json_decode($item['name']);
                        foreach ($content as $key => $val) {
                            $name->$key->value = $val;
                            if ($key == 'proof') {
                                if ($name->proof->value == '') {
                                    $name->proof->value = [];
                                } else {
                                    $name->proof->value = explode(',', $name->proof->value);
                                }
                            }
                        }
                        $item['content'] = $name;
                        unset($item['name']);
                    }
                }
                break;
        }

        $response->data = new ApiData();
        $response->data->info['list'] = $list;
        return $response;
    }

    /**
     * 用户故障评价结果
     * @params id -> case_id
     */
    public function actionViewCommentResult($id) {
        $response = new ApiResponse();

        $work_id = HllWfCase::find()->select(['work_id'])->where(['id' => $id, 'valid' => 1])->scalar();

        if ($work_id) {
            //装修报障 详情
            $man_id = DecorateMaintain::find()->select(['id'])->where(['case_id' => $id, 'valid' => 1])->scalar();

            if ($man_id) {
                $detail = static::getDetail($man_id, $work_id);

                if (!empty($detail)) {
                    // 处理流程
                    //日志查询
                    $flow = (new Query())->select(["date_format(t1.created_at,'%m-%d %H:%i') as time", 't3.flow_name', 't2.nickname', 'comment', 'img'])->from('hll_wf_log as t1')
                        ->leftJoin('ecs_wechat_user as t2', 't2.ect_uid = t1.user_id')
                        ->leftJoin('hll_wf_flow as t3', 't3.id = t1.flow_id')
                        ->where(['t1.case_id' => $detail['case_id'], 't1.valid' => 1])
                        ->orderBy(['t1.created_at'=>SORT_ASC])->all();
                    //日志处理图片
                    foreach ($flow as &$item) {
                        if ($item['img'] == '') {
                            $item['img'] = [];
                        } else {
                            $item['img'] = explode(',', $item['img']);
                        }
                    }
                } else {
                    $flow = [];
                }

                // 用户反馈评价
                $log = (new Query())->select(["t1.rate_comment", "t1.rate_imgs", "t1.rate_star", "date_format(t1.created_at,'%m-%d %H:%i') as time", "t2.nickname", "t2.headimgurl"])
                                    ->from('hll_wf_rate as t1')
                                    ->leftJoin('ecs_wechat_user as t2', 't2.ect_uid = t1.user_id')
                                    ->where(['t1.case_id' => $id, 't1.valid' => 1])->one();

                if ($log) {
                    if (!$log['rate_imgs']) {
                        $log['rate_imgs'] = [];
                    } else {
                        $log['rate_imgs'] = explode(',', $log['rate_imgs']);
                    }
                    $info['comment'] = $log;
                } else {
                    $info['comment'] = [];
                }

                $info['detail'] = $detail;
                $info['flow'] = $flow;
                $info['work_id'] = $work_id;

                $response->data = new ApiData();
                $response->data->info = $info;
            } else {
                $response->data = new ApiData(200,'数据错误');
            }
        } else {
            $response->data = new ApiData(200,'数据错误');
        }

        return $response;
    }

    /**
     * 故障详情
     * @params work_id -> work_id
     * @params id -> work_id == 1 id -> 装修项目维修编号
     * @params id -> work_id == 2 id -> 报障的apply_id
     */
    private static function getDetail ($id, $work_id) {
        if ($work_id == 1) {
            $result = (new Query())->select(['t1.contact_name', 't1.contact_phone', 't1.failure_cause',
                't1.case_id', 't1.failure_pics', 't5.address_desc', 't3.status_name', 't3.time_limit','t2.status_id',
                't7.size', 't7.cate_id', 't7.brand_id', 't7.model_id'])
                ->from('decorate_maintain as t1')
                ->leftJoin('hll_wf_case as t2', 't2.id = t1.case_id')
                ->leftJoin('hll_wf_status as t3', 't3.id = t2.status_id')
                ->leftJoin('decorate_project as t4', 't4.id = t1.decorate_id')
                ->leftJoin('hll_user_address as t5', 't5.id = t4.address_id')
                ->leftJoin('decorate_material as t6', 't6.id = t1.material_id')
                ->leftJoin('decorate_material_goods as t7', 't7.goods_id = t6.goods_id')
                ->where(['t1.id' => $id, 't1.valid' => 1])->one();
        } else if ($work_id == 2 || $work_id == 3 ||$work_id==4) {
            $result = (new Query())->select(['t1.id', 't1.content','t2.status_id' ,'t3.status_name', 't1.case_id',
                't1.created_at', 't3.time_limit', "(t4.content) as name"])->from('hll_feedback_apply as t1')
                ->leftJoin('hll_wf_case as t2', 't2.id = t1.case_id')
                ->leftJoin('hll_wf_status as t3', 't3.id = t2.status_id')
                ->leftJoin('hll_feedback_community as t4', 't4.community_id = t1.community_id')
                ->where(['t1.valid' => 1, 't1.id' => $id])->one();
        } else {
            $result = [];
        }

        if (!empty($result)) {
            // 装修报障处理
            if ($work_id == 1) {
                $result['cate_name'] = DecorateMaterial::getDecorateMaterialGoodsRel($result['cate_id'], 0);
                $brand = DecorateMaterial::getDecorateMaterialGoodsRel($result['brand_id'], 1);
                $model = DecorateMaterial::getDecorateMaterialGoodsRel($result['model_id'], 2);
                $result['name'] = $brand . ' ' . $model . ' ' . $result['size'];
                //图片处理
                if (!$result['failure_pics']) {
                    $result['failure_pics'] = [];
                } else {
                    $result['failure_pics'] = explode(',', $result['failure_pics']);
                }
            }
            // 兰园报障处理
            else if ($work_id == 2 || $work_id == 3 || $work_id == 4) {
                $content = json_decode($result['content']);
                $name = json_decode($result['name']);
                foreach ($content as $key => $val) {
                    $name->$key->value = $val;
                    if ($key == 'proof') {
                        if ($name->proof->value == '') {
                            $name->proof->value = [];
                        } else {
                            $name->proof->value = explode(',', $name->proof->value);
                        }
                    }
                }
                $result['content'] = $name;
                unset($result['name']);
            }
        }

        return $result;
    }

    /**
     * 获取故障log处理内容
     * @param $logId
     * @return ApiResponse
     */
    public function actionGetLogInfo($logId) {
        $response = new ApiResponse();

        $result = (new Query())->select(['comment', 'img'])->from('hll_wf_log')->where(['id' => $logId, 'valid' => 1])->one();

        if (!empty($result)) {
            if ($result['img'] == '') {
                $result['img'] = [];
            } else {
                $result['img'] = explode(',', $result['img']);
            }
            if($result['comment'] == null){
                $result['comment'] = '';
            }

            $response->data = new ApiData();
            $response->data->info = $result;
        } else {
            $response->data = new ApiData(200, '数据错误');
        }

        return $response;
    }

    /**
     * 编辑log处理内容
     */
    public function actionEditLog() {
        $response = new ApiResponse();

        $logId = f_post('log_id', 0);
        $comment = f_post('comment', '');
        $img = f_post('img', '');

        if ($logId == 0) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        $query = HllWfLog::find()->where(['id' => $logId, 'valid' => 1])->one();

        if (!empty($query)) {
            $query->comment = $comment;
            $query->img = $img;

            if ($query->save()) {
                $response->data = new ApiData();
            } else {
                $response->data = new ApiData(300, '保存数据出错');
            }
        } else {
            $response->data = new ApiData(200, '数据错误');
        }

        return $response;
    }
}