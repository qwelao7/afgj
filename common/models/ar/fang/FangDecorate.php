<?php

namespace common\models\ar\fang;

use Yii;
use common\components\Util;
use common\models\ar\service\Service;
use common\models\ar\service\ServiceQuote;
use common\models\ar\fang\FangLoupan;
use common\models\ar\fang\FangHouse;
use common\models\ar\fang\FangHouseType;

/**
 * This is the model class for table "fang_decorate".
 *
 * @property string $id
 * @property integer $type
 * @property string $title
 * @property string $thumbnail
 * @property string $loupan_id
 * @property string $house_type_id
 * @property string $service_id
 * @property integer $room_num
 * @property integer $is_recommend
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class FangDecorate extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'fang_decorate';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id','room_num'], 'required'],
            [['id', 'type', 'loupan_id', 'house_type_id', 'service_id','is_recommend', 'creater', 'updater', 'valid', 'room_num'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 50],
            [['thumbnail'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'type' => '类型：1精装，2软装，3精装加软装',
            'title' => '标题',
            'thumbnail' => '缩略图',
            'loupan_id' => 'loupan.id',
            'house_type_id' => 'house_type.id',
            'service_id' => 'service.id',
            'room_num' => '居室数目',
            'is_recommend' => '0-不推荐， 1-推荐',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    public function getService() {
        return $this->hasOne(Service::className(), ['id'=>'service_id']);
    }

    public function getUpgrade() {
        return $this->hasMany(FangDecorateUpgrade::className(), ['decorate_id'=>'id'])->joinWith(['service']);
    }

    public function getHousetype() {
        return $this->hasMany(FangHouseType::className(), ['id'=>'house_type_id']);
    }

    /**
     * @param $accountId 楼盘ID
     * @param $adminId 居室ID
     * @return array
     * @author long.huang
     * @date  2016-06-29 13:00
     */
    public static function getDecorateByCondition($loupanId,$roomId) {
        empty($loupanId) && $loupanId=null;
        empty($roomId) && $roomId=null;
        $decoration = FangDecorate::find()->join('LEFT JOIN', 'fang_house_type', 'fang_decorate.house_type_id = fang_house_type.id')
                                                 ->join('LEFT JOIN', 'fang_loupan', 'fang_loupan.id = fang_decorate.loupan_id')
                                                 ->join('LEFT JOIN', 'service_quote', 'service_quote.service_id = fang_decorate.service_id')
                                                 ->select('fang_decorate.*')
                                                 ->addSelect('fang_house_type.area, fang_house_type.lowest_total_price')
                                                 ->addSelect('fang_loupan.name')
                                                 ->addSelect('fang_house_type.name as house_type_name')
                                                 ->addSelect('service_quote.price, service_quote.price_unit')
                                                 ->where(['fang_decorate.valid'=>1])
                                                 ->andFilterWhere(['fang_decorate.room_num'=>$roomId,'fang_decorate.loupan_id'=>$loupanId])
                                                 ->asArray()->all();
        foreach($decoration as &$item) {
            $item['room_num_change'] = Util::numTransform($item['room_num']);
        }
        return $decoration;
    }

    /**
    * @param $id 装修id
    */
    public static function getDecorateById($id) {
        $decoration = FangDecorate::find()->join('LEFT JOIN', 'fang_house_type', 'fang_decorate.house_type_id = fang_house_type.id')
                                              ->join('LEFT JOIN', 'fang_loupan', 'fang_loupan.id = fang_decorate.loupan_id')
                                              ->join('LEFT JOIN', 'service_quote', 'service_quote.service_id = fang_decorate.service_id')
                                              ->select('fang_decorate.*')
                                              ->addSelect('fang_house_type.area, fang_house_type.lowest_total_price')
                                              ->addSelect('fang_loupan.name')
                                              ->addSelect('fang_house_type.name as house_type_name')
                                              ->addSelect('service_quote.price, service_quote.price_unit')
                                              ->where(['fang_decorate.valid'=>1, 'fang_decorate.id'=>$id])
                                              ->asArray()->one();
        $decoration['room_num_change'] = Util::numTransform($decoration['room_num']);
        return $decoration;
    }

    /**
     * 获取装修户型名称
     * @param $id service_id
     */
    public static function decorateHouseType($id) {
        $data = FangHouseType::find()->join('LEFT JOIN', 'fang_decorate', 'fang_house_type.id = fang_decorate.house_type_id')
                                    ->where('fang_decorate.service_id='.$id)
                                    ->andWhere('fang_decorate.valid=1 and fang_house_type.valid=1')
                                    ->select('fang_house_type.name')->one();
        return $data;
    }
}
