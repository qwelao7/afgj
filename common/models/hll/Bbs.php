<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\models\ar\fang\FangLoupan;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_bbs".
 *
 * @property integer $id
 * @property integer $loupan_id
 * @property integer $bbs_name
 * @property integer $thumbnail
 * @property integer $is_main
 * @property string $announcement
 * @property integer $join_way
 * @property integer $allow_del
 * @property integer $link_qq
 * @property integer $qq_qrcode
 * @property integer $link_weixin
 * @property integer $weixin_group_master
 * @property integer $user_num
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class Bbs extends ActiveRecord
 {
    //加入方式
    public static $join_type = [
        'type' => [1,2,3],
        'name' => ['任何人可加入','需版主批准','需版主邀请'],
    ];
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'hll_bbs';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['loupan_id', 'is_main', 'join_way', 'allow_del', 'user_num', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['bbs_name', 'thumbnail'], 'string', 'max' => 50],
            [['announcement'], 'string', 'max' => 200],
            [['qq_qrcode'], 'string', 'max' => 255],
            [['link_weixin', 'weixin_group_master','link_qq'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'loupan_id' => 'Loupan ID',
            'bbs_name' => 'Bbs Name',
            'thumbnail' => 'Thumbnail',
            'is_main' => 'Is Main',
            'announcement' => 'Announcement',
            'join_way' => 'Join Way',
            'allow_del' => 'Allow Del',
            'link_qq' => 'Link Qq',
            'qq_qrcode' => 'Qq Qrcode',
            'link_weixin' => 'Link Weixin',
            'weixin_group_master' => 'Weixin Qroup Master',
            'user_num' => 'User Num',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    public static function getBbs($bbsId,$fileds=null,$condition=[]) {
        if(!$fileds) {
            $fileds = ['id','loupan_id','bbs_name','thumbnail','is_main', 'announcement',
                'link_qq','qq_qrcode','link_weixin','weixin_group_master','allow_del'];
        }
        $bbs = (new Query())->select($fileds)
            ->from("hll_bbs")
            ->where(['id'=>$bbsId,'valid'=>1])
            ->andFilterWhere($condition)
            ->one();
        return $bbs;
    }

    public static function joinBbs($bbs,$userId,$fromUserId=0) {
        $result['state'] =false;
        $result['msg'] ='';
        $bbsUser = HllBbsUser::findOne(['bbs_id'=>$bbs['id'],'account_id'=>$userId,'valid'=>1]);
        //已加入
        if($bbsUser) {
            if($bbsUser->status == 4) {
                $result['msg'] ="该用户已加入黑名单,加入失败";
                return $result;
            }
            $result['msg'] ="该用户已加入,无需重复操作";
            return $result;
        }

        $val = HllBbsUser::findOne(['bbs_id'=>$bbs['id'],'account_id'=>$userId,'valid'=>0]);
        if(!$val) {
            //新建bbs用户
            $bbsUser = new HllBbsUser();
            $bbsUser->bbs_id = $bbs['id'];
            $bbsUser->account_id = $userId;
            $bbsUser->user_role = 3;
            // 加入方式：1任何人可加入，2需管理员批准，3需邀请，4不允许加入 状态：1待审核，2正常，3禁言，4拉黑
            if($bbs['join_way'] == 1) {
                $bbsUser->status = 2;
            }else  if($bbs['join_way'] == 2) {
                $bbsUser->status = 1;
            }else if($bbs['join_way'] == 3) {
                //To-do
            }
            $result['state'] = $bbsUser->save();
            if($result['state']) {
                $bbsModel = Bbs::findOne($bbs['id']);
                $bbsModel->user_num = $bbsModel->user_num +1;
                $bbsModel->save();
            } else {
                $result['msg'] ='加入失败';
            }
            return $result;
        }else {
            //更新之前退出的
            $val->valid = 1;
            if($val->save()) {
                $result['state'] = true;
                $bbsModel = Bbs::findOne($bbs['id']);
                $bbsModel->user_num = $bbsModel->user_num +1;
                $bbsModel->save();
            }else {
                $result['msg'] = '加入失败';
            }
            return $result;
        }

    }

    public static function quitBbs($bbsId,$userId) {
        $bbsUserModel = HllBbsUser::findOne(['bbs_id'=>$bbsId,'account_id'=>$userId,'valid'=>1]);
        if($bbsUserModel) {
            $bbsUserModel->valid = 0;
            if($bbsUserModel->save()) {
                $bbsModel = Bbs::findOne($bbsId);
                $bbsModel->user_num = $bbsModel->user_num -1;
                return $bbsModel->save();
            }
        }
        return false;
    }

    public static function ChangeBbs($bbsId,$userId,$type) {
        $bbsUserModel = HllBbsUser::findOne(['bbs_id'=>$bbsId,'account_id'=>$userId,'valid'=>1]);
        if($bbsUserModel) {
            if($type == 1){
                $bbsUserModel->status = 2;
            }
            if($type == 2){
                $bbsUserModel->user_role = 2;
            }
            if($type == 3){
                $bbsUserModel->user_role = 3;
            }
            if($bbsUserModel->save()) {
                return true;
            }
        }
        return false;
    }

    public static function Destory($bbsId){
        $bbs = Bbs::findOne($bbsId);
        $bbs->valid = 0;
        if($bbs->save()){
            return true;
        }
        return false;
    }

    public static function bbsList($loupanId,$userId=null) {
        $data = (new Query())->select(['id','bbs_name', 'thumbnail', 'announcement',
            'join_way', 'user_num','link_qq','qq_qrcode','link_weixin','weixin_group_master'])
            ->from('hll_bbs')
            ->where(['loupan_id'=>$loupanId, 'valid'=>1])
            ->orderBy(['is_main'=>SORT_DESC, 'user_num'=>SORT_DESC])
            ->distinct()->all();

        return $data;
    }

    public static function followState($userId, $bbsId) {
        $result = [];

        $data = (new Query())->select(['status','user_role'])->from('hll_bbs_user')->where(['account_id'=>$userId, 'bbs_id'=>$bbsId, 'valid'=>1])->one();
        if($data) {
            //拉黑
            $result['status'] = ($data['status'] == 4)?2:1;
            // 成员权限
            $result['admin_level'] = $data['user_role'];
        }

        return $result;
    }

    public static function createMainBbsOfLoupan($communityId) {

        $communityObj = Community::findOne($communityId);
        if($communityObj) {
            $bbs = new Bbs();
            $bbs->loupan_id = $communityId;
            $bbs->bbs_name = $communityObj->name;
            $bbs->thumbnail = $communityObj->thumbnail;
            $bbs->is_main = 1;
            if($bbs->save(false)){
                 return $bbs;
            }
        }
        return false;
    }

    public static function getMessage($data){
        foreach($data as &$item){
            $result = (new Query())->select(['title','created_at'])
                ->from('message')->where(['loupan_id'=>$item['id'],'valid'=>1,'message_type'=>1])
                ->orderBy(['created_at'=>SORT_DESC])->one();
            if($result){
                $item['title'] = $result['title'];
                $item['created_at'] = strtotime($result['created_at']);
            }
        }
        return $data;
    }

    public static function getBbsIdByCommunity($community) {
        $bbs = Bbs::find()->select('id')->where(['loupan_id'=>$community, 'is_main'=>1, 'valid'=>1])->scalar();
        return $bbs;
    }
}
