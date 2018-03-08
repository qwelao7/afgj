<?php
/**
 *
 * @author: XuYi
 * @date: 2016-10-27
 * @version: $Id$
 */

namespace console\controllers;

use common\components\WxTmplMsg;
use common\components\zhima\ZhiMa;
use common\models\ar\system\EcsRegion;
use common\models\ar\system\QrCode;
use common\models\ecs\EcsUsers;
use common\models\hll\HllEquipmentBrand;
use common\models\hll\HllEquipmentServiceCenter;
use common\models\hll\HllKv;
use common\models\hll\HllSpringTask;
use common\models\SpiderModel;
use common\models\SpringActivity;
use Yii;
use yii\console\Controller;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class TestController extends Controller
{

	public function actionLuck()
	{
		Yii::$app->wechat->sendRedToUser(1);
	}

	public function actionRaffle()
	{
		$activity = new SpringActivity();
		$activity->raffle();
	}

	public function actionCheck1()
	{
		$activity = new SpringActivity();
		$activity->checkUserHouse();
	}

	public function actionCheck2()
	{
		$activity = new SpringActivity();
		$activity->checkUserNeighbor();
	}

	public function actionQrcode()
	{
		$c = QrCode::find()->where('qr_code like "shelf%"')->count();

		for ($i = 0; $i < 2; $i++) {

			$scene_id = 'shelf';
//			for ($j = 0; $j <= (6 - strlen($c)); $j++) {
//				$scene_id .= '0';
//			}
			$scene_id .= ($c + $i + 1);
//			echo $scene_id;
//			continue;
			$result = Yii::$app->wechat->getQrcode()->forever($scene_id);
			$url = $result->url;
			if ($url) {
				$model = new QrCode();
				$model->qr_url = $url;
				$model->qr_code = $scene_id;
				$model->item_type = 2;
				$model->expire_time = '0000-00-00 00:00:00';
				$model->save();
			}

		}


	}

	public function actionTest()
	{
		set_time_limit(0);
		ini_set('memory_limit', '2048M');

		$cate = HllKv::find()->where(['kv_type'=>'1','valid'=>'1'])->asArray()->select('id,kv_key,kv_value')->all();
		$list = ArrayHelper::map($cate,'kv_key','kv_value');
		$data = SpiderModel::SpiderEquipmentBrand($list);
		HllEquipmentBrand::saveDataFromSpider($data);
		unset($cate, $list, $data);


		$brand_list = HllEquipmentBrand::find()->select('id,service_policy_url')->where(['valid' => '1'])
			->asArray()->orderBy('id asc')->all();

		$list = ArrayHelper::map($brand_list, 'id', 'service_policy_url');
		$count = count($list);
		$length = 1;
		$page = ceil($count / $length);

		$city_data = EcsRegion::find()->where(['region_type' => 2])->select('region_id as city_id,region_name')->asArray()->all();
		$city_data = ArrayHelper::map($city_data,'city_id','region_name');
		for ($i = 1; $i <= $page; $i++) {
			$l = array_slice($list, $length * ($i - 1), $length, true);

			$data = SpiderModel::SpiderEquipmentCenter($l);
			HllEquipmentServiceCenter::saveDataFromSpider($data, $city_data);
		}

	}
}