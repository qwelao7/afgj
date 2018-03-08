<?php
namespace mobile\modules\rest\controllers;

use backend\models\fang\FangLoupanSearch;
use Yii;
use mobile\components\ActiveController;
use common\models\ar\message\Message;
use common\models\ar\user\Account;
use common\models\ar\community\CommunityHelp;
use common\models\ar\community\CommunityHelpReply;
use common\models\ar\community\CommunityVolunteer;
use common\models\ar\community\CommunityAdmin;
use common\models\ar\user\AccountAddress;
use common\models\ar\fang\FangHouse;
use common\models\ar\user\AccountSkill;
use common\models\ar\admin\Admin;
use common\components\Util;
use common\models\ar\user\AccountFriend;
use common\models\ar\fang\FangLoupan;
use common\models\ar\message\MessageChat;
use yii\db\Query;
use yii\web\BadRequestHttpException;
use common\components\WxTmplMsg;

class CommunityController extends ActiveController {

    /**
     * 社群首页
     * @param unknown $id
     */
    public function actionCommunityList($id) {
        //用户住房所在楼盘
        $title = FangLoupan::getLoupansByAccountID(Yii::$app->user->id,Yii::$app->user->identity->admin_id);

        if(!$title)return $this->renderRest(['title'=>[]]);
        if(!$id) $id = array_keys($title)[0];

        //当前楼盘
        $curLoupan = $title[$id];

        /* 很高兴认识你 */
        //小区业主数量
        $neighborNum = AccountAddress::numByLoupan($id, Yii::$app->user->id);
        //当前登录用户在当前小区的好友数
        $myFriendNum = AccountFriend::numByLoupan(Yii::$app->user->id, $id);

        /* 园区新鲜事 */
        $news = Message::newsNumByLoupan($id);

        /* 业工帮帮忙 */
        $help = CommunityHelp::numByloupan($id);

        return $this->renderRest([
            'title'=>$title,
            'curLoupan'=>$curLoupan,
            'msgCounts'=>Yii::$app->user->identity->new_message_num,
            'neighbor' => ['neighborNum'=>$neighborNum, 'myFriendNum'=>$myFriendNum],
            'news' => $news,
            'help' => $help,
        ]);
    }

