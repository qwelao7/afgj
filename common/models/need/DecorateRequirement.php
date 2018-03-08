<?php
namespace common\models\need;

use Yii;
use common\components\ActiveRecord;

/**
 *
 * @property integer $id
 * @property string  $area_name
 * @property string  $community_name
 * @property string  $decorate_type
 * @property string  $house_area
 * @property string  $house_type
 * @property string  $cust_name
 * @property string  $cust_phone
 * @property string  $create_at
 */
class DecorateRequirement extends ActiveRecord
{
    public static function tableName() {
        return 'decorate_requirement';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['community_name','area_name','decorate_type', 'house_area', 'house_type', 'cust_name', 'cust_phone'], 'required'],
            [['id'], 'integer'],
            [['community_name','area_name','decorate_type', 'house_area', 'house_type', 'cust_name', 'cust_phone'], 'string'],
            [['create_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '编号',
            'area_name' => '所在城市',
            'community_name' => '小区',
            'decorate_type' => '装修类型',
            'house_area' => '房子面积',
            'house_type' => '房子类型',
            'cust_name' => '姓名',
            'cust_phone' => '电话号码',
            'create_at' => '创建时间'
        ];
    }
}