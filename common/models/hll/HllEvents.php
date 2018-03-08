<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
use yii\base\Exception;
/**
 * This is the model class for table "hll_events".
 *
 * @property string $id
 * @property string $title
 * @property string $thumbnail
 * @property string $content
 * @property string $address
 * @property string $deadline
 * @property integer $events_num
 * @property integer $joined_num
 * @property integer $comment_num
 * @property integer $free
 * @property integer $fee
 * @property integer $accept_point
 * @property string $accept_point_community_id
 * @property string $auth_way
 * @property string $events_type
 * @property string $ext_fields
 * @property integer $display_order
 * @property integer $apply_check
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 * @property string $tel
 */
class HllEvents extends ActiveRecord
{

    private static $default = 'defaultpic/active.jpg';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_events';
    }
    public static $event_status_list=[1=>'活动进行中',2=>'报名已结束',3=>'活动审核中',4=>'活动审核不通过',5=>'报名已截止'];
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'address', 'begin_time', 'end_time','deadline'], 'required'],
            [['events_num', 'joined_num', 'comment_num', 'free', 'display_order','status', 'creater', 'updater', 'valid','apply_check'], 'integer'],
            [['created_at', 'updated_at', 'content', 'thumbnail','tel','accept_point','accept_point_community_id'], 'safe'],
            [['title', 'thumbnail', 'address', 'fee', 'events_time'], 'string', 'max' => 100],
            [['auth_way', 'events_type'], 'string', 'max' => 50],
            [['ext_fields'], 'string', 'max' => 1000],
            [['tel'], 'string', 'max' => 14]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'thumbnail' => 'Thumbnail',
            'content' => 'Content',
            'address' => 'Address',
            'begin_time' => 'Begin Time',
            'end_time' => 'End Time',
            'events_time' => 'Events Time',
            'events_num' => 'Events Num',
            'joined_num' => 'Joined Num',
            'comment_num' => 'Comment Num',
            'free' => 'Free',
            'fee' => 'Fee',
            'accept_point_community_id' => 'Accept Point Community Id',
            'accept_point' => 'Accept Point',
            'auth_way' => 'Auth Way',
            'status' => 'Status',
            'events_type' => 'Events Type',
            'ext_fields' => 'Ext Fields',
            'display_order' => 'Display Order',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
            'tel'   => 'Tel',
            'apply_check'   => 'Apply Check',
        ];
    }

    //根据id获取活动的详情
    public static function getActivityDetailById($id, $fields = null)
    {
        if (empty($fields)) {
            $fields = ['t1.title', 't1.address', 't1.thumbnail', 't1.events_num', 't1.joined_num', 't1.events_type', 't1.ext_fields','t1.tel',
                't1.free', 't1.fee', 't1.comment_num', 't1.begin_time', 't1.end_time', 't1.events_time', 't1.created_at', 't1.creater', 't2.nickname'];
        }
        $data = (new Query())->select($fields)->from('hll_events as t1')
            ->leftJoin('ecs_wechat_user as t2', 't2.ect_uid = t1.creater')
            ->where(['t1.valid' => 1, 't1.id' => $id])->one();
        return $data;
    }

    //根据小区id获取活动列表
    public static function getActivityList($community_id)
    {
        $nowTime = time();
        if ($community_id == 0) {
            $auth_way = 'public';
            $field = ['id', 'free', 'fee', 'thumbnail', 'title', 'events_time', 'auth_way', 'official',"if(UNIX_TIMESTAMP(end_time) > $nowTime,0,1) as is_past"];
            $data = (new Query())->select($field)->from('hll_events')->where(['auth_way' => $auth_way, 'valid' => 1,'status'=>[1,2]])->orderBy(['is_past' => SORT_ASC,'created_at' => SORT_DESC]);
        } else {
            $auth_way = 'community';
            $public = 'public';
            $field = ['t2.id', 't2.free', 't2.fee', 't2.thumbnail', 't2.title', 't2.events_time', 't2.auth_way', 't2.official',"if(UNIX_TIMESTAMP(t2.end_time) > $nowTime,0,1) as is_past"];
            $data = (new Query())->select($field)->from('hll_events_community as t1')
                ->rightJoin('hll_events as t2', 't2.id = t1.events_id')
                ->where(['t2.valid' => 1, 't2.status'=>[1,2],'t2.auth_way' => $public])
                ->orWhere(['t1.community_id' => $community_id, 't1.valid' => 1, 't2.status'=>[1,2], 't2.valid' => 1, 't2.auth_way' => $auth_way])
                ->orderBy(['is_past'=>SORT_ASC,'t2.auth_way' => SORT_DESC, 't2.created_at' => SORT_DESC])->distinct(['t2.id']);
        }
        return $data;
    }

    public static function getEvents($id, $field = ['*'])
    {
        $events = HllEvents::find()->select($field)->where(['id' => $id, 'valid' => 1])
            ->asArray()->one();
        return $events;
    }

    //创建活动
    public static function createOrUpdateApply($events_data)
    {
        $events_data['thumbnail'] = $events_data['thumbnail'] == '' ? static::$default : $events_data['thumbnail'];
        $events_data['begin_time'] = date("Y-m-d H:i:s", strtotime($events_data['events_begin'].':00'));
        $events_data['end_time'] = date("Y-m-d H:i:s", strtotime($events_data['events_end'].':00'));
        $events_data['events_time'] = date("m-d H:i", strtotime($events_data['events_begin'].':00')) . '至' . date("m-d H:i", strtotime($events_data['events_end'].':00'));
        $community_id = $events_data['auth_way'];
        $events_data['auth_way'] = $events_data['auth_way'] == 0 ? 'public' : 'community';
        $events_data['creater'] = Yii::$app->user->id;
        $events_data['content'] = static::getEventsContent($events_data['content'],$events_data['img']);
        $events_data['status'] = 3;
        $events_data['deadline'] = $events_data['apply_end'].':00';
        unset($events_data['img']);
        unset($events_data['apply_end']);
        $trans = Yii::$app->db->beginTransaction();
        try {
            if(isset($events_data['events_id'])){
                $events_model = HllEvents::findOne(['id'=>$events_data['events_id'],'valid'=>1]);
                if(!$events_model){
                    throw new exception("无此活动信息", 104);
                }
            }else{
                $events_model = new HllEvents();
            }
            if ($events_model->load($events_data, '')) {
                if ($events_model->save()) {
                    if($events_data['auth_way'] == 'community'){
                        $events_community = new HllEventsCommunity();
                        $events_community->events_id = $events_model->id;
                        $events_community->community_id = $community_id;
                        if ($events_community->save()) {
                            $trans->commit();
                            return true;
                        } else {
                            throw new exception("活动与社区关联信息保存失败", 103);
                        }
                    }else{
                        $trans->commit();
                        return true;
                    }
                } else {
                    throw new exception("活动保存失败", 102);
                }
            } else {
                throw new exception("数据加载失败", 101);
            }
        } catch (\yii\db\Exception $e) {
            $trans->rollBack();
            throw new exception("创建活动失败", 100);
        }
    }

    //活动副文本
    public static function getEventsContent($eventsContent,$eventsImg){
        $content = '';
        if(!empty($eventsContent)){
            $content = $content . '<section class="_135editor" data-tools="135编辑器" data-id="88968" style="border: 0px none; padding: 0px; position: relative;">
<section class="layout" style="margin: 10px auto;"><p>' . $eventsContent  . '<br></p></section></section><p>';
        }
        if($eventsImg != ''){
            $eventsImg = explode(',',$eventsImg);
            $img_start = '<section class="_135editor" data-tools="135编辑器" data-id="86362"><img src="';
            $img_end = '" style="width: 100%; margin: 0px; height: auto !important;"  height="auto" border="0" opacity="" title="" alt=""/></section>';
            foreach($eventsImg as $item){
                $img = Yii::$app->upload->domain . $item;
                $content = $content . $img_start . $img . $img_end;
            }
        }
        return $content;
    }
}