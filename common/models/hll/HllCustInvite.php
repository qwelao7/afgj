<?php
/**
 *
 * @author: xuyi
 * @date: 2017/3/2
 */
namespace common\models\hll;

use common\components\ActiveRecord;
use Yii;

/**
 * This is the model class for table "hll_cust_invite".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $invite_date
 * @property string $cust_name
 * @property string $cust_mobile
 * @property string $invite_time
 * @property string $agent_name
 * @property integer $invite_code
 * @property integer $code_use_num
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllCustInvite extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'hll_cust_invite';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['community_id', 'invite_date'], 'required'],
			[['community_id', 'invite_code', 'code_use_num', 'creater', 'updater', 'valid'], 'integer'],
			[['invite_date', 'created_at', 'updated_at'], 'safe'],
			[['cust_name', 'agent_name'], 'string', 'max' => 50],
			[['cust_mobile'], 'string', 'max' => 20],
			[['invite_time'], 'string', 'max' => 11],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '邀请码编号',
			'community_id' => '小区编号',
			'invite_date' => '来访日期',
			'cust_name' => '客户姓名',
			'cust_mobile' => '客户手机号',
			'invite_time' => '来访时间',
			'agent_name' => '经纪人姓名',
			'invite_code' => '通行码',
			'code_use_num' => '通行码可用次数，验证一次少一次，为0就不可用了',
			'creater' => '创建者id',
			'created_at' => '创建时间',
			'updater' => '更新者id',
			'updated_at' => '更新时间',
			'valid' => '0已经删除，1有效',
		];
	}

	public static function sendSms($id){
		$model = static::findOne($id);
		$time = date('m-d',strtotime($model->invite_date)).'  '."$model->invite_time";
		return Yii::$app->sms->send($model->cust_mobile, 'customerInvite', [
			'num' => $model->invite_code,
			'name' => $model->cust_name,
			'time' => $time,
			'agent' => $model->agent_name,
		],'宝华桃李春风');
	}
}