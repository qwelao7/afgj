<?php

namespace common\models\ar\user;

use common\models\ar\admin\Admin;
use Yii;
use common\models\ar\community\CommunityVolunteer;
use common\models\ar\user\AccountSkill;
use common\models\ar\user\AccountAddress;
use common\models\ar\fang\FangHouse;
use common\models\ar\system\QrCode;


/**
 * This is the model class for table "account".
 *
 * @property string $id
 * @property string $login_name
 * @property string $password
 * @property string $password_reset_token
 * @property string $primary_mobile
 * @property string $primary_email
 * @property string $weixin_code
 * @property string $full_name
 * @property integer $sex
 * @property string $identity_card_id
 * @property string $identity_card_type
 * @property string $avatar
 * @property integer $promoter_id
 * @property string $status
 * @property string $new_message_num
 * @property string $created_at
 * @property string $updated_at
 * @property string $description
 */
class Account extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'account';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['status', 'promoter_id','admin_id', 'new_message_num'], 'integer'],
            [['identity_card_type', 'description'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['password', 'identity_card_id'], 'string', 'max' => 64],
            [['login_name'], 'string', 'max' => 200],
            [['password_reset_token', 'avatar'], 'string', 'max' => 255],
            [['primary_mobile'], 'string', 'max' => 20],
            [['primary_email', 'full_name', 'nickname'], 'string', 'max' => 50],
            [['weixin_code'], 'string', 'max' => 32],
            [['weixin_code'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'login_name' => '用户名',
            'nickname' => '昵称',
            'password' => '密码',
            'primary_mobile' => '手机号',
            'primary_email' => '邮箱',
            'weixin_code' => '微信号',
            'full_name' => '真实姓名',
            'sex' => '性别',
            'identity_card_id' => '该账户所有人身份证件号码',
            'identity_card_type' => '该账户所有人身份证件类型，只支持身份证和护照',
            'avatar' => 'Avatar',
            'promoter_id' => '推荐人主账户ID',
            'admin_id' => '管家ID',
            'status' => '状态',
            'created_at' => '注册时间',
            'description' => 'Description',
        ];
    }


    /*
     * status字段所有的状态
     */
    public static $status = [
        1 => ['name'=>'正常'],
        2 => ['name'=>'冻结'],
    ];

    /**
     * 关联默认地址
     * @return type
     */
    public function getAddress() {
        return $this->hasOne(AccountAddress::className(), ['account_id' => 'id'])->onCondition(['is_default'=>'yes']);
    }

    /**
     * 关联所有地址
     * @return type
     */
    public function getAllAddress() {
        return $this->hasMany(AccountAddress::className(), ['account_id' => 'id']);
    }

    /**
     * 关联account_skill
     */
    public function getSkills(){
        return $this->hasMany(AccountSkill::className(), ['account_id'=>'id']);
    }

    /**
     * 绑定用户手机号码
     * @param $uid 用户ID
     * @param $phone 用户申请绑定的手机号
     * @return bool ture|false 绑定成功返回true,否则返回false
     * @author zend.wang
     * @date  2016-06-08 15:30
     */
    public static function bindMobile($uid, $phone) {
        /* 检查该手机是否已经存在，若存在将其更新为空 */
        static::updateAll(['primary_mobile'=>''],'primary_mobile = :phone',[':phone'=>$phone]);

        $model = static::findOne(['id'=>$uid,'status'=>1]);
        if (!$model) {
            return FALSE;
        }

        /* 更新管家ID */
        $admin_id = Admin::find()->select('id')->where(['valid'=>1,'cellphone'=>$phone])->scalar();
        if ($admin_id) {
            $model->admin_id = $admin_id;
        }
        $model->primary_mobile = $phone;
        if (!$model->save()) {
            return FALSE;
        }
        if ($uid == Yii::$app->user->id) {
            Yii::$app->user->identity->primary_mobile = $phone;
        }
        return TRUE;
    }

    /**
     * 用户关注公众号或者扫描二维码时，帮用户添加搜藏
     * @param unknown $qrcodeID
     */
    public static function addFavorByQrcodeID($qrcodeID, $accountID){
        $qrcodeID = (int)$qrcodeID;
        $accountID = (int)$accountID;
        $qrcode = QrCode::findOne($qrcodeID);
        if(is_null($qrcode)){
            Yii::warning('用户关注公众号或者扫描二维码时，帮用户添加搜藏：二维码记录不存在');
            return FALSE;
        }
        /* 查询用户是否已经收藏过 */
        $accountFavor = AccountFavor::findOne(['account_id' => $accountID, 'item_type' => $qrcode->item_type, 'item_id' => $qrcode->item_id]);
        if(!is_null($accountFavor))return TRUE;
        $accountFavor = new AccountFavor();
        $accountFavor->account_id = $accountID;
        $accountFavor->item_type=$qrcode->item_type;//楼盘
        $accountFavor->item_id = $qrcode->item_id;
        return $accountFavor->save();
    }

    /**
     * 通过微信基础接口 添加用户
     * @param $wxData
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function addByWxBase($wxData) {
        $user = new static;
        //关注公众号的用户 获取用户的公众号信息
        $rsp = Yii::$app->wx->getUserInfo($wxData['openid']);
        Yii::warning('关注公众号的用户 获取用户的公众号信息:'.print_r($rsp, TRUE));
        if (isset($rsp['subscribe']) && $rsp['subscribe']==1) {
            $user->nickname = $rsp['nickname'];
            $user->weixin_code = $wxData['openid'];
            $user->status = 1;
            $avatar = Yii::$app->upload->saveImgToUrl($rsp['headimgurl'], 'avatar');
            if ($avatar) {
                $user->avatar = $avatar['path'];
            }
            if ($user->save()) {
                $house = FangHouse::addFangHouse();
                $address = AccountAddress::addAccountAddress($house->id, $user, $house->building_num, $house->unit_num, $house->house_num);
                return $user;
            } else {
                Yii::warning('用户添加失败:'.print_r($user->getErrors(), TRUE));
                $user = static::find()->where(['weixin_code'=>$wxData['openid']])->one();
                if ($user) {
                    return $user;
                }
            }
        }
        return false;
    }

    /* 通过account_id获取头像、昵称 */
    public static function getAccountInfo($accountId) {
        if($accountId) {
            $data = Account::find()->where(['id'=>$accountId])->select(['avatar', 'nickname', 'sex','full_name', 'primary_mobile','admin_id'])->one();
            $data['avatar'] = static::getAvatar($data['avatar']);
            return $data;
        }else {
            return false;
        }
    }

    /* 通过volunteer_id获取业工信息 */
    public static function getVolunteerInfo($volunteerId) {
        $data =  Account::find()->join('INNER JOIN', 'community_volunteer', 'account.id = community_volunteer.account_id')
                              ->where(['community_volunteer.id'=>$volunteerId])
                              ->select(['nickname', 'avatar'])
                              ->one();
        $data['avatar'] = static::getAvatar($data['avatar']);
        return $data;
    }

    /**
     * 默认头像
     * @param string $avatar
     * @return string
     */
    public static function getAvatar($avatar) {
        return empty($avatar) ? Yii::$app->params['userDefaultAvatar'] : (string)$avatar;
    }

    /**
    *   根据house_id获取房主信息
    */
    public static function userInfoByHouseID($houseID) {
        $data = Account::find()->join('LEFT JOIN', 'account_address', 'account_address.account_id = account.id')
                                ->where(['account_address.house_id'=>$houseID])
                                ->andWhere(['account_address.valid'=>1, 'account_address.owner_auth'=>1, 'account.status'=>1])
                                ->select('account.nickname, account.avatar, account.primary_mobile')
                                ->all();
        foreach($data as $key=>$value) {
            $data[$key]['avatar'] = Account::getAvatar($value['avatar']);
        }
        return $data;
    }

    /**
     * 邀请朋友
     * @param $fromUserId 邀请人
     * @param $toUserId 被邀请人
     */
    public static function buildInviteRelation($fromUserId,$toUserId) {
        $activityId = f_params('invite_award_activity_id');
        $result = AccountInvite::getAwardByInvitation($fromUserId, $toUserId, $activityId);
        return $result;
    }
}
