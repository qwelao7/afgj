<?php
/**
 *
 * @author: xuyi
 * @date: 2017/3/28
 */
namespace common\models\hll;

use Yii;

/**
 * This is the model class for table "hll_zhima_log".
 *
 * @property integer $id
 * @property string $transaction_id
 * @property string $biz_no
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllZhimaLog extends \yii\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'hll_zhima_log';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['creater', 'updater', 'valid'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['transaction_id', 'biz_no'], 'string', 'max' => 100],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '图书馆编号',
			'transaction_id' => 'hll交易id',
			'biz_no' => '芝麻返回id',
			'creater' => '创建者id',
			'created_at' => '创建时间',
			'updater' => '更新者id',
			'updated_at' => '更新时间',
			'valid' => '0已经删除，1有效',
		];
	}
}