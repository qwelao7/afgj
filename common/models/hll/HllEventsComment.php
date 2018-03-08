<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_events_comment".
 *
 * @property string $id
 * @property string $events_id
 * @property string $content
 * @property integer $display_order
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventsComment extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_events_comment';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['events_id', 'content'], 'required'],
            [['events_id', 'display_order', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['content'], 'string', 'max' => 200],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'events_id' => 'Events ID',
            'content' => 'Content',
            'display_order' => 'Display Order',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //根据活动id获取评论
    public static function getCommentByEventsId($id){
        $data = (new Query())->select(['t2.nickname','t2.headimgurl','t1.content','t1.created_at'])->from('hll_events_comment as t1')
            ->leftJoin('ecs_wechat_user as t2','t2.ect_uid=t1.creater')
            ->where(['t1.valid'=>1,'t1.events_id'=>$id])->orderBy(['display_order'=>SORT_DESC, 'created_at'=>SORT_DESC]);
        return $data;
    }
}
