<?php

namespace common\models\ar\order;

use Yii;

/**
 * This is the model class for table "service_order_comment".
 *
 * @property integer $id
 * @property integer $order_id
 * @property integer $quality_star
 * @property integer $attitude_star
 * @property integer $service_star
 * @property string $context
 * @property integer $add_time
 * @property integer $add_user
 * @property integer $edit_time
 * @property integer $edit_user
 * @property integer $valid
 */
class ServiceOrderComment extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'service_order_comment';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['order_id', 'quality_star', 'attitude_star', 'service_star', 'add_time', 'add_user', 'edit_time', 'edit_user', 'valid'], 'integer'],
            [['context'], 'string'],
            [['order_id'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'order_id' => '订单号',
            'quality_star' => '服务质量',
            'attitude_star' => '服务态度',
            'service_star' => '管家服务',
            'context' => '内容',
            'add_time' => '添加时间',
            'add_user' => '添加人',
            'edit_time' => '编辑时间',
            'edit_user' => '编辑人',
            'valid' => '是否有效(0 无效 1有效)',
        ];
    }
}