    /**
     * 很高兴认识你
     * @param integer $id
     */
    public function actionNeighbors($loupanID, $page){
        $limit = 20;
        $offset = ($page-1) * $limit;
        $loupanID = intval($loupanID);
        $adminId = Yii::$app->user->identity->admin_id;
        $isCurrLoupanAdmin = false;
        $myself =[];
        $curUserInfo = null;
        $vaild = false;
        /* 楼盘信息 */
        $loupans = FangLoupan::getLoupansByAccountID(Yii::$app->user->id,$adminId);
        /* 管家 */
        $admins = Admin::find()
            ->join('INNER JOIN','community_admin','community_admin.admin_id=admin.id')
            ->select(['admin.id', 'admin.avatar', 'admin.nickname'])
            ->where('community_admin.loupan_id='.$loupanID)
            ->indexBy("id")
            ->asArray()
            ->all();
        if(!empty($admins[$adminId])){
            $isCurrLoupanAdmin = true;
            unset($admins[$adminId]);
        }
        /* 自己 */
        if(!$isCurrLoupanAdmin ) {
            //用户在该楼盘是否有一套验证过的房子
            $vaild = AccountAddress::ownerAuthNum($loupanID)>0;
            $myskill = AccountSkill::find()->where(['account_id' => Yii::$app->user->id])->select(['skill', 'account_id'])->asArray()->all();
            if($myskill){
                $myskill = Util::groupBy('account_id', $myskill, 'skill');
                $myself['skill'] = array_keys((array)$myskill[Yii::$app->user->id]);
                $myself['skill'] = array_slice($myself['skill'], 0, 4);
            }
            $address =(new Query())->select("t2.building_num,t2.unit_num,t2.house_num")
                ->from("account_address as t1")
                ->leftJoin("fang_house as t2","t2.id = t1.house_id")
                ->where(['t1.valid'=>1,'t1.owner_auth'=>1,'t1.account_id' => Yii::$app->user->id,'t1.loupan_id'=>$loupanID])
                ->all();
            if($address){
                foreach ($address as $key => $value) {
                    $address[$key] = join('', FangHouse::joinUnit($value, TRUE));
                };
                $myself['address'] = $address;
            }

            /* 当前登录用户楼栋户号信息 */
            $curUserInfo = (new Query())->select("t3.building_num,t3.unit_num,t3.house_num")
                            ->addSelect(['CONCAT("的",t2.nickname) AS nickname'])
                            ->from("account_address as t1")
                            ->leftJoin("account as t2",'t1.account_id=t2.id')
                            ->leftJoin("fang_house as t3",'t1.house_id=t3.id')
                            ->where('t1.account_id='.Yii::$app->user->id.' AND t1.loupan_id='.$loupanID.' AND  t1.valid=1 AND t3.valid=1')
                            ->orderBy('t1.is_default,t1.id')
                            ->one();
            $curUserInfo && $curUserInfo = join('', FangHouse::joinUnit($curUserInfo, TRUE));
        }
        /* 用户列表 */
        $list = AccountAddress::find()
                        ->join('RIGHT JOIN', 'fang_house', 'account_address.house_id=fang_house.id')
                        ->join('INNER JOIN', 'account', 'account_address.account_id=account.id')
                        ->join('LEFT JOIN', 'community_volunteer', 'account_address.loupan_id=community_volunteer.loupan_id AND account_address.account_id=community_volunteer.account_id')
                        ->select('account_address.id,account_address.account_id,account_address.house_id,account.avatar, account_address.loupan_id')
                        ->addSelect('account.nickname,account.sex,account.full_name')
                        ->addSelect('fang_house.building_num,fang_house.unit_num,fang_house.house_num,fang_house.group_name')
                        ->addSelect('community_volunteer.account_id AS volunteerID,community_volunteer.declaration')
                        ->where('account_address.loupan_id='.$loupanID.' AND account_address.valid=1 AND account_address.owner_auth=1 AND account_address.account_id !='.Yii::$app->user->id)
                        ->orderBy('length(fang_house.building_num),fang_house.building_num,length(fang_house.unit_num),fang_house.unit_num, length(fang_house.house_num),fang_house.house_num')
                        ->offset($offset)->limit($limit)
                        ->asArray()
                        ->all();
        $list = static::neighbourList($list, $curUserInfo);
        return $this->renderRest([
            'list' => $list,
            'loupans' => $loupans,
            'admins' => $admins,
            'myself' => $myself,
            'vaild' => $vaild,
            'isCurrLoupanAdmin'=> $isCurrLoupanAdmin
        ]);
    }

    /**
    * 很高兴认识你，搜索功能
    * @param interger $loupanID
    * @param string $searchInfo
    * @param array $list
    */
    public function actionSearchByInfo($loupanID, $searchInfo, $curUserInfo) {
        $list = [];
        $houseNumInfo = null;
        $nickNameInfo = null;
        if(is_numeric($searchInfo)){
            $houseNumInfo = $searchInfo;
            $list = AccountAddress::searchByInfo($loupanID, $houseNumInfo, $nickNameInfo);
        }else {
            $nickNameInfo = $searchInfo;
            $list = AccountAddress::searchByInfo($loupanID, $houseNumInfo, $nickNameInfo);
            if(!$list) {
                $list = AccountAddress::searchBySkill($loupanID, $searchInfo);
            }
        }
        $list = static::neighbourList($list,$curUserInfo);
        return $this->renderRest($list);
    }

