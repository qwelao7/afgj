<?php

namespace common\models\ar\user;

use common\models\ecs\EcsUsers;
use Yii;
use common\models\ar\user\Account;
use common\models\ar\user\AccountAddress;
use common\models\ar\admin\Admin;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "account_friend".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $friend_id
 * @property integer $status
 * @property string $message
 * @property string $created_at
 * @property string $updated_at
 */
class AccountFriend extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'account_friend';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'friend_id'], 'required'],
            [['account_id', 'friend_id', 'status', 'display_order'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['message','first_letter','remark_name'], 'string', 'max' => 100],
//            ['first_letter', 'default', 'value' => function($model){
//                $first_letter="#";
//                if($model->friend_id){
//                    $nickname = Account::find()->select('nickname')->where(['id'=>$model->friend_id])->scalar();
//                    if($nickname){
//                        list($first_letter,$assiiVal) = f_firstLetter($nickname);
//                        $model->display_order = $assiiVal;
//                    }
//                }
//                return $first_letter;
//            }],
//            ['display_order', 'default', 'value' => function($model){
//                $display_order="0";
//                if($model->friend_id){
//                    $nickname = Account::find()->select('nickname')->where(['id'=>$model->friend_id])->scalar();
//                    $result = f_firstLetter($nickname);
//                }
//                return $display_order;
//            }],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'first_letter' => '好友昵称首字母',
            'display_order' => '好友姓氏的unicode',
            'account_id' => '用户编号',
            'friend_id' => '好友编号',
            'remark_name' => '备注名',
            'status' => '交友状态：1待验证，2好友，3屏蔽 4关注 5 取消关注',
            'message' => 'account向friend打招呼的信息',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * friend_id字段关联account表
     * @return ActiveQuery
     */
    public function getFriendAccount(){
        return $this->hasOne(Account::className(), ['id'=>'friend_id']);
    }

    /**
     * account_id字段关联account表
     * @return ActiveQuery
     */
    public function getAccountAccount(){
        return $this->hasOne(Account::className(), ['id'=>'account_id']);
    }

    /**
     * 查询某个用户在某个小区里有多少个好友
     * @param integer $loupanID
     */
    public static function numByLoupan($accountID, $loupanID){
        return static::find()->join('INNER JOIN', 'account_address', 'account_address.account_id=account_friend.friend_id')
                    ->where(['account_friend.status'=>2, 'account_friend.account_id'=>(int)$accountID])
                    ->andWhere(['account_address.loupan_id'=>(int)$loupanID, 'account_address.valid'=>1])
                    ->count('1');
    }

    /**
    *   查询两个用户是否为好友
    */
    public static function isFriend($accountID, $anotherID) {
        $data = AccountFriend::find()->where(['account_id'=>$accountID])
                                    ->andWhere(['friend_id'=>$anotherID, 'status'=>2])
                                    ->count();
        $data = (bool)$data;
        if($accountID == $anotherID) $data = true;
        return $data;
    }

    /**
     * 获取某个用户的打招呼列表
     * @param integer $acccountID
     */
    public static function sayHiListByAccountID($acccountID){
        return AccountFriend::find()
            ->select('account_friend.id,account_friend.account_id AS fromID, account_friend.message, account_friend.created_at,("sayHi") msgType')
            ->addSelect('account.nickname, account.avatar')
            ->joinWith('accountAccount', FALSE, 'INNER JOIN')
            ->where('account_friend.friend_id='.(int)$acccountID.' AND account_friend.status=1')
            ->asArray()
            ->all();
    }

    /** 用户信息
        房产是否认证
        当前用户与新鲜事发布者是否为好友
    */
    public static function infoByVaildFriend($data, $vaild, $name, $byId, $single){
        if($single) $data = [$data];
        foreach($data as $key=>$value) {
            if($value[$byId]) {
                //社区新鲜事用户信息s
                if($vaild == 'true') {
                    $data[$key][$name] = Account::getAccountInfo($value[$byId]);
                    $data[$key]['isFriend'] = AccountFriend::isFriend(Yii::$app->user->id,$value[$byId]);
                } else {
                    $nickname = AccountAddress::concatNickname($value[$byId],2);
                    $data[$key][$name]['nickname'] = "业主".$nickname;
                    $data[$key][$name]['avatar'] = Account::getAvatar('');
                }
            }else {
                //社区新鲜事管理员信息
                $data[$key][$name] = Admin::getAdminInfo($value['admin_id']);
            }
        };
        if($single)return reset($data);
        return $data;
    }

    /**
     * 用户关注
     * @param $userId 用户ID
     * @param $followId 关注者ID
     * @return bool|string
     * @author zend.wang
     * @date  2016-08-06 13:00
     */
    public static function follow($userId, $followId) {

        $count = AccountFriend::find()->where(['account_id'=>$userId])->andWhere(['friend_id'=>$followId, 'status'=>4])->count();
        if($count) {
           return true;
        }

        $result = AccountFriend::find()->where(['account_id'=>$userId])->andWhere(['friend_id'=>$followId,'status'=>5])->one();
        if($result) {
            $result->status = 4;
            $result->save();
            return $result->save() ? $result->id : false;
        }else {
            $model = new AccountFriend();
            $model->account_id = $userId;
            $model->friend_id = $followId;
            $user = EcsUsers::getUser($followId);
            $nickname = $user['nickname'];
            if($nickname){
                list($first_letter,$display_order) = f_firstLetter($nickname);
                $model->display_order = $display_order;
                $model->first_letter = $first_letter;
            }
            $model->status = 4;

            return $model->save() ? $model->id : false;
        }
    }
    /**
     * 取消关注
     * @param $userId 用户ID
     * @param $followId 关注者ID
     * @return bool|string
     * @author zend.wang
     * @date  2016-08-06 13:00
     */
    public static function unfollow($userId, $followId) {

        $result = false;
        $model = AccountFriend::find()->where(['account_id'=>$userId])->andWhere(['friend_id'=>$followId, 'status'=>4])->one();
        if($model) {
            $model->status = 5;
            $result = $model->save();
        }
        return $result;
    }


    /**
     * 查询关注状态
     * @param $userId 用户ID
     * @param $followId 关注者ID
     * @return bool|string
     * @status 0/未关注 1/已关注 2/相互关注
     * @author zend.wang
     * @date  2016-08-06 13:00
     */
    public static function followStatus($userId, $followId) {

        $result = 0;
        $follow = AccountFriend::find()->where(['account_id'=>$userId])->andWhere(['friend_id'=>$followId, 'status'=>4])->one();
        $followed = AccountFriend::find()->where(['account_id'=>$followId])->andWhere(['friend_id'=>$userId, 'status'=>4])->one();
        if($follow && $followed)
        {
            $result = 2;
        }
        if($follow && !$followed)
        {
            $result = 1;
        }
        return $result;
    }

    public static function getHouseInfo($userId,$communityIds=[]) {
        $desc = (new Query())->select(['address_desc'])
            ->from("hll_user_address")
            ->where(['account_id'=>$userId,'valid'=>1,'owner_auth'=>1])->andFilterWhere(['community_id'=>$communityIds])->scalar();
        return empty($desc) ? "" : $desc;
    }
    /**
     * 获取用户所有社区邻居信息
     * @param $userId
     * @author zend.wang
     * @date  2016-08-08 13:00
     */
    public static function getNeighboursByUser($userId,$communityIds) {
        $result = [];
        $list = (new Query())->select(['t1.first_letter','t1.friend_id','t2.nickname','t1.remark_name','t2.headimgurl as avatar','t3.user_name'])
                    ->from("account_friend as t1")
                    ->leftJoin("ecs_wechat_user as t2","t2.ect_uid = t1.friend_id")
                    ->leftJoin("ecs_users as t3","t3.user_id = t1.friend_id")
                    ->where(['t1.account_id'=>$userId,'t1.status'=>4])
                    ->orderBy('t1.first_letter ASC,t1.display_order ASC')->all();

        if($list) {
            foreach ($list as $item) {
                $group = $item['first_letter'];
                if($item['remark_name']) {
                    $item['nickname'] = $item['remark_name'];
                } else if(!$item['nickname'] && $item['user_name']) {
                    $item['nickname'] = $item['user_name'];
                }
                unset($item['first_letter']);
                unset($item['user_name']);
                unset($item['remark_name']);
                $item['avatar'] = Account::getAvatar($item['avatar']);
                $item['desc'] = static::getHouseInfo($item['friend_id'],$communityIds);
                $result[$group][] = $item;
            }
            if(ArrayHelper::keyExists('#',$result)) {
                $lastItem = array_shift($result);
                $result['#'] = $lastItem;
            }
        }
        return $result;
    }
}
