<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_wf_case".
 *
 * @property string $id
 * @property integer $work_id
 * @property integer $status_id
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllWfCase extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_wf_case';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['work_id', 'creater', 'updater', 'valid','status_id'], 'integer'],
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
            'work_id' => 'Work ID',
            'status_id' => 'Status ID',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    public static  function getNextStatus($case_id) {

    }
    public static  function getFlow($where) {
        return (new Query())->select(['id','user_id','current_status_id','next_status_id','need_cust'])
            ->from('hll_wf_flow')->where(['valid' => 1])->andWhere($where)->one();
    }
}
