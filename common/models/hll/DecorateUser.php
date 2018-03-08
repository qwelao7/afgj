<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "decorate_user".
 *
 * @property integer $id
 * @property integer $decorate_id
 * @property integer $user_id
 * @property integer $user_role
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class DecorateUser extends ActiveRecord
 {

    public static $roles = [1=>'房主',2=>'项目经理',3=>'客服'];

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'decorate_user';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['decorate_id', 'user_id'], 'required'],
            [['decorate_id', 'user_id', 'user_role', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'decorate_id' => 'Decorate ID',
            'user_id' => 'User ID',
            'user_role' => 'User Role',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 获取装修项目联系人信息
     * @param $decorateId
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public static function getContactsByDecorateId($decorateId) {
        $userId = Yii::$app->user->id;
        $addressExt = (new Query())->select(['t1.user_id','t1.user_role','t2.nickname','t2.headimgurl','t3.mobile_phone'])
            ->from('decorate_user as t1')
            ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.user_id')
            ->leftJoin('ecs_users as t3','t3.user_id = t1.user_id')
            ->where(['t1.decorate_id'=>$decorateId,'t1.valid'=>1])
            ->andWhere(['<>', 't3.user_id', $userId])->orderBy('t1.user_role ASC')->all();
        return $addressExt;
    }
}
