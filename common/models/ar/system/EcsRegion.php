<?php

namespace common\models\ar\system;

use Yii;

/**
 * This is the model class for table "ecs_region".
 *
 * @property integer $region_id
 * @property integer $parent_id
 * @property string $region_name
 * @property integer $region_type
 * @property integer $agency_id
 * @property string $first_letter
 */
class EcsRegion extends \yii\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'ecs_region';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['parent_id', 'region_type', 'agency_id'], 'integer'],
			[['first_letter'], 'required'],
			[['region_name'], 'string', 'max' => 120],
			[['first_letter'], 'string', 'max' => 1],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'region_id' => 'Region ID',
			'parent_id' => 'Parent ID',
			'region_name' => 'Region Name',
			'region_type' => 'Region Type',
			'agency_id' => 'Agency ID',
			'first_letter' => 'First Letter',
		];
	}

	public static function getName($id)
	{
		$r = static::findOne(['region_id' => $id]);
		return $r ? $r->getAttribute('region_name') : '';
	}
}
