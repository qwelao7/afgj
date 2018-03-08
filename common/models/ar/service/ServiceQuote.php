<?php

namespace common\models\ar\service;

use Yii;
use yii\helpers\ArrayHelper;
use common\models\ar\service\Service;

/**
 * This is the model class for table "service_quote".
 *
 * @property string $id
 * @property integer $service_id
 * @property integer $parent_quote_id
 * @property string $title
 * @property string $price
 * @property string $price_unit
 * @property integer $ssr_id
 * @property string $area
 * @property string $unit
 * @property string $created_at
 * @property string $updated_at
 * @property string $scenario_params
 * @property string $description
 */
class ServiceQuote extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'service_quote';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['service_id', 'ssr_id', 'creater', 'updater', 'valid','parent_quote_id', 'price_unit'], 'integer'],
            [['price'], 'number'],
            [['scenario_params', 'description','area'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['title','price', 'unit'], 'required'],
            [['unit'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'service_id' => '服务ID',
            'parent_quote_id' => '装修id',
            'title' => '报价标题',
            'price' => '价格',
            'ssr_id' => '结算表id',
            'price_unit' => '报价类型 1-/套 2-/平方',
            'ssr_id' => 'Reference: service_settlement_rule.id',
            'unit' => '报价单位',
            'areacode' => '地址(所有的服务都有地址属性，这里特地抽出来)',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'scenario_params' => '报价构成参数，序列化数据，由 core\\service\\quote\\Scenario解析',
            'description' => 'Description',
            'creater' => '创建者id',
            'updater' => '更新者id',
            'valid' => '0已经删除，1有效',
        ];
    }


    /**
     * 获取服务
     * @return ActiveQuery
     */
    public function getService(){
        return $this->hasOne(Service::className(), ['id' => 'service_id']);
    }
    
    /**
     * 获取总价
     * @param type $items
     */
    public static function getTotal($items) {
        $total = 0;
        $ids = ArrayHelper::getColumn($items, 'id');
        $data = static::find()->where(['id'=>$ids])->indexBy('id')->all();
        foreach ($items as $v) {
            if (isset($data[$v['id']])) {
                $total += $data[$v['id']]['price']*$v['num'];
            }
        }
        return $total;
    }

    public static function detail($ids) {
        $data = static::find()->where(['id'=>$ids])->asArray()->all(); 
        return $data;
    }

    /**
     * 获取升级包详情
     * @param $id 装修service_id
     */
    public static function upgradeInfo($id) {
        return ServiceQuote::find()->join('LEFT JOIN', 'service', 'service.id = service_quote.service_id')
                ->where(['service_quote.parent_quote_id'=>$id, 'service_quote.valid'=>1])
                ->andWhere(['service.status'=>1])
                ->select('service_quote.*')
                ->addSelect('service.description')
                ->asArray()->all();
    }

}
