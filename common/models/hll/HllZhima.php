<?php
/**
 *
 * @author: xuyi
 * @date: 2017/3/28
 */
namespace common\models\hll;

use Yii;

/**
 * This is the model class for table "hll_zhima".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $open_id
 * @property integer $zm_score
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllZhima extends \yii\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'hll_zhima';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['user_id'], 'required'],
			[['user_id', 'zm_score', 'creater', 'updater', 'valid'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['open_id'], 'string', 'max' => 255],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'id',
			'user_id' => '用户id',
			'open_id' => '芝麻open_id',
			'zm_score' => '芝麻信用分',
			'creater' => '创建者id',
			'created_at' => '创建时间',
			'updater' => '更新者id',
			'updated_at' => '更新时间',
			'valid' => '0已经删除，1有效',
		];
	}

	/**
	 * 获取芝麻积分等级
	 * @param $zm_score
	 * @return string
	 */
	public static function getScoreLevel($zm_score){
		$level = '';
		switch(true){
			case $zm_score < 550;
				$level = '较差';
				break;
			case $zm_score < 600;
				$level = '中等';
				break;
			case $zm_score < 650;
				$level = '良好';
				break;
			case $zm_score < 700;
				$level = '优秀';
				break;
			case $zm_score < 950;
				$level = '极好';
				break;
			default:
				$level = '数据错误';
				break;
		}
		return $level;
	}
}