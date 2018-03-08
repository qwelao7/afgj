<?php

namespace common\models\ar\service;

use Yii;

/**
 * This is the model class for table "service_time".
 *
 * @property integer $id
 * @property integer $service_id
 * @property string $data
 * @property integer $add_time
 * @property integer $add_user
 * @property integer $edit_time
 * @property integer $edit_user
 * @property integer $valid
 */
class ServiceTime extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'service_time';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['service_id', 'data'], 'required'],
            [['service_id', 'creater', 'updater', 'valid'], 'integer'],
            [['data'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'service_id' => '服务',
            'data' => '服务时间',
            'created_at' => '添加时间',
            'creater' => '添加人',
            'updated_at' => '编辑时间',
            'updater' => '编辑人',
            'valid' => '是否有效',
        ];
    }


    /**
     * 获取服务
     * @return ActiveQuery
     */
    public function getService(){
        return $this->hasOne(Service::className(), ['id' => 'service_id']);
    }
}