    /**
     * 打招呼
     * @param integer $accountID 用户id
     */
    public function actionSayHai($toAccountID, $msg){
        $toAccountID = (int)$toAccountID;
        //检查id为$accountID的用户是否已经向当前登陆用户打过招呼或者是否已经是好友
        $alreadySayHaiToCurUser = AccountFriend::find()
            ->select('id,status')
            ->where("account_id={$toAccountID} AND friend_id=".Yii::$app->user->id)
            ->one();

        $afModel = new AccountFriend;
        $afModel->account_id = Yii::$app->user->id;
        $afModel->friend_id = $toAccountID;
        $afModel->message = addslashes($msg);
        if(!$alreadySayHaiToCurUser){
            $trans = Yii::$app->db->beginTransaction();
            $toAccountModel = Account::findOne($toAccountID);
            $toAccountModel->new_message_num++;
            try{
                if($afModel->save() && $toAccountModel->save()){
                    $trans->commit();
                    //send weixin template message
                    WxTmplMsg::changeAccountRemind($toAccountID,'您收到新的打招呼信息','打招呼',$afModel->message,"/community-sayhi/");
                    return $this->renderRest(['succ' => TRUE, 'msg' => '请等待对方验证', 'status' => 1]);
                }else {
                   throw new BadRequestHttpException("保存失败");
                }
            }catch (\yii\db\Exception $e){
                $trans->rollBack();
                return $this->renderRest(['succ' => FALSE, 'msg' => '系统繁忙，请稍后再试']);
            }
        }else{
            if('2' == $alreadySayHaiToCurUser) return $this->renderRest(['succ' => TRUE]);//如果已经是好友直接返回
            $alreadySayHaiToCurUser->status = 2;
            $afModel->status = 2;
            $trans = Yii::$app->db->beginTransaction();
            try{
                $stat = $alreadySayHaiToCurUser->save(FALSE);
                $afModel->save();
                $trans->commit();
                return $this->renderRest(['succ' => TRUE, 'msg' => 'Ta也向你打招呼了，你们已经成为好友！', 'status' => 2]);
            }catch (yii\db\Exception $e){
                $trans->rollBack();
                return $this->renderRest(['succ' => FALSE, 'msg' => '系统繁忙，请稍后再试']);
            }
        }
    }

    /**
     * 打招呼列表
     */
    public function actionSayHiList($messageType=0){
        /* 清空当前登录用户的new_message_num字段 */
        Yii::$app->user->identity->new_message_num = min(0, Yii::$app->user->identity->new_message_num);
        if(Yii::$app->user->identity->isAttributeChanged('new_message_num', FALSE))Yii::$app->user->identity->save();

        if($messageType && Yii::$app->user->identity->admin_id > 0){
            /*来着发给管家得消息列表*/
            $list = MessageChat::lastMsgToAdmins(Yii::$app->user->identity->admin_id);
        }else {
            /* 打招呼列表 */
            $list = AccountFriend::sayHiListByAccountID(Yii::$app->user->id);

            /* 来自朋友的信息列表 */
            $list = array_merge($list,MessageChat::lastMsgFromFriends(Yii::$app->user->id));

            /* 来自管理员的信息列表 */
            $list = array_merge($list,MessageChat::lastMsgFromAdmins(Yii::$app->user->id));
        }

        foreach($list as &$item){
            $item['avatar'] = Account::getAvatar($item['avatar']);
        }
        $list = util::order('created_at', SORT_DESC, SORT_STRING, $list);
        return $this->renderRest(['list' =>$list,'adminId'=>Yii::$app->user->identity->admin_id]);
    }

    /**
     * 添加好友
     * @param integer $id account_friend表的主键
     */
    public function actionAddFriend($id){
        $hiModel = AccountFriend::findOne($id);
        $hiModel->status = 2;
        $reverseHiModel = new AccountFriend();
        $reverseHiModel->account_id = $hiModel->friend_id;
        $reverseHiModel->friend_id  = $hiModel->account_id;
        $reverseHiModel->status = 2;
        $trans = Yii::$app->db->beginTransaction();
        try{
            $hiModel->save();
            $reverseHiModel->save();
            $trans->commit();
            return $this->renderRest(['succ' => TRUE, 'msg'=>'添加成功']);
        }catch (\yii\db\Exception $e){
            $trans->rollBack();
            return $this->renderRest(['succ'=>FALSE, 'msg'=>'系统繁忙，请稍后再试', 'error'=>$e->getMessage()]);
        }
    }

