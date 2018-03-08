<?php
namespace mobile\modules\v2\models;

use Yii;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;
use common\models\ar\user\Account;

/**
 * User model
 *
 * @property string $password write-only password
 */
class User extends Account implements IdentityInterface {

    /**
     * @inheritdoc
     */
    public static function findIdentity($id) {
        return static::find()->where(['id'=>$id,'status'=>'1'])->one();
    }
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $user = false;
        if ($type) {
            $filterClass = substr($type,22);
            switch($filterClass) {
                case "WxQueryParamAuth":
                    $user = static::find()->where(['weixin_code'=>$token])->one();
                    break;
                default:
                    throw new UnauthorizedHttpException('You are requesting with an invalid credential.');
            }
        }

        return $user;
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token) {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token) {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId() {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey() {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey) {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->passwords);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey() {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken() {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken() {
        $this->password_reset_token = null;
    }

    /**
     * 根据openId进行登录 未注册的用户先注册
     * @param type $openId
     * @param type $type
     */
    public static function loginOrReg($data) {
        if (!isset($data['openid']) && empty($data['openid'])) {
            return false;
        }
        $user = static::find()->where(['weixin_code'=>$data['openid']])->one();
        if (!$user) {
            $user = static::addByWxBase($data);
        }
        return $user;
    }

    /**
     * 注册或绑定用户手机号,
     * 第一次注册用户,需考虑是否是通过其他人分享邀请的。
     * @param $data
     * @return bool|mixed|string
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public static function registerOrBindMobile($data) {
        if(!empty($data['openId'])) {
            $user = static::find()->where(['weixin_code'=>$data['openid']])->one();
            if ($user) {
                $result = static::bindMobile($user->id,$data['mobile']);
                if($result) {
                    return  $user->id;
                }
            }
        }
        $user = static::find()->where(['primary_mobile'=>$data['mobile']])->one();
        if(!$user) {
            $user = new User();
            $user->primary_mobile = $data['mobile'];
            $user->status = 1;
        }

        if( $user->save()){

            if(!empty($data['invitation_code'])) {
                $invitorId = static::find()->select('id')->where(['id'=>$data['invitation_code'] ,'status'=>1])->scalar();
                if($invitorId) {
                    //更新邀请
                }
            }

            return $user->id;
        } else {
            return false;
        }

    }
}
