<?php

namespace common\models\hll;

use Yii;

/**
 * This is the model class for table "hll_kv".
 *
 * @property integer $id
 * @property integer $kv_type
 * @property integer $kv_key
 * @property string $kv_value
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllKv extends \yii\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'hll_kv';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['kv_type', 'kv_key', 'creater', 'updater', 'valid'], 'integer'],
			[['kv_value'], 'required'],
			[['created_at', 'updated_at'], 'safe'],
			[['kv_value'], 'string', 'max' => 50],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '键值对编号',
			'kv_type' => '键值对类型：1设施类型，2养护内容，3维修中心反馈原因',
			'kv_key' => '键',
			'kv_value' => '值',
			'creater' => '创建者id',
			'created_at' => '创建时间',
			'updater' => '更新者id',
			'updated_at' => '更新时间',
			'valid' => '0已经删除，1有效',
		];
	}
}
