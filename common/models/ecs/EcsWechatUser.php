<?php

namespace common\models\ecs;

use Yii;
use yii\web\IdentityInterface;
use yii\base\NotSupportedException;
/**
 * This is the model class for table "ecs_wechat_user".
 *
 * @property integer $uid
 * @property integer $wechat_id
 * @property integer $subscribe
 * @property string $openid
 * @property string $nickname
 * @property integer $sex
 * @property string $city
 * @property string $country
 * @property string $province
 * @property string $language
 * @property string $headimgurl
 * @property integer $subscribe_time
 * @property string $remark
 * @property string $privilege
 * @property string $unionid
 * @property integer $group_id
 * @property integer $ect_uid
 * @property integer $bein_kefu
 * @property integer $isbind
 */
class EcsWechatUser extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_wechat_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sex', 'uid', 'subscribe', 'wechat_id', 'subscribe_time', 'group_id', 'ect_uid', 'bein_kefu', 'isbind'], 'integer'],
            [['wechat_id', 'subscribe', 'openid', 'sex', 'language', 'headimgurl', 'subscribe_time','ect_uid'], 'required'],
            [['language'], 'string', 'max' => 50],
            [['openid', 'nickname', 'city','country','province', 'headimgurl','remark','privilege','unionid'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'uid' => 'Uid',
            'wechat_id' => 'Wechat ID',
            'subscribe' => 'Subscribe',
            'openid' => 'Openid',
            'nickname' => 'Nickname',
            'sex' => 'Sex',
            'city' => 'City',
            'country' => 'Country',
            'province' => 'Province',
            'language' => 'Language',
            'headimgurl' => 'Headimgurl',
            'subscribe_time' => 'Subscribe Time',
            'remark' => 'Remark',
            'privilege' => 'Privilege',
            'unionid' => 'Unionid',
            'group_id' => 'Group ID',
            'ect_uid' => 'Ect Uid',
            'bein_kefu' => 'Bein Kefu',
            'isbind' => 'Isbind',
        ];
    }


}