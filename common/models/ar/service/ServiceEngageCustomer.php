<?php

namespace common\models\ar\service;

use Yii;

/**
 * This is the model class for table "service_engage_customer".
 *
 * @property string $id
 * @property integer $service_id
 * @property string $cust_name
 * @property string $cust_mobile
 * @property integer $cust_loupan
 * @property integer $account_id
 * @property string $created_at
 * @property string $updated_at
 * @property integer $valid
 * @property integer $is_join
 * @property integer $sqs_id
 * @property string $age_range
 * @property integer $join_num
 */
class ServiceEngageCustomer extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'service_engage_customer';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['service_id', 'cust_loupan', 'account_id', 'valid','is_join','sqs_id', 'join_num'], 'integer'],
            [['cust_name'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['cust_name', 'cust_mobile'], 'string', 'max' => 20],
            [['age_range'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '服务预约客户编号',
            'service_id' => '服务ID',
            'cust_name' => '客户姓名',
            'cust_mobile' => '客户手机号码',
            'cust_loupan' => '客户来自楼盘编号',
            'account_id' => '客户关联用户编号',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'valid' => '是否有效：0无效，1有效',
            'is_join' => '是否参加 1-参加 2-不参加',
            'sqs_id' => '报班编号',
            'age_range' => '选择年龄段',
        ];
    }
}
