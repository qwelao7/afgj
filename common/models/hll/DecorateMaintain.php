<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "decorate_maintain".
 *
 * @property string $id
 * @property integer $user_id
 * @property integer $case_id
 * @property integer $decorate_id
 * @property integer $material_id
 * @property string $failure_cause
 * @property string $failure_pics
 * @property string $contact_name
 * @property string $contact_phone
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class DecorateMaintain extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'decorate_maintain';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'case_id', 'decorate_id', 'material_id', 'failure_cause', 'contact_name', 'contact_phone'], 'required'],
            [['user_id', 'case_id', 'decorate_id', 'material_id', 'creater', 'updater', 'valid'], 'integer'],
            [['failure_pics', 'created_at', 'updated_at'], 'safe'],
            [['failure_cause', 'failure_pics'], 'string', 'max' => 100],
            [['contact_name'], 'string', 'max' => 10],
            [['contact_phone'], 'string', 'max' => 20],
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
            'case_id' => 'Case ID',
            'decorate_id' => 'Decorate ID',
            'material_id' => 'Material ID',
            'failure_cause' => 'Failure Cause',
            'failure_pics' => 'Failure Pics',
            'contact_name' => 'Contact Name',
            'contact_phone' => 'Contact Phone',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