    /**
    * 社群求助列表
    * @param interger $id
    * @param interger $page
    */
    public function actionCommunityHelpList($id, $page, $type) {
        $limit = 10;
        $offset = ($page-1) * $limit;
        //用户在该楼盘是否有一套验证过的房子
        $vaild = AccountAddress::ownerAuthNum($id);
        $vaild = (bool)$vaild;
        /* 求助信息 */
        if($type=='my') {
            $result = CommunityHelp::find()->where(['loupan_id'=>$id, 'valid'=>1])->andWhere(['account_id'=>Yii::$app->user->id])
                                            ->orderBy(['created_at'=>SORT_DESC])->offset($offset)->limit($limit)->asArray()->all();
        }else if($type=='ask'){
            $result = CommunityHelp::find()->where(['loupan_id'=>$id, 'valid'=>1])->andWhere(['volunteer_id'=>Yii::$app->user->id])
                                            ->orderBy(['status'=>SORT_ASC,'created_at'=>SORT_DESC])->offset($offset)->limit($limit)->asArray()->all();
        }else{
            $result = CommunityHelp::find()->where(['loupan_id'=>$id, 'valid'=>1])
                                            ->orderBy(['created_at'=>SORT_DESC])->offset($offset)->limit($limit)->asArray()->all();
        };
        if($result) {
            //用户信息
            //房产是否认证
            //当前用户与新鲜事发布者是否为好友￥
            $result = AccountFriend::infoByVaildFriend($result, $vaild, 'account_info','account_id',false);
            foreach($result as $key=> $value) {
                $result[$key]['formate_time'] = CommunityHelp::formatTime($value['created_at']);
                $result[$key]['volunteer_info'] = Account::getAccountInfo($value['volunteer_id']);
            };
        };
        $data = array('list'=>$result, 'vaild'=>$vaild);
        return $this->renderRest($data);
    }

    /**
    * 社群求助详情
    * @param interger $id
    */
    public function actionCommunityHelpDetail($id,$vaild) {
        $data = (new Query())->select(['t1.*'])->from('community_help as t1')->where(['t1.valid'=>1, 't1.id'=>$id])->one();
        //用户信息
        //房产是否认证
        //当前用户与新鲜事发布者是否为好友
        $data = AccountFriend::infoByVaildFriend($data, $vaild, 'account_info','account_id',true);
        $data['formate_time'] = CommunityHelp::formatTime($data['created_at']);
        $data['volunteer_info'] = Account::getAccountInfo($data['volunteer_id']);
        if($data['pics']) {
            $data['pics'] = explode(",", $data['pics']);
        }else {
            $data['pics'] = false;
        };
        /* 回复信息 */
        $data['reply'] = CommunityHelpReply::find()->where(['help_id'=>$id, 'valid'=>1])->orderBy(['created_at'=>SORT_DESC])->asArray()->all();
        $data['reply'] = AccountFriend::infoByVaildFriend($data['reply'], $vaild, 'reply_info','creater',false);
        foreach($data['reply'] as $key=>$value) {
            $data['reply'][$key]['created_at'] = date('m-d H:s',strtotime($value['created_at']));
            $data['reply'][$key]['isVolunteer'] = CommunityVolunteer::find()->where(['loupan_id'=>$id, 'account_id'=>$value['creater']])->count();
        };
        if($data['volunteer_id'] == Yii::$app->user->id || $data['account_id'] == Yii::$app->user->id) {
            $data['isShowComplete'] = true;
        }else {
            $data['isShowComplete'] = false;
        };
        return $this->renderRest($data);
    }

