<?php

namespace common\models\hll;


use common\models\ecs\EcsUsers;
use Yii;
use yii\base\Event;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use common\models\hll\UserAddress;

/**
 *
 * 房产地址信息拓展表
 *
 */
class UserAddressTemp extends \yii\db\ActiveRecord
{
    public static function tableName() {
        return 'hll_user_address_temp';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id','account_id', 'creater', 'valid','city','province','district'], 'integer'],
            [['is_default'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['address_desc'], 'string', 'max' => 40],
            [['community_name'], 'string', 'max' => 30],
            [['consignee','mobile'], 'string', 'max' => 60],
            [['group_name', 'building_num','unit_num','house_num'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'community_name' => 'community name',
            'consignee' => 'Consignee',
            'mobile' => 'Mobile',
            'city' =>'City',
            'province' => 'Province',
            'district' => 'District',
            'is_default' => 'Is Default',
            'desc' => 'Building House Num',
            'group_name' => 'group_name',
            'building_num' => 'building_num',
            'unit_num' => 'unit_num',
            'house_num' => 'house_num',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 设置默认地址
     * @param type $id 要设置的地址id
     */
    public static function setDefault($id, $uid)
    {
        static::updateAll(['is_default' => 'no'], ['account_id' => $uid]);
        UserAddress::updateAll(['is_default' => 'no'], ['account_id' => $uid]);
        $model = static::find()->where(['address_id' => $id, 'account_id' => $uid, 'valid'=>1])->one();
        if ($model) {
            $model->is_default = 'yes';
            if ($model->save()) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }
}
