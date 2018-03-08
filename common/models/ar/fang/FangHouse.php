<?php

namespace common\models\ar\fang;

use common\models\ar\user\AccountAddress;
use Yii;

/**
 * This is the model class for table "fang_house".
 *
 * @property string $id
 * @property string $loupan_id
 * @property string $building_num
 * @property string $unit_num
 * @property string $house_num
 * @property string $house_type_id
 * @property string $area
 * @property string $internal_area
 * @property string $get_ratio
 * @property string $raw_price
 * @property integer $decorate_price
 * @property string $total_price
 * @property integer $sell_status
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property string $creater
 */
class FangHouse extends \yii\db\ActiveRecord
 {
    
    public static $sellStatusText = [
        1 => [
            'name' => '不可售',
            'class' => 'font-color-1'
        ],
        2 => [
            'name' => '可售',
            'class' => 'font-color-2'
        ],
        3 => [
            'name' => '已预约',
            'class' => 'font-color-4'
        ],
        4 => [
            'name' => '已售',
            'class' => 'font-color-3'
        ],
    ];
    
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'fang_house';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['loupan_id', 'house_type_id', 'raw_price', 'decorate_price', 'sell_status', 'updater', 'creater'], 'integer'],
            [['area', 'internal_area', 'get_ratio', 'total_price'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['building_num', 'unit_num', 'house_num'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'loupan_id' => '楼盘id',
            'building_num' => '楼栋号',
            'unit_num' => '单元号',
            'house_num' => '户号',
            'house_type_id' => '户型id',
            'area' => '建筑面积',
            'internal_area' => '套内建筑面积',
            'get_ratio' => '得房率',
            'raw_price' => '申请毛坯单价，单位元每平米',
            'decorate_price' => '装修价，单位元每平米',
            'total_price' => '总价，单位万',
            'sell_status' => '销售状态：1不可售卖，2可售，3已预约，4已售',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'creater' => '创建者id',
        ];
    }

    /**
     * 关联楼盘
     */
    public function getLoupan(){
        return $this->hasOne(\common\models\ar\fang\FangLoupan::className(), ['id'=>'loupan_id']);
    }

    /**
     * 补上楼栋号单元号户号的单位
     * @param boolean $justOne 是否只有一个数组
     */
    public static $units = [
        'building_num' => '栋',
        'unit_num' => '单元',
        'house_num' => '室',
    ];
    public static function joinUnit($list, $justOne=FALSE){
        $list = (array)$list;
        if($justOne)$list = [$list];
        foreach($list as &$item){
            if(!empty($item['building_num']))$item['building_num'] .= static::$units['building_num'];
            if(!empty($item['unit_num']))$item['unit_num'] .= static::$units['unit_num'];
            if(!empty($item['house_num']))$item['house_num'] .= static::$units['house_num'];
        }
        return $justOne ? $list[0] : $list ;
    }

    /**
     * 新增虚拟房产
     * @params $fictitious_id 虚拟房产编号
     */
    public static function addFangHouse() {
        $fictitious_id = 14;
        $last = FangHouse::find()->where('loupan_id='.$fictitious_id)->andWhere('sell_status != 1 and valid=1')->orderBy(['id'=>SORT_DESC])->one();
        if($last) {
            $house_id = $last->id;
            $house_num = $last->house_num;
            $unit_num = $last->unit_num;
            $building_num = $last->building_num;
            $floor = (int)(substr($house_num, 0, strrpos($house_num, '0')));
            $door = (int)(substr($house_num, strrpos($house_num, '0')+1));
            $num = AccountAddress::find()->where('house_id='.$house_id)->andWhere('loupan_id='.$fictitious_id)->andWhere('valid=1')->count();
            //每户限定3个用户
            if($num < 3) {
                return $last;
            }else {
                //每单元限定2户
                if($door == 1 || $door == 3) {
                    $house = new static;
                    $house->loupan_id = $fictitious_id;
                    $house->building_num = $building_num;
                    $house->unit_num = $unit_num;
                    $house->house_num = (string)$floor.'0'.(string)($door+1);
                    $house->sell_status = 2;
                }elseif($door == 2){
                    $house = new static;
                    $house->loupan_id = $fictitious_id;
                    $house->building_num = $building_num;
                    $house->unit_num = '2';
                    $house->house_num = (string)$floor.'03';
                    $house->sell_status = 2;
                }else {
                    //楼层限定21层
                    if($floor <= 20) {
                        $house = new static;
                        $house->loupan_id = $fictitious_id;
                        $house->building_num = $building_num;
                        $house->unit_num = '1';
                        $house->house_num = (string)($floor+1).'01';
                        $house->sell_status = 2;
                    }else {
                        $house = new static;
                        $house->loupan_id = $fictitious_id;
                        $house->building_num = (string)((int)($building_num)+1);
                        $house->unit_num = '1';
                        $house->house_num = '101';
                        $house->sell_status = 2;
                    }
                }
                if($house->save()) {
                    return $house;
                }else {
                    Yii::warning('虚拟房间创建失败:'.print_r($house->getErrors(), TRUE));
                }
            }
        }else {
            $house = new static;
            $house->loupan_id = $fictitious_id;
            $house->building_num = '1';
            $house->unit_num = '1';
            $house->house_num = '101';
            $house->sell_status = 2;
            if($house->save()) {
                return $house;
            }else {
                Yii::warning('虚拟房间创建失败:'.print_r($house->getErrors(), TRUE));
            }
        }
    }

}
