<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;

/**
 * This is the model class for table "hll_public_benefit_donate".
 *
 * @property string $id
 * @property integer $pb_id
 * @property integer $user_id
 * @property string $wish
 * @property string $donate_money
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllPublicBenefitDonate extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_public_benefit_donate';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pb_id', 'user_id', 'creater', 'updater', 'valid'], 'integer'],
            [['donate_money'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['wish'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pb_id' => 'Pb ID',
            'user_id' => 'User ID',
            'wish' => 'Wish',
            'donate_money' => 'Donate Money',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
