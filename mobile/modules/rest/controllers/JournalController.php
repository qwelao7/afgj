<?php

namespace mobile\modules\rest\controllers;

use Yii;
use mobile\components\ActiveController;
use common\models\ar\message\Message;
use common\models\ar\message\MessageArticle;
use common\models\ar\fang\FangLoupan;
use yii\db\Query;
/**
 * 成长日志
 * Class LoupanJournalController
 * @package mobile\modules\rest\controllers
 */
class JournalController extends ActiveController {

    /**
     * 楼盘列表
     * @param int $id 楼盘ID
     * @return \mobile\components\type
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionLoupan($id=0) {
        $query= FangLoupan::find()->select(['id','name','thumbnail','address','avg_price','tag'])
                            ->orderBy(['sort'=>SORT_ASC])->where(['<', 'status', '4']);
        if($id>0)  $query->andWhere(['id'=>$id]);
        $data = $query ->asArray()->all();
        if ($data) {
            foreach ($data as $k=>$v) {
                $data[$k]['tag'] = explode(',', $v['tag']);
                $journalCount = Message::find()->where(['loupan_id'=>$v['id'], 'valid'=>1, 'message_type'=>3,'publish_status'=>2])->count();
                if(empty($journalCount)) {
                    unset( $data[$k]);
                }
            }
        }
        //各个楼盘
        $result = FangLoupan::find()->where(['<', 'status', '4'])->select(['name', 'id'])->asArray()->all();
        $data = array('list'=>$data, 'title'=>$result);
        return $this->renderRest($data);
    }

    /**
     * 楼盘成长日志(v2)
     *  所有楼盘
     *  @return \mobile\components\type
     **/
    public function actionLoupanList() {
        $query= FangLoupan::find()->select(['id','name','thumbnail','address','avg_price','tag'])
            ->orderBy(['sort'=>SORT_ASC])->where(['<', 'status', '4']);
        $data = $query ->all();
        if ($data) {
            foreach ($data as $k=>$v) {
                $data[$k]['tag'] = explode(',', $v['tag']);
                $journalCount = Message::find()->where(['loupan_id'=>$v['id'], 'valid'=>1, 'message_type'=>3,'publish_status'=>2])->count();
                if(empty($journalCount)) {
                    unset( $data[$k]);
                }
            }
        }
        return $this->renderRest($data);
    }

    /**
     * 楼盘装修日志
     * @param $id 楼盘ID
     * @return \mobile\components\type
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionDetail($id) {
        $now = f_date(time());
        $msgList = Message::find()->select(['id','title','content','publish_time','attachment_content','attachment_type'])
            ->where(['loupan_id'=>$id, 'valid'=>1, 'message_type'=>3,'publish_status'=>2])
            ->andWhere(['<', 'publish_time', $now])
            ->orderBy(['publish_time'=>SORT_DESC])->asArray()->all();

        if($msgList) {
            foreach($msgList as $key=>$value) {
                //$msgList[$key]['content'] = f_sub($value['content'],50);
                $msgList[$key]['publish_time'] = f_sub($value['publish_time'],5,'');
                if($value['attachment_type'] == 1){
                    $msgList[$key]['attachment_content'] = explode(",", $value['attachment_content']);
                }else {
                    $msgList[$key]['attachment_content'] ="";
                }
            };
        }

        $loupanInfo = FangLoupan::find()->select(['id','name','thumbnail','avg_price','tag','address'])->where(['<', 'status', '4'])->andWhere(['id'=>$id])->asArray()->one();

        if ($loupanInfo) {
            $loupanInfo['tag'] = explode(',', $loupanInfo['tag']);
        }
        $result = array('msgList'=>$msgList,'loupanInfo'=>$loupanInfo);

        return $this->renderRest($result);
    }

    /**
     * 装修日志
     * @param $id 用户房产编号
     * @return \mobile\components\type
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionDecorate($id) {
        $now = f_date(time());
        $msgList = Message::find()->select(['id','title','content','publish_time','attachment_content','attachment_type'])
            ->where(['address_id'=>$id, 'valid'=>1, 'message_type'=>5,'publish_status'=>2])
            ->andWhere(['<', 'publish_time', $now])
            ->orderBy(['publish_time'=>SORT_DESC])->asArray()->all();

        if($msgList) {
            foreach($msgList as $key=>$value) {
                $msgList[$key]['publish_time'] = f_sub($value['publish_time'],5,'');
                if($value['attachment_type'] == 1){
                    $msgList[$key]['attachment_content'] = explode(",", $value['attachment_content']);
                }else {
                    $msgList[$key]['attachment_content'] ="";
                }
            };
        }

        $houseInfo = (new Query())->select("title,coverpic,budget,area,room_type,id")->from("message_decorate")->where(['address_id'=>$id])->one();
        if($houseInfo){
            $room_type_list =['','一居室','二居室','三居室','四居室','五居室及以上'];
            $houseInfo['room']=$room_type_list[$houseInfo['room_type']];
        }
        $result = array('msgList'=>$msgList,'houseInfo'=>$houseInfo);

        return $this->renderRest($result);
    }

    /**
     * 加载装修日志的富文本
     * @param $decorateId 装修日志id
     */
    public function actionJournalRichText($decorateId){
        $data = (new Query())->from('message_decorate')->where('id='.$decorateId)->one();
        return $this->renderRest($data);
    }

    /**
     * 装修日志列表
     * @param $type 存在-筛选推荐关注 不存在-全部
     */
    public function actionJournalList($type) {
        $limit = ($type)?2:null;
        $room_type_list =['','一居','二居','三居','四居','五居及以上'];
        $data = (new Query())->select("id, title, coverpic, budget, area, room_type, is_recommend, updated_at, address_id")->from("message_decorate")->where(["valid"=>1])->andFilterWhere(['is_recommend'=>$type])->orderBy(["updated_at"=>SORT_DESC])->limit($limit)->all();
        foreach($data as $key=>$value) {
            $data[$key]['room'] = $room_type_list[$value['room_type']];
            $data[$key]['loupan'] = (new Query())->select('t1.name')->from('fang_loupan as t1')->leftJoin('account_address as t2', 't1.id=t2.loupan_id')->where(['t2.id'=>$value['address_id']])->one();
        }
        return $this->renderRest($data);
    }
}