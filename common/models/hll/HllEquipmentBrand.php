<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_equipment_brand".
 *
 * @property integer $id
 * @property integer $type_id
 * @property string $name
 * @property integer $cust_service_phone
 * @property string $service_time
 * @property string $service_info
 * @property string $service_policy_url
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEquipmentBrand extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'hll_equipment_brand';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['type_id', 'name'], 'required'],
			[['type_id', 'cust_service_phone', 'creater', 'updater', 'valid'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['name', 'service_time', 'service_policy_url'], 'string', 'max' => 100],
			[['service_info'], 'string', 'max' => 1000],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '设备品牌编号',
			'type_id' => '设备类型编号,kv表type=1',
			'name' => '设备品牌名',
			'cust_service_phone' => '客服电话',
			'service_time' => '服务时间',
			'service_info' => '保修信息',
			'service_policy_url' => '保修政策网址',
			'creater' => '创建者id',
			'created_at' => '创建时间',
			'updater' => '更新者id',
			'updated_at' => '更新时间',
			'valid' => '0已经删除，1有效',
		];
	}

	public static function saveDataFromSpider($data)
	{
		foreach ($data as $cate_id => $v) {
			echo "开始保存分类:" . $cate_id . " 数据\n";
			$c = [];
			foreach ($v as $value) {
				$c[] = [
					'type_id' => $cate_id,
					'name' => $value['name'],
					'service_policy_url' => $value['url'],
				];
			}
			static::getDb()->createCommand()->batchInsert('hll_equipment_brand', array_keys($c[0]), $c)->execute();
			echo "保存结束\n";
		}
	}

	public static function updateDataFromCenter($id, $data)
	{
		$brand = static::findOne($id);
		if ($data['cust_service_phone']) {
			$brand->cust_service_phone = $data['cust_service_phone'];
		}
		if ($data['service_info']) {
			$brand->service_info = $data['service_info'];
		}
		if ($data['service_time']) {
			$brand->service_time = $data['service_time'];
		}
		if ($data['service_policy_url']) {
			$brand->service_policy_url = $data['service_policy_url'];
		}
		$brand->save(false);
	}

	//获取指定品牌的id
	public static function getBrandByName($brand_name,$type){
		$brand = new HllEquipmentBrand();
		$brand->name = $brand_name;
		$brand->type_id = $type;
		$brand->save(false);
		return $brand->id;
	}
}