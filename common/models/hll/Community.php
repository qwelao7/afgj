<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_community".
 *
 * @property integer $id
 * @property integer $city
 * @property integer $district
 * @property string $cname
 * @property string $firstletter
 * @property integer $displayorder
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 */
class Community extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'hll_community';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id','province' ,'city', 'district','name','firstletter'], 'required'],
            [['id','city','district'], 'integer'],
            [['name'], 'string', 'max' => 30],
            [['thumbnail'], 'string', 'max' => 100],
            [['firstletter'], 'string', 'max' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '小区编号',
            'province' => '身份编号',
            'city' => '城市编号',
            'district' => '地区编号',
            'name' => '小区名称',
            'thumbnail' => '小区图片',
            'firstletter' => '小区名前缀',
            'displayorder' => '排序',
            'creater' => '创建者Id',
            'created_at' => '创建时间',
            'updater' => '更新者Id',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 获取小区的省市区信息
     * @param $id
     * @return array
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public static function getRegionById($id) {
        $communityObj = static::findOne($id);
        return [$communityObj->province,$communityObj->city,$communityObj->district];
    }

    public static function getRegionByDistrict($id){
        $city = (new Query())->select(['parent_id'])->from('ecs_region')->where(['region_id'=>$id])->one();
        $province = (new Query())->select(['parent_id'])->from('ecs_region')->where(['region_id'=>$city])->one();
        return [$city['parent_id'],$province['parent_id']];
    }

    /**
     * @param $id
     * @param $type 1为与楼盘绑定的社区  2为未绑定的 3为获取所有绑定的社区
     */
    public static function getCommunityThumbnailById($id, $type){
        switch($type){
            case 1:
                $community = (new Query())->select(['t2.thumbnail','t1.community_id','t3.name'])
                    ->from('hll_community_ext as t1')
                    ->leftJoin('fang_loupan as t2','t2.id = t1.loupan_id')
                    ->leftJoin('hll_community as t3','t3.id = t1.community_id')
                    ->where(['t1.community_id'=>$id,'t1.valid'=>1,'t2.valid'=>1,'t3.valid'=>1])->one();
                if(!$community){
                    $community = [];
                }else{
                    $community['type'] = 1;
                }
                break;
            case 2:
                $community = static::find()->select(["(id) as community_id",'thumbnail','name'])
                    ->where(['id'=>$id,'valid'=>1])->asArray()->one();
                if(!$community){
                    $community = [];
                }else{
                    $community['type'] = 2;
                }
                break;
            case 3:
                $community = (new Query())->select(['t2.thumbnail','t1.community_id','t3.name',"(1) as type"])
                    ->from('hll_community_ext as t1')
                    ->leftJoin('fang_loupan as t2','t2.id = t1.loupan_id')
                    ->leftJoin('hll_community as t3','t3.id = t1.community_id')
                    ->where(['t1.community_id'=>$id,'t1.valid'=>1,'t2.valid'=>1,'t3.valid'=>1])
                    ->orderBy(['t1.id'=>SORT_ASC])->all();
                $community = (!$community) ? [] : $community;
                break;
            default:
                $community = [];
                break;
        }
        return $community;
    }
}
