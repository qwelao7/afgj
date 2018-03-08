<?php

namespace common\models\ar\service;

use Yii;
use common\models\ar\admin\Admin;
/**
 * This is the model class for table "provider".
 *
 * @property string $id
 * @property string $name
 * @property string $logo
 * @property integer $owner_id
 * @property string $business_license_number
 * @property string $organization_code
 * @property integer $creator_id
 * @property string $legal_representative_name
 * @property string $legal_representative_id_card
 * @property string $business_register_date
 * @property integer $updater
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $description
 */
class Provider extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'provider';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
//             [['name', 'business_license_number', 'legal_representative_name', 'legal_representative_mobile', 'legal_representative_id_pic'], 'required'],
            [['creater', 'updater'], 'integer'],
            [['description'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 200],
            [['logo'], 'string', 'max' => 255],
            [['business_license_number'], 'string', 'max' => 64],
            [['legal_representative_name'], 'string', 'max' => 32],
            [['legal_representative_id'], 'string', 'max' => 16]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'name' => '名称',
            'logo' => 'Logo',
            'business_license_number' => '营业执照注册号',
            'business_license_pic' => '营业执照照片',
            'legal_representative_name' => '服务商法人',
            'legal_representative_pic' => '法人身份证照片',
            'legal_representative_mobile' => '法人手机号码',
            'legal_representative_id' => '法人身份证号码',
            'legal_representative_id_pic' => '法人身份证照片',
            'status' => '状态',
            'description' => '描述',
        ];
    }


    public function getAdmin(){
        return $this->hasOne(Admin::className(), ['id' => 'admin_id']);
    }
}
