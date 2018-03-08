<?php
namespace mobile\modules\rest\controllers;

use Yii;
use mobile\components\ActiveController;
use common\models\ar\fang\FangLoupan;
use common\models\ar\fang\FangHouse;
use common\models\ar\fang\FangYouhui;
use common\models\ar\fang\FangHouseType;
use common\models\ar\fang\FangDecorate;
use common\models\ar\fang\FangDecorateUpgrade;
use common\models\ar\system\Area;
use yii\helpers\Json;
use common\components\Util;


/**
 * Description of FangController
 *
 * @author Don.T
 */
class FangController extends ActiveController
{

    /**
     * 获取楼盘列表
     * @param type $page 页码
     * @return type
     */
    public function actionLouPanList($page = 1)
    {
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $data = FangLoupan::find()->orderBy(['sort' => SORT_ASC])->where(['<', 'status', '4'])->offset($offset)->limit($limit)->asArray()->all();
        if ($data) {
            foreach ($data as $k => $v) {
                $data[$k]['tag'] = explode(',', $v['tag']);
            }
        }
        return $this->renderRest($data);
    }

    /**
     * 获取楼盘详情
     * @param type $id 楼盘id
     * @return type
     */
    public function actionLouPanDetail($id, $type = '')
    {
        $data = FangLoupan::find()->joinWith(['area'])->where(['fang_loupan.id' => $id])->asArray()->one();
        $data['area_address'] = Area::parentsStr($data['area_id']);
        if ($data) {
            $data['tag'] = explode(',', $data['tag']);
        }
        $data['decorate_level'] = ['id' => $data['decorate_level']] + FangLoupan::$decorateLevel[$data['decorate_level']];
        $data['property_type'] = FangLoupan::parsePropertyType($data['property_type']);
        if ($type == 'full') {
            //获取floor-information页面所需要的所有数据
            $now = date("Y-m-d H:i:s");
            $data['houseType'] = FangHouseType::find()->where(['loupan_id' => $id])->orderBy(['id' => SORT_DESC])->limit(2)->asArray()->all();
            $data['house'] = FangHouse::find()->where(['loupan_id' => $id])->orderBy(['id' => SORT_DESC])->limit(5)->asArray()->all();
            $sortmin = FangHouseType::find()->where(['loupan_id' => $id])->orderBy(['sort' => SORT_ASC])->select('id')->asArray()->one();
            $data['pics'] = \yii\helpers\Json::decode($data['pics']);
        }
        return $this->renderRest($data);
    }

    /**
     * 获取销售列表
     * @param type $id 楼盘id
     * @param type $page 页码
     * @return type
     */
    public function actionSaleList($id, $page)
    {
        $limit = 50;
        $offset = ($page - 1) * $limit;
        $data = FangHouse::find()->where(['loupan_id' => $id])
            ->orderBy([
                'building_num' => SORT_ASC,
                'unit_num' => SORT_ASC,
                'house_num' => SORT_ASC
            ])->offset($offset)->limit($limit)->asArray()->all();
        return $this->renderRest($data);
    }

    /**
     * 获取优惠列表
     * @param type $id 楼盘id
     * @return type
     */
    public function actionCouponList($id)
    {
        $data = FangYouhui::find()->where(['loupan_id' => $id])->orderBy(['sort' => SORT_ASC])->asArray()->all();
        return $this->renderRest($data);
    }

    /**
     * 获取优惠详情
     * @param type $id 楼盘id
     * @return type
     */
    public function actionCouponDetail($id)
    {
        $data = FangYouhui::find()->where(['id' => $id])->asArray()->one();
        return $this->renderRest($data);
    }

    /**
     * 获取户型列表
     * @param type $id 楼盘id
     */
    public function actionHouseTypeList($id)
    {
        $data = FangHouseType::find()
            ->orderBy(['fang_house_type.sort' => SORT_ASC])
            ->where(['fang_house_type.loupan_id' => $id])
            ->asArray()->all();
        foreach ($data as $key => $value) {
            $house_type_id = $data[$key]['id'];
            $data[$key]['selling'] = FangHouse::find()->where(['house_type_id' => $house_type_id])
                ->andWhere(['loupan_id' => $id])
                ->orderBy(['building_num' => SORT_ASC])
                ->select(['building_num'])
                ->distinct()
                ->asArray()->all();
            $data[$key]['type_total_price'] = FangHouse::find()->where(['house_type_id' => $house_type_id])
                ->andWhere(['loupan_id' => $id])
                ->min('total_price');
            $data[$key]['area'] = $data[$key]['area'];
            $data[$key]['type_total_price'] = round($data[$key]['type_total_price']);
        }
        return $this->renderRest($data);
    }

    /**
     * 获取户型详情
     * @param type $id
     */
    public function actionHouseTypeDetail($id)
    {
        $data = FangHouseType::find()->joinWith(['decorate'])
            ->orderBy(['fang_house_type.sort' => SORT_ASC])
            ->where(['fang_house_type.id' => $id])
            ->asArray()->all();
        if ($data[0]['decorate']) {
            foreach ($data[0]['decorate'] as $key => $value) {
                $data[0]['decorate'][$key]['service']['pics'] = Json::decode($value['service']['pics']);
                $data[0]['decorate'][$key]['upgrade'] = FangDecorateUpgrade::find()->joinWith(['service'])
                    ->where(['decorate_id' => $data[0]['decorate'][$key]['id']])
                    ->asArray()->all();
                if ($data[0]['decorate'][$key]['upgrade']) {
                    foreach ($data[0]['decorate'][$key]['upgrade'] as $kj => $vj) {
                        $data[0]['decorate'][$key]['upgrade'][$kj]['service']['pics'] = Json::decode($vj['service']['pics']);
                    }
                }
            }
        };
        return $this->renderRest($data);
    }
}
