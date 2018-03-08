<?php

namespace common\models\hll;
use Yii;

/**
 * This is the model class for table "hll_duiba_orders".
 *
 * @property integer $id
 * @property integer $app_id
 * @property integer $user_id
 * @property integer $credits
 * @property integer $actual_price
 * @property string $duiba_order_num
 * @property integer $order_status
 * @property integer $credits_status
 * @property string $type
 * @property string $description
 * @property string $gmt_create
 * @property string $gmt_modified
 */
class HllDuibaOrders extends \yii\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'hll_duiba_orders';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['app_id', 'user_id'], 'required'],
			[['app_id', 'user_id', 'credits', 'actual_price', 'order_status', 'credits_status'], 'integer'],
			[['gmt_create', 'gmt_modified'], 'safe'],
			[['duiba_order_num', 'description'], 'string', 'max' => 255],
			[['type'], 'string', 'max' => 40],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'app_id' => 'App ID',
			'user_id' => 'User ID',
			'credits' => 'Credits',
			'actual_price' => 'Actual Price',
			'duiba_order_num' => 'Duiba Order Num',
			'order_status' => 'Order Status',
			'credits_status' => 'Credits Status',
			'type' => 'Type',
			'description' => 'Description',
			'gmt_create' => 'Gmt Create',
			'gmt_modified' => 'Gmt Modified',
		];
	}
}