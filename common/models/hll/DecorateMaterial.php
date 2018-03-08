<?php

namespace common\models\hll;

use common\models\ar\system\QrCode;
use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "decorate_material".
 *
 * @property integer $id
 * @property integer $decorate_id
 * @property integer $goods_id
 * @property string $quality_guarantee_start_date
 * @property string $quality_guarantee_end_date
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class DecorateMaterial extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'decorate_material';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['decorate_id', 'goods_id', 'quality_guarantee_start_date', 'quality_guarantee_end_date'], 'required'],
            [['decorate_id', 'goods_id', 'creater', 'updater', 'valid'], 'integer'],
            [['quality_guarantee_start_date', 'quality_guarantee_end_date', 'created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'decorate_id' => 'Decorate ID',
            'goods_id' => 'Goods ID',
            'quality_guarantee_start_date' => 'Quality Guarantee Start Date',
            'quality_guarantee_end_date' => 'Quality Guarantee End Date',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 获取装修材料
     * @param $decoratedId
     * @return array
     * @author zend.wang
     * @date  2016-09-06 13:00
     */
    public static function getListByDecorateId($decoratedId) {
        $time = date('Y-m-d H:i:s');
        $result = (new Query())->select(['t1.quality_guarantee_start_date','t1.quality_guarantee_end_date', 't1.area',
            't1.id','t2.goods_id','t2.name','t2.brand_id','t2.model_id','t3.id as cat_id','t3.name as cat_name'])
            ->from('decorate_material as t1')
            ->leftJoin("decorate_material_goods  as t2"," t2.goods_id=t1.goods_id")
            ->leftJoin("decorate_material_goods_rel  as t3"," t3.id=t2.cate_id")
            ->where(['t1.decorate_id'=>$decoratedId,'t1.valid'=>1, 't3.type'=>0])
            ->orderBy('t1.id DESC')->all();
        $list =[];
        if($result) {
            foreach($result as &$val) {
                $val['state'] = ($val['quality_guarantee_end_date'] > $time)?0:1;
                $list[$val['cat_name']][] = $val;
            }
        }
        if($list){
            foreach($list as &$item){
                foreach($item as &$val){
                    $val['brand_name'] = static::getDecorateMaterialGoodsRel($val['brand_id'],1);
                    $val['model_name'] = static::getDecorateMaterialGoodsRel($val['model_id'],2);
                }
            }
        }
        return $list;
    }

    public static function getDecorateMaterialGoodsRel($id,$type){
        $name = (new Query())->select(['name'])->from('decorate_material_goods_rel')
            ->where(['id'=>$id,'type'=>$type])->scalar();
        return (!$name) ? '' : $name;
    }
}
