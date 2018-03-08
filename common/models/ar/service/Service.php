<?php

namespace common\models\ar\service;

use Yii;

/**
 * This is the model class for table "service".
 *
 * @property string $id
 * @property string $title
 * @property string $logo
 * @property integer $catalog_id
 * @property integer $provider_id
 * @property integer $creator_id
 * @property integer $updater
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $start_time
 * @property string $stop_time
 * @property string $description
 */
class Service extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'service';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status','catalog_id', 'provider_id', 'creater', 'updater'], 'integer'],
            [['description'], 'string'],
            [['created_at', 'updated_at', 'start_time', 'stop_time'], 'safe'],
            [['title'], 'string', 'max' => 200],
            [['logo'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => '服务名称',
            'logo' => 'Logo',
            'pics' => '图片',
            'catalog_id' => '分类',
            'provider_id' => '供应商',
            'description' => '描述',
            'status' => '状态',
            'hotline' => '服务热线',
            'start_time' => '服务开始时间',
            'stop_time' => '服务结束时间',
        ];
    }


    /*
     * status字段
     */
    public static $status = [
        1 => ['name'=>'上架'],
        2 => ['name'=>'下架'],
        3 => ['name'=>'已删除'],
    ];
    
    /**
     * 关联报价
     * @return type
     */
    public function getQuote() {
        return $this->hasMany(ServiceQuote::className(), ['service_id' => 'id']);
    }
    
    public function getCatalog() {
        return $this->hasOne(Catalog::className(), ['id' => 'catalog_id']);
    }
    
    public function getTime() {
        return $this->hasMany(ServiceTime::className(), ['service_id' => 'id']);
    }
}
