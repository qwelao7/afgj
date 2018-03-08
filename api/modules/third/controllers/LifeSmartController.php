<?php
/**
 *
 * Life Smart 智能家居
 * 在线报修
 * @author: zend wang
 * @date: 2017-10-26
 * @version: $Id$
 */

namespace api\modules\third\controllers;


use yii;
use yii\db\Query;
use yii\base\Exception;
use yii\rest\Controller;
use common\models\hll\HllWfCase;
use common\models\hll\HllWfLog;
use common\models\hll\HllFeedbackApply;
use common\components\templatemessage\TemplateMessage;
class LifeSmartController extends Controller
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();
		$behaviors['contentNegotiator'] = [
			'class' => yii\filters\ContentNegotiator::className(),
			'formats' => [
				'application/json' => yii\web\Response::FORMAT_JSON,
			],
		];
		return $behaviors;
	}
    public function actionIndex()
    {
        return ['code' => 10000, 'msg' => '接口正常'];
    }
    /**
     * 故障上报到回来啦社区，然后开始走报修处理流程
     * @author zend.wang
     * @time 2017-10-26 15:00
     */
	public function actionFailure()
	{
        //数据入库
        $work_id = 4;
        $community_id = 19663;
        $content = f_post('content', '');

        $status_id = (new Query())->select(['id'])->from('hll_wf_status')
            ->where(['work_id' => $work_id,'valid' => 1])->orderBy(['id'=>SORT_ASC])->scalar();

        $case = new HllWfCase();
        $case->work_id = $work_id;
        $case->status_id = $status_id;

        $trans = Yii::$app->db->beginTransaction();

        try {
            if ($case->save()) {

                $apply = new HllFeedbackApply();
                $apply->community_id = $community_id;
                $apply->user_id = 0;
                $apply->case_id = $case->id;
                $apply->content = $content;

                if ($apply->save()) {
                    $nowFlow = HllWfCase::getFlow(['work_id' => $work_id, 'current_status_id' => $status_id]);

//                    $log = new HllWfLog();
//                    $log->case_id = $case->id;
//                    $log->user_id = 0;
//                    $log->flow_id = intval($nowFlow['id']);
//                    $log->current_status_id = intval($case->status_id);
//                    $log->next_status_id = intval($nowFlow['next_status_id']);

//                    if ($log->save()) {
                        $trans->commit();
                        //开始发起智能家居故障修理流程
                        $nextFlow = HllWfCase::getFlow(['work_id' => $work_id, 'current_status_id' => $nowFlow['next_status_id']]);
                        if($nextFlow) {
                            TemplateMessage::execute("TaskHandleNotice",['userId'=>$nextFlow['user_id'],'url'=>"/error-list.html?id={$work_id}"]);
                        }
				        return ['status' => 200, 'msg' => 'success'];
                    } else {
                        throw new Exception('保存记录失败', 107);
                    }
                } else {
                    throw new Exception('保存记录失败', 106);
                }
//            } else {
//                throw new Exception('保存记录失败', 105);
//            }
        } catch (Exception $e) {
            $trans->rollBack();
            return ['status' => $e->getCode(), 'msg' => $e->getMessage()];
        }
	}
}
