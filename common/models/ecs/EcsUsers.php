<?php

namespace common\models\ecs;

use Yii;
use yii\base\NotSupportedException;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;
use yii\db\Query;
/**
 * This is the model class for table "ecs_users".
 *
 * @property integer $user_id
 * @property string $email
 * @property string $user_name
 * @property string $password
 * @property string $real_name
 * @property string $identification
 * @property string $question
 * @property string $answer
 * @property integer $sex
 * @property string $birthday
 * @property string $user_money
 * @property string $frozen_money
 * @property integer $pay_points
 * @property integer $rank_points
 * @property integer $address_id
 * @property integer $reg_time
 * @property integer $last_login
 * @property string $last_time
 * @property string $last_ip
 * @property integer $visit_count
 * @property integer $user_rank
 * @property integer $is_special
 * @property string $ec_salt
 * @property string $salt
 * @property integer $parent_id
 * @property integer $flag
 * @property string $alias
 * @property string $msn
 * @property string $qq
 * @property string $office_phone
 * @property string $home_phone
 * @property string $mobile_phone
 * @property integer $is_validated
 * @property string $credit_line
 * @property string $passwd_question
 * @property string $passwd_answer
 */
class EcsUsers extends \yii\db\ActiveRecord  implements IdentityInterface {

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'ecs_users';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['sex', 'pay_points', 'rank_points', 'address_id', 'reg_time', 'last_login', 'visit_count', 'user_rank', 'is_special', 'parent_id', 'flag', 'is_validated'], 'integer'],
            [['sex','mobile_phone','password'], 'safe'],
            [['user_money', 'frozen_money', 'credit_line'], 'number'],
            //[['alias', 'msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone', 'credit_line'], 'required'],
            [['email', 'user_name', 'alias', 'msn'], 'string', 'max' => 60],
            [['password'], 'string', 'max' => 32],
            [['identification'], 'string', 'max' => 18],
            [['question', 'answer', 'passwd_answer'], 'string', 'max' => 255],
            [['last_ip'], 'string', 'max' => 15],
            [['ec_salt', 'salt'], 'string', 'max' => 10],
            [['qq', 'office_phone', 'home_phone', 'mobile_phone'], 'string', 'max' => 20],
            [['passwd_question','real_name'], 'string', 'max' => 50],
            [['user_name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'user_id' => 'User ID',
            'email' => 'Email',
            'user_name' => 'User Name',
            'password' => 'Password',
            'real_name' => 'Real Name',
            'identification' => 'Identification',
            'question' => 'Question',
            'answer' => 'Answer',
            'sex' => 'Sex',
            'birthday' => 'Birthday',
            'user_money' => 'User Money',
            'frozen_money' => 'Frozen Money',
            'pay_points' => 'Pay Points',
            'rank_points' => 'Rank Points',
            'address_id' => 'Address ID',
            'reg_time' => 'Reg Time',
            'last_login' => 'Last Login',
            'last_time' => 'Last Time',
            'last_ip' => 'Last Ip',
            'visit_count' => 'Visit Count',
            'user_rank' => 'User Rank',
            'is_special' => 'Is Special',
            'ec_salt' => 'Ec Salt',
            'salt' => 'Salt',
            'parent_id' => 'Parent ID',
            'flag' => 'Flag',
            'alias' => 'Alias',
            'msn' => 'Msn',
            'qq' => 'Qq',
            'office_phone' => 'Office Phone',
            'home_phone' => 'Home Phone',
            'mobile_phone' => 'Mobile Phone',
            'is_validated' => 'Is Validated',
            'credit_line' => 'Credit Line',
            'passwd_question' => 'Passwd Question',
            'passwd_answer' => 'Passwd Answer',
        ];
    }
    public static function getUser($userId,$fields=null) {

        if(!$fields) {
            $fields = ['t1.user_id','t1.email','t1.user_name','t1.mobile_phone','t1.sex','t2.wechat_id','t2.subscribe','t2.nickname','t2.headimgurl'];
        }

        $data = (new Query())->select($fields)->from('ecs_users as t1')
            ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.user_id')
            ->where(['t1.user_id'=>$userId])->one();
        if (in_array('t2.headimgurl',$fields)) {
            if(!isset($data['headimgurl'])) {
                $data['headimgurl'] = Yii::$app->params['userDefaultAvatar'];
            }
        }
        return $data;
    }
    public static function getAdmin($adminId,$fields=null) {

        if(!$fields) {
            $fields = ['t1.user_id','t1.email','t1.user_name','t1.headimgurl'];
        }
        $data = (new Query())->select($fields)->from('ecs_admin_user as t1')
            ->where(['t1.user_id'=>$adminId])->one();
        $data['headimgurl'] = static::getAvatar($data['headimgurl']);
        return $data;
    }
    public static function getUserSkills($userId) {
        $fields = ['t1.id','t1.skill'];
        $result = [];
        $list =  (new Query())->select($fields)->from('account_skill as t1')
            ->where(['t1.account_id'=>$userId])->all();
        if($list) {
            $result = ArrayHelper::map($list,'id','skill');
        }
        return $result;
    }
    /**
     * 默认头像
     * @param string $avatar
     * @return string
     */
    public static function getAvatar($avatar) {
        return empty($avatar) ? Yii::$app->params['userDefaultAvatar'] : (string)$avatar;
    }
    public static function findIdentity($id)
    {
        $user = static::findOne(['user_id'=>$id]);
        return $user;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('You are requesting with an invalid credential.');
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        // TODO: Implement getAuthKey() method.
    }

    public function validateAuthKey($authKey)
    {
        // TODO: Implement validateAuthKey() method.
    }
    public static function primaryKey() {
        return ['user_id'];
    }

    /**
     * 获取指定的省市区信息
     * @param $province
     * @param $city
     * @param $district
     * @return array
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public static function getUserRegionDetailName($province,$city,$district) {
        $data = (new Query())->select(['region_name'])
            ->from("ecs_region")
            ->where(["region_id"=>[$province,$city,$district]])->orderBy('region_type ASC')
            ->all();
        if($data && is_array($data)) {
            return ArrayHelper::getColumn($data,'region_name');
        } else {
          return [];
        }
    }
//    /**
//     * 更新用户信息
//     */
//    public static function modifyUserInfo($userId,$data){
//        $user = EcsUsers::find()->where(['user_id'=>$userId])->one();
//        $user->sex = $data['sex'];
//        $user->password = $data['password'];
//        $user->mobile_phone = $data['mobile_phone'];
//        $result = $user->save();
//        return $user;
//    }
    /**
     * 获取当前城市定位信息
     * @return array|bool
     * @author zend.wang
     * @date  2016-10-08 13:00
     */
     public  static  function  getGeoLocationCity() {
         $ip = f_real_ip();
         if(in_array($ip,['0.0.0.0','127.0.0.1'],true)) {
             $result=['region_id'=>220,'region_name'=>'南京'];
         } else {
//             $key = "curr_city_{$ip}";
//             $cache = Yii::$app->cache;
//             $result = $cache->get($key);
//             if($result == false) {
                 $url = "http://ip.taobao.com/service/getIpInfo.php?ip={$ip}";
                 $locationInfo = json_decode(file_get_contents($url)); //调用淘宝接口获取信息
                 if(empty($locationInfo->data->city)){
                     $locationInfo->data->city = '南京市';
                 }
                 $result = (new Query())->select(['region_id','region_name'])
                     ->from("ecs_region")
                     ->where(["region_name"=>str_replace('市','',$locationInfo->data->city)])
                     ->one();
//                 Yii::$app->cache->set($key, $result);
//             }
         }
         return $result;
     }

    /**
     * 注册或绑定用户手机号,
     * 第一次注册用户,需考虑是否是通过其他人分享邀请的。
     * @param $data
     * @return bool|mixed|string
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public static function registerOrBindMobile($data,$id) {
        $user = static::find()->where(['user_id'=>$id])->one();
        if(!$user) {
            $user = new EcsUsers();
        }
        $user->mobile_phone = $data['mobile'];
        if($user->save()){
            return $user->id;
        } else {
            return false;
        }

    }
}
