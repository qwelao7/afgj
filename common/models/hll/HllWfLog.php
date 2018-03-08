<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
use Yii\db\Query;
/**
 * This is the model class for table "hll_wf_log".
 *
 * @property string $id
 * @property integer $case_id
 * @property integer $flow_id
 * @property integer $user_id
 * @property string $comment
 * @property string $img
 * @property integer $current_status_id
 * @property integer $next_status_id
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllWfLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_wf_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['case_id', 'flow_id', 'user_id', 'current_status_id', 'next_status_id', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['comment'], 'string', 'max' => 1000],
            [['img'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'case_id' => 'Case ID',
            'flow_id' => 'Flow ID',
            'user_id' => 'User ID',
            'comment' => 'Comment',
            'img' => 'Img',
            'current_status_id' => 'Current Status ID',
            'next_status_id' => 'Next Status ID',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 报障日志列表
     * @param $id
     * @return array
     */
    public static function getLogList($id,$fields){
        //日志查询
        $flow = (new Query())->select($fields)->from('hll_wf_log as t1')
            ->leftJoin('ecs_wechat_user as t2', 't2.ect_uid = t1.user_id')
            ->leftJoin('hll_wf_flow as t3', 't3.id = t1.flow_id')
            ->where(['t1.case_id' => $id, 't1.valid' => 1])->orderBy(['t1.created_at'=>SORT_ASC])->all();
        if($flow){
            //日志处理图片
            foreach ($flow as &$item) {
                if ($item['img'] == '') {
                    $item['img'] = [];
                } else {
                    $item['img'] = explode(',', $item['img']);
                }
            }
        }else{
            $flow = [];
        }
        return $flow;
    }
}
