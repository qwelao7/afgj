<?php

namespace common\models\ecs;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "ecs_admin_user".
 *
 * @property integer $user_id
 * @property string  $user_name
 * @property string  $email
 * @property string  $password
 * @property string  $ec_salt
 * @property integer $add_time
 * @property integer $last_login
 * @property string  $last_ip
 * @property string  $action_list
 * @property string  $nav_list
 * @property string  $lang_type
 * @property integer $agency_id
 * @property integer $suppliers_id
 * @property string  $todolist
 * @property integer $role_id
 * @property string  $headimgurl
 */
class EcsAdminUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_admin_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'add_time', 'last_login', 'agency_id', 'suppliers_id', 'role_id'], 'integer'],
            [['user_name', 'email'], 'string', 'max' => 60],
            [['password'], 'string', 'max'=> 32],
            [['ec_salt'], 'string', 'max' => 10],
            [['last_ip'], 'string', 'max' => 15],
            [['lang_type'], 'string', 'max' => 50],
            [['headimgurl'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => '管理员id',
            'user_name' => '名称',
            'email' => '邮箱',
            'password' => '密码',
            'ex_salt' => '',
            'add_time' => '创建时间',
            'last_login' => '上次登录时间',
            'last_ip' => '上次登录ip',
            'action_list' => '权限列表',
            'nav_list' => '菜单列表',
            'lang_type' => '语言',
            'agency_id' => '',
            'suppliers_id' => '',
            'todolist' => '记事本记录的数据',
            'role_id' => '',
            'headimgurl' => '头像'
        ];
    }

    public static function getAdminInfo($id, $fields = null) {
        if (!$fields) {
            $fields = ['user_name', 'email', 'headimgurl'];
        }

        $data = (new Query())->select($fields)->from('ecs_admin_user')
                            ->where(['user_id'=>$id])->one();

        if (in_array('headimgurl', $fields) && $data) {
            if ($data['headimgurl'] != '') {
                $data['headimgurl'] = Yii::$app->upload->domain .$data['headimgurl'];
            }else {
                $data['headimgurl'] = Yii::$app->params['userDefaultAvatar'];
            }
        }

        return $data;
    }
}
