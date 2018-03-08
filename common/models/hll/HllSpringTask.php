<?php

namespace common\models\hll;

use Yii;

/**
 * This is the model class for table "hll_spring_task".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $task_id
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllSpringTask extends \yii\db\ActiveRecord
{

	const TASK_HOME = 1; //我家在这里
	const TASK_NEIGHBOR = 2; //邻居在这里
	const TASK_GREET = 3;    //你好邻居

	const POINT_HOME = 80;
	const POINT_NEIGHBOR = 80;
	const POINT_GREET = 80;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_spring_task';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'task_id', 'creater', 'updater', 'valid'], 'integer'],
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
            'task_id' => 'Task ID',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

	public static function checkUserTaskStatus($user_id)
	{
		$task = HllSpringTask::find()->select(['id', 'task_id'])->where([
			'user_id' => $user_id,
			'valid' => '1'
		])->orderBy('task_id asc')->indexBy('task_id')->all();
		$res = [
			HllSpringTask::TASK_HOME => false,
			HllSpringTask::TASK_NEIGHBOR => false,
			HllSpringTask::TASK_GREET => false,
		];
		foreach ($task as $k => $v) {
			if (in_array($k, [HllSpringTask::TASK_HOME, HllSpringTask::TASK_GREET, HllSpringTask::TASK_NEIGHBOR])) {
				$res[$k] = true;
			}
		}
		return $res;
	}

	public static function checkUsersTask()
	{
		$res = [
			HllSpringTask::TASK_HOME =>HllSpringTask::find()->where(['task_id' => HllSpringTask::TASK_HOME, 'valid' => '1'])->count(),
			HllSpringTask::TASK_NEIGHBOR =>HllSpringTask::find()->where(['task_id' => HllSpringTask::TASK_NEIGHBOR, 'valid' => '1'])->count(),
			HllSpringTask::TASK_GREET =>HllSpringTask::find()->where(['task_id' => HllSpringTask::TASK_GREET, 'valid' => '1'])->count(),
		];
		return $res;
	}



}
