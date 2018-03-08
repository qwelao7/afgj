<?php
namespace mobile\modules\v2\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\filters\Cors;
use yii\filters\HttpCache;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use mobile\modules\v2\models\User;
use common\models\ar\message\MessageVote;
use common\models\ar\message\MessageVoteQuestion;
use common\models\ar\message\MessageVoteQuestionItem;
use common\models\ar\message\MessageVoteResult;
use mobile\components\ApiController;

/**
 * 投票相关操作接口
 * 无需登录
 * Class WechatController
 * @package api\modules\v1\controllers
 */
class VoteController extends ApiController
{
    public $second_cache = 60;

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => f_params('origins'),//定义允许来源的数组
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],//允许动作的数组
                ],
            ],
            [
                'class' => HttpCache::className(),
                'only' => ['view', 'result'],
                'lastModified' => function () { // 设置 Last-Modified 头
                    return time() + $this->second_cache;
                },
                'cacheControlHeader' => 'Cache-Control: public, max-age=' . $this->second_cache,
            ],
        ]);
    }

    /**
     * 投票结果
     * @param $id vote_id
     * @return ApiResponse
     */
    public function actionResult($id)
    {
        $response = new ApiResponse();
        $userId = Yii::$app->user->id;
        if (!$id) {
            $response->data = new ApiData(1, 'id参数不能为空');
            return $response;
        }

        $fields = ['title', 'thumbnail', 'deadline', 'content','id'];

        $vote = MessageVote::find()->select($fields)->where(['id' => $id, 'valid' => 1, 'is_show' => 1])->asArray()->one();

        if (!$vote) {
            $response->data = new ApiData(4, '投票信息未找到');
            return $response;
        } else {
            $vote['thumbnail'] = empty($vote['thumbnail']) ? 'defaultpic/vote.jpg' : $vote['thumbnail'];
        }

        $info['vote'] = $vote;
        $info['questions'] = MessageVoteQuestion::find()->select(['id', 'title', 'votetype'])->where(['mv_id' => $id, 'valid' => 1])->asArray()->all();
        if ($info['questions']) {
            foreach ($info['questions'] as &$list) {
                $list['type'] = MessageVoteQuestion::$types[$list['votetype']];
                $list['total_num'] = 0;
                $list['options'] = MessageVoteQuestionItem::find()->select(['id', 'content', 'picpath'])->where(['mvq_id' => $list['id'], 'valid' => 1])->asArray()->all();
                foreach ($list['options'] as &$item) {
                    $result = MessageVoteResult::find()->where(['mvqi_id' => $item['id'], 'valid' => 1])->count();
                    $item['voted_num'] = $result;
                    $list['total_num'] += $item['voted_num'];
                    if ($userId) {
                        $my_result = MessageVoteResult::find()->where(['mvqi_id' => $item['id'], 'valid' => 1, 'account_id' => $userId])->count();
                        !empty($my_result) ? $item['voted'] = true : $item['voted'] = false;
                    }
                }
            }
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 投票
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionJoin()
    {

        $response = new ApiResponse();
        $data = Yii::$app->request->post();
        $userId = Yii::$app->user->id;

        if (!$data || empty($data['options']) || empty($data['v_id']) || !is_array($data['options'])) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }


        $db = Yii::$app->db;
        $rows = [];
        foreach ($data['options'] as $item) {
            $rows[] = [$data['v_id'], $item, $userId];
        }

        $transaction = $db->beginTransaction();
        try {
            $result = $db->createCommand()->batchInsert(MessageVoteResult::tableName(),
                ['mv_id', 'mvqi_id', 'account_id'], $rows)->execute();
            $transaction->commit();
            if ($result) {
                $response->data = new ApiData(0);
            } else {
                $response->data = new ApiData(101, '数据同步失败,请重试');
            }
        } catch (\yii\db\Exception $e) {
            $transaction->rollback();
            $response->data = new ApiData(102);
            $response->error = "保存异常";
        }

        return $response;
    }

    /**
     * 投票详情
     * @param $id vote_id
     * @param null $fields
     * @return ApiResponse
     */
    public function actionInfo($id, $fields = null)
    {
        $response = new ApiResponse();

        if (empty($id)) {
            $response->data = new ApiData(100, '参数错误');
            return $response;
        }

        if (!$fields) {
            $fields = ['id','title', 'thumbnail', 'deadline', 'content', 'is_show'];
        }

        $userId = Yii::$app->user->id;

        $val = MessageVote::find()->select($fields)->where(['id' => $id, 'valid' => 1])->asArray()->one();
        if ($val) {
            $val['join'] = (bool)MessageVoteResult::find()->where(['account_id' => $userId, 'mv_id' => $id, 'valid' => 1])->one();
            if ($val['thumbnail'] == '' || empty($val['thumbnail'])) {
                $val['thumbnail'] = Yii::$app->params['defaultVoteImg'];
            }
            $info['state']['expired'] = (strtotime($val['deadline']) > time()) ? false : true;  //投票未过期
            $info['state']['voted'] = (bool)MessageVoteResult::find()->where(['mv_id' => $id, 'account_id' => $userId, 'valid' => 1])->count();

            if (!$info['state']['voted']) {
                $info['vote'] = $val;
                $info['questions'] = MessageVoteQuestion::find()->select(['id', 'title', 'votetype'])->where(['mv_id' => $id, 'valid' => 1])->asArray()->all();
                if ($info['questions']) {
                    foreach ($info['questions'] as &$list) {
                        $list['type'] = MessageVoteQuestion::$types[$list['votetype']];
                        $list['options'] = MessageVoteQuestionItem::find()->select(['id', 'content', 'picpath'])->where(['mvq_id' => $list['id'], 'valid' => 1])->asArray()->all();
                    }
                }
            }else {
                $info['vote']['is_show'] = $val['is_show'];
            }

            $response->data = new ApiData();
            $response->data->info = $info;
        } else {
            $response->data = new ApiData(101, '数据错误');
        }

        return $response;
    }
}
