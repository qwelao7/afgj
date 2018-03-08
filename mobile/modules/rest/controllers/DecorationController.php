<?php
namespace mobile\modules\rest\controllers;

use Yii;
use yii\db\Query;
use common\components\Util;
use yii\helpers\Json;
use mobile\components\ActiveController;
use common\models\ar\fang\FangLoupan;
use common\models\ar\fang\FangHouse;
use common\models\ar\fang\FangHouseType;
use common\models\ar\fang\FangDecorate;
use common\models\ar\service\Service;
use common\models\ar\service\ServiceQuote;
use common\models\ar\order\ServiceOrder;
use common\models\ar\order\ServiceOrderAddress;
use common\models\ar\order\ServiceOrderQuote;

class DecorationController extends ActiveController
{
    /**
     * 装修服务
     */
    public function actionDecorate()
    {
        $limit = 2;
        $decoration = FangDecorate::find()->join('LEFT JOIN', 'fang_house_type', 'fang_decorate.house_type_id = fang_house_type.id')
            ->join('LEFT JOIN', 'fang_loupan', 'fang_loupan.id = fang_decorate.loupan_id')
            ->join('LEFT JOIN', 'service_quote', 'service_quote.service_id = fang_decorate.service_id')
            ->select('fang_decorate.*')
            ->addSelect('fang_house_type.area, fang_house_type.lowest_total_price')
            ->addSelect('fang_loupan.name')
            ->addSelect('fang_house_type.name as house_type_name')
            ->addSelect('service_quote.price, service_quote.price_unit')
            ->where(['fang_decorate.valid' => 1, 'fang_decorate.is_recommend' => 1])
            ->limit($limit)->asArray()->all();
        foreach($decoration as &$item) {
            $item['room_num'] = Util::numTransform($item['room_num']);
        }
        return $this->renderRest(['decoration' => $decoration]);
    }

    /**
     * 获取楼盘, 户型情况
     */
    public function actionDecorateInfo($loupanID)
    {
        $obj = ['name' => '全部', 'id' => 0];
        $data = ['room_num' => 0, 'id' => 0];
        $loupan = FangLoupan::find()->where(['valid' => 1])->select(['name', 'id'])->asArray()->all();
        array_unshift($loupan, $obj);
        if ($loupanID == 0) {
            $houseType = FangDecorate::find()->orderBy('room_num')->where(['valid' => 1])->groupBy('room_num')->select(['room_num', 'id'])->asArray()->all();
        } else {
            $houseType = FangDecorate::find()->orderBy('room_num')->where(['valid' => 1, 'loupan_id' => $loupanID])->select(['room_num', 'id'])->asArray()->all();
        }
        array_unshift($houseType, $data);
        if($houseType) {
            foreach($houseType as &$item) {
                if($item['room_num'] == 0) {
                    $item['room_num_change'] = '全部';
                }else {
                    $item['room_num_change'] = Util::numTransform($item['room_num']);
                }
            }
        }
        return $this->renderRest(['loupan' => $loupan, 'house_type' => $houseType]);
    }

    /**
     * 获取装修列表
     * @param $roomId 装修room
     * @param $loupanId 装修楼盘id
     * @author long.huang
     * @date  2016-06-30 16:00
     */
    public function actionDecorateList($loupanId, $roomId)
    {
        $model = FangDecorate::getDecorateByCondition($loupanId, $roomId);
        return $this->renderRest($model);
    }

    /**
     * 获取装修详情
     * @param $id 装修id
     * @author long.huang
     * @date  2016-06-30 16:00
     */
    public function actionDecorateDetail($id)
    {
        $data = FangDecorate::find()->join('LEFT JOIN', 'service', 'service.id = fang_decorate.service_id')
            ->where(['fang_decorate.id' => $id, 'fang_decorate.valid' => 1])
            ->andWhere(['service.status' => 1])
            ->select('fang_decorate.*')
            ->addSelect('service.description, service.hotline')
            ->asArray()->one();
        $data['upgrade'] = ServiceQuote::upgradeInfo($data['service_id']);
        return $this->renderRest($data);
    }

    /**
     * 预约详情
     * @param $id 装修id
     */
    public function actionDecorateOrderDetail($id)
    {
        $decorate = FangDecorate::getDecorateById($id);
        $upgrade = ServiceQuote::upgradeInfo($decorate['service_id']);
        return $this->renderRest(['decorate' => $decorate, 'upgrade' => $upgrade]);
    }

}
