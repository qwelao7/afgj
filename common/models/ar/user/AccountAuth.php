<?php

namespace common\models\ar\user;

use Yii;
use common\models\ar\user\AccountAddress;

/**
 * This is the model class for table "account_auth".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $auth_type
 * @property integer $address_id
 * @property string $authdata
 * @property string $failcause
 * @property integer $failnum
 * @property string $created_at
 * @property string $updated_at
 */
class AccountAuth extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'account_auth';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'auth_type'], 'required'],
            [['account_id', 'auth_type', 'address_id', 'failnum','address_temp_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['authdata'], 'string', 'max' => 1000],
            [['failcause'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'account_id' => '用户编号',
            'auth_type' => '认证类型：1身份认证，2房主认证',
            'address_id' => '房产编号，房主认证时用',
            'authdata' => '认证资料',
            'failcause' => '认证失败原因',
            'failnum' => '认证失败次数',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }


    /**
     * 用户房产认证状态
     * @param $addressId(account_address.id)
     * @author meizijiu
     * @date  2016-08-05 18:00
     */
    public static function authState($addressId) {
        $message['code'] = 1;

        $address = AccountAddress::find()->where(['id'=>$addressId, 'valid'=>1])->select(['house_id', 'owner_auth'])->one();
        $auth = AccountAuth::find()->where(['address_id'=>$addressId, 'account_id'=>Yii::$app->user->id])->one();

        if(!$address) {
            $message['msg'] = '信息为空';
        }

        if($address->house_id == 0 && !$auth) {

            $message['code'] = 1; //房产未认证
        }else if($address->house_id != 0 && !$auth) {

            $message['code'] = 2; //房产已认证
        }else if($auth->failnum == 0) {

            $message['code'] = 3;
            $message['msg'] = $address->house_id;//认证执行中
        }else if($auth->failcause != undefined && $auth->failnum > 0) {

            $message['code'] = 4; //认证被拒绝
        }
        return $message;
    }
}
