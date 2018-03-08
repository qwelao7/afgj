<?php

namespace common\models\ar\admin;

use Yii;
use common\models\ar\admin\AuthAssignment;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "admin".
 *
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $is_super
 * @property string $realname
 * @property string $email
 * @property string $cellphone
 * @property string $gender
 * @property integer $creater
 * @property string $create_at
 * @property integer $updater
 * @property string $update_at
 * @property integer $valid
 */
class Admin extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'admin';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['username','password','is_super', 'gender'], 'required'],
            [['is_super', 'gender'], 'string'],
            [['creater', 'updater', 'valid'], 'integer'],
            [['create_at', 'update_at'], 'safe'],
            [['username', 'realname', 'cellphone'], 'string', 'max' => 20],
            [['password'], 'string', 'max' => 60],
            [['email'], 'string', 'max' => 50],
            [['username'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'username' => '用户名',
            'password' => '密码',
            'is_super' => '是否超级管理员',
            'realname' => '真实姓名',
            'cellphone' => '手机号码',
            'gender' => '性别',
            'creater' => '添加人',
            'create_at' => '添加时间',
            'updater' => '修改人',
            'update_at' => '修改时间',
            'valid' => '是否有效:0无效，1有效',
        ];
    }

    /* 获取管理员信息 */
    public static function getAdminInfo($adminId) {
        return Admin::find()->where(['id'=>$adminId])->select(['nickname', 'avatar'])->one();
    }

    /**
     * 是否超级管理员
     */
    public static $isSuper = [
        0 => ['name' => '否'],
        1 => ['name' => '是'],
    ];

    public function beforeSave($insert){
        if(!parent::beforeSave($insert))return FALSE;
        $this->username = trim($this->username);
        if($insert || $this->isAttributeChanged('password')){
            $this->password = Yii::$app->getSecurity()->generatePasswordHash($this->password);
        }
        return TRUE;
    }

    public function getRole(){
        return $this->hasOne(AuthAssignment::className(), ['user_id'=>'id']);
    }

    /**
     * 验证密码
     * @param unknown $passwd
     * @return boolean
     */
    public function validatePassword($passwd){
        return Yii::$app->getSecurity()->validatePassword($passwd, $this->password);
    }

    /************************* implements \yii\web\IdentityInterface *****************************/
    public function getId(){
        return $this->id;
    }

    public function getAuthKey(){}

    public function validateAuthKey($authKey){}

    public static function findIdentity($id){
        return static::findOne(['id' => $id, 'valid' => 1]);
    }

    public static function findIdentityByAccessToken($token, $type = null){}

    /**
     * 获取楼盘管理员列表
     * @return array
     * @author zend.wang
     * @date  2016-06-29 13:00
     */
    public static function getLoupanAdminList() {

        $result = (new Query())->select(['t1.id','t1.realname'])
                    ->from('admin as t1')
                    ->leftJoin('auth_assignment as t2','t1.id=t2.user_id')
                    ->where(['t1.valid'=>1,'t1.is_super'=>'0','t2.item_name'=>'楼盘管家'])
                    ->all();
        return !empty($result) ? ArrayHelper::map($result,'id','realname'):[];
    }
}
