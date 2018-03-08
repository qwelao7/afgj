<?php

namespace common\models\hll;
use Yii;
use yii\db\Query;

/**
 * This is the model class for table "hll_spring_award".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $award_grade
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllSpringAward extends \yii\db\ActiveRecord
{
	const FIRST_AWARD = 1;
	const SECOND_AWARD = 2;
	const THIRD_AWARD = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_spring_award';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'award_grade', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'award_grade' => 'Award Grade',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    public function saveAward($list){
		$time = date('Y-m-d H:i:s', time());
		if ($list){
			$data = [];
			foreach ($list as $award=> $users){
				foreach ($users as $u){
					$data[] = [
						'user_id' => $u,
						'award_grade' => $award,
						'valid' => '1',
						'creater' => '1',
						'created_at' => $time,
						'updater' => '1',
						'updated_at' => $time,
					];
				}

			}
			HllSpringAward::getDb()->createCommand()->batchInsert('hll_spring_award', array_keys($data[0]), $data)->execute();
		}
	}


	public static function getUsersAward()
	{
		$res = [
			HllSpringAward::FIRST_AWARD =>HllSpringAward::find()->where(['award_grade' => HllSpringAward::FIRST_AWARD, 'valid' => '1'])->count(),
			HllSpringAward::SECOND_AWARD =>HllSpringAward::find()->where(['award_grade' => HllSpringAward::SECOND_AWARD, 'valid' => '1'])->count(),
			HllSpringAward::THIRD_AWARD =>HllSpringAward::find()->where(['award_grade' => HllSpringAward::THIRD_AWARD, 'valid' => '1'])->count(),
		];
		return $res;
	}

	public static function getAwardResult()
	{
		$fields = ['t2.headimgurl', 't2.nickname'];

		$data = [
			HllSpringAward::FIRST_AWARD =>(new Query())->select($fields)->from('hll_spring_award as t1')->leftJoin('ecs_wechat_user as t2', 't1.user_id = t2.ect_uid')->where(['t1.award_grade' => HllSpringAward::FIRST_AWARD, 't1.valid' => '1'])->orderBy(['t1.created_at'=>SORT_DESC])->all(),
			HllSpringAward::SECOND_AWARD =>(new Query())->select($fields)->from('hll_spring_award as t1')->leftJoin('ecs_wechat_user as t2', 't1.user_id = t2.ect_uid')->where(['t1.award_grade' => HllSpringAward::SECOND_AWARD, 't1.valid' => '1'])->orderBy(['t1.created_at'=>SORT_DESC])->all(),
			HllSpringAward::THIRD_AWARD =>(new Query())->select($fields)->from('hll_spring_award as t1')->leftJoin('ecs_wechat_user as t2', 't1.user_id = t2.ect_uid')->where(['t1.award_grade' => HllSpringAward::THIRD_AWARD, 't1.valid' => '1'])->orderBy(['t1.created_at'=>SORT_DESC])->all(),
		];

		$data = (!empty($data[HllSpringAward::FIRST_AWARD])) ? $data : array();

		return $data;
	}
}