    /**
    * 业工详情
    * @param interger $id
    */
    public function actionCreateCommunity($id){
        $data = Account::find()->join('INNER JOIN', 'community_volunteer', 'account.id = community_volunteer.account_id')
                                            ->where(['community_volunteer.loupan_id'=>$id, 'community_volunteer.valid'=>1])
                                            ->orderBy(['community_volunteer.id'=>SORT_ASC])
                                            ->select(['account.avatar', 'account.nickname','account.id'])
                                            ->addSelect(['community_volunteer.declaration'])
                                            ->asArray()->all();
        foreach($data as &$item) {
            $item['avatar'] = Account::getAvatar($item['avatar']);
            $item['deal'] = CommunityHelp::find()->where(['volunteer_id'=>$item['id'], 'status'=>1, 'valid'=>1])->count();
            $item['ask'] = CommunityHelp::find()->where(['volunteer_id'=>$item['id'], 'valid'=>1])->count();
            $item['reply'] = CommunityHelpReply::find()->where(['creater'=>$item['id'], 'valid'=>1])->count();
            $item['skills'] = AccountSkill::find()->where(['account_id'=>$item['id']])->select(['skill'])->all();
            $item['skills'] = array_slice($item['skills'], 0,4);
            }
        return $this->renderRest($data);
    }

    /**
    * 创建求助
    */
    public function actionAddHelp($data) {
        $model = new CommunityHelp;
        $model->account_id = Yii::$app->user->id;
        $model->loupan_id = $data['id'];
        $model->status = 0;
        $model->title = $data['title'];
        $model->content = $data['desc'];
        $model->volunteer_id = $data['volunteer_id'];
        $model->created_at = date('Y-m-d H:i:s',time());
        if($data['imgs']) {
            if(!empty($data['imgs'])) {
                foreach($data['imgs'] as $key=>$value) {
                    $data['imgs'][$key] = substr(parse_url($value,PHP_URL_PATH ), 1);
                };
            };
            $model->pics = implode(',', $data['imgs']);

        }else {
            $model->pics = '';
        }
        $transaction = Yii::$app->db->beginTransaction();
        try{
            if($model->validate() && $model->save()){
                $transaction->commit();
                //send weixin template message
                WxTmplMsg::changeAccountRemind($model->volunteer_id,'您收到新的友邻求助信息','友邻互助',$model->title,"/community-help-detail/{$model->id}/{$model->loupan_id}/all");
            }else{
                throw new BadRequestHttpException("保存失败");
            }
        } catch(\yii\db\Exception $e) {
            $transaction->rollback();
            return $this->renderRestErr('提交失败');
        }
        return $this->renderRest($model);
    }

    /**
    * 更新求助解决情况
    */
    public function actionUpdateStatus($id) {
        $data = CommunityHelp::find()->where(['id'=>$id])->one();
        if($data['status'] == 0) {
            $data['status'] = 1;
        };
        $data->save();
        return $this->renderRest($data);
    }

    /**
    * 新增社区回复
    */
    public function actionAddReply($id,$data) {
        $transaction = Yii::$app->db->beginTransaction();
        try{
            $model = new CommunityHelpReply;
            $model->help_id = $id;
            $model->creater = Yii::$app->user->id;
            $model->content = $data;
            $model->updated_at = date('Y-m-d H:i:s',time());
            $model->save();
            $transaction->commit();
        } catch(\yii\db\Exception $e) {
            $transaction->rollback();
            return $this->renderRestErr('提交失败');
        }
        return $this->renderRest($model);
    }

