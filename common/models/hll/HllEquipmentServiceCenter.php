<?php

namespace common\models\hll;

use common\models\ar\system\EcsRegion;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "hll_equipment_service_center".
 *
 * @property integer $id
 * @property integer $city_id
 * @property integer $brand_id
 * @property string $company_name
 * @property string $company_address
 * @property string $phone
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEquipmentServiceCenter extends \yii\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'hll_equipment_service_center';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['city_id', 'brand_id', 'company_name', 'company_address', 'phone'], 'required'],
			[['city_id', 'brand_id', 'creater', 'updater', 'valid'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['company_name', 'company_address'], 'string', 'max' => 100],
			[['phone'], 'string', 'max' => 50],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '设施维修中心编号',
			'city_id' => '城市编号',
			'brand_id' => '设施品牌编号',
			'company_name' => '公司名称',
			'company_address' => '公司地址',
			'phone' => '联系电话',
			'creater' => '创建者id',
			'created_at' => '创建时间',
			'updater' => '更新者id',
			'updated_at' => '更新时间',
			'valid' => '0已经删除，1有效',
		];
	}

	public static function saveDataFromSpider($data, $city_data)
	{
		foreach ($data as $brand_id => $v) {
			$brand_data = $v['brand'];
			HllEquipmentBrand::updateDataFromCenter($brand_id, $brand_data);
			echo "开始保存分类id:" . $brand_id . " 的站点数据\n";
			$center_data = [];
			foreach ($v['center'] as $center) {
				$city_id = array_search($center['city'], $city_data) + 0;

				$center_data[] = [
					'brand_id' => $brand_id,
					'company_name' => $center['name'],
					'company_address' => $center['address'],
					'phone' => $center['phone'],
					'city_id' => $city_id,
				];
			}
			if ($center_data) {
				static::getDb()->createCommand()->batchInsert('hll_equipment_service_center', array_keys($center_data[0]), $center_data)->execute();
				echo "保存结束\n";
			} else {
				echo "no data\n";
			}

		}
	}
}