    /**
     * 发消息
     * @param $msg 消息内容
     * @param int $toAccountID 发送消息给指定业主ID
     * @param int $toAdminID 发送消息给指定管家ID
     * @param bool $isCurrLoupanAdmin  是否当前楼盘业主的管家,默认false
     * @return \mobile\components\type|void
     * @throws \yii\db\Exception
     * @throws \yii\web\BadRequestHttpException
     * @author zend.wang
     * @date  2016-06-21 13:00
     */
    public function actionSendMsg($msg, $toAccountID=0, $toAdminID=0,$isCurrLoupanAdmin=false){
        if('' == $msg || ( 0 == $toAccountID && 0 == $toAdminID ) || ( 0 != $toAccountID && 0 != $toAdminID ))return $this->renderRestErr('参数错误');
        /* 准备数据 */
        $messageChatModel = new MessageChat();
        $messageChatModel->message = $msg;
        if($isCurrLoupanAdmin){
            $messageChatModel->admin_id = Yii::$app->user->identity->admin_id;
        }else {
            $messageChatModel->account_id = Yii::$app->user->id;
        }
        $messageChatModel->to_account_id = $toAccountID;
        $messageChatModel->to_admin_id = $toAdminID;
        if($toAccountID){
            $toAccount = Account::findOne($toAccountID);
            if($toAccount)$toAccount->new_message_num++;
        }

        /* 入库 */
        $trans = Yii::$app->db->beginTransaction();
        try{
            if ($messageChatModel->save()) {
                if($toAccount){
                    $toAccount->save();
                }
                $trans->commit();

                //send weixin template message
                $toAdminID>0 && WxTmplMsg::changeAccountRemind($toAdminID,'您收到新的业主信息','发消息',$msg,"/community-sayhi/");

                return $this->renderRest('发送成功');
            }else {
                throw new BadRequestHttpException("发送失败,请重试");
            }
        }catch (\yii\db\Exception $e){
            $trans->rollBack();
            return $this->renderRestErr('发送失败，请重试');
        }
    }

    /**
     * 很高兴认识你用户列表
     * @param $list 业主信息列表
     * @param $curUserInfo 当前用户的楼栋号信息
     */
    public static function neighbourList($list, $curUserInfo) {
        $isCurrLoupanAdmin = false;
        $vaild = true;
        if ($list) {
            /* 技能 */
            $skills = AccountSkill::find()
                ->select('account_id,skill')
                ->where('account_id IN('.join(',',array_column($list, 'account_id')).')')
                ->asArray()
                ->all();
            $skills = Util::groupBy('account_id', $skills, 'skill');
            /* 好友 */
            $friendStatus = AccountFriend::find()
                ->select('friend_id,status')
                ->where('account_id='.Yii::$app->user->id)
                ->asArray()
                ->indexBy('friend_id')
                ->all();
            foreach($list as &$item){
                $item['vaild'] = $vaild;
                $item['avatar'] = Account::getAvatar($item['avatar']);
                $curUserInfo && $item['curUserInfo'] = $curUserInfo;
                if(isset($skills[$item['account_id']])) $item['skills'] = array_keys($skills[$item['account_id']]);
                if($isCurrLoupanAdmin) {
                    $item['friendStatus'] = 2;
                } else if(isset($friendStatus[$item['account_id']])) {
                    $item['friendStatus'] = $friendStatus[$item['account_id']]['status'];
                }
                //房产是否认证
                if($vaild) {
                    //当前用户与新鲜事发布者是否为好友
                    $item['isFriend'] = AccountFriend::isFriend(Yii::$app->user->id,$item['account_id']);
                } else {
                    $nickname = AccountAddress::concatNickname($item['account_id'], $item['loupan_id']);
                    $item['nickname'] = "业主".$nickname;
                }
            }
        }
        return $list;
    }

    /**
     * 判断用户是否为业工
     * @param $id 楼盘id
     */
    public function actionIsAdmin($id) {
        $data =CommunityVolunteer::find()
            ->where(['account_id'=>Yii::$app->user->id])
            ->andWhere(['loupan_id'=>$id])
            ->count();
        $data = (bool)$data;
        return $this->renderRest($data);
    }

    /**
     * 社区楼盘列表
     */
    public function actionNeighLoupanList() {
        /* 用户住房所在楼盘 */
        $data = FangLoupan::getLoupansByAccountID(Yii::$app->user->id,Yii::$app->user->identity->admin_id);
        return $this->renderRest($data);
    }
}
