<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_red_envelope_detail".
 *
 * @property string $id
 * @property integer $reid
 * @property string $remoney
 * @property integer $user_id
 * @property string $taken_time
 * @property integer $send_status
 * @property integer $return_status
 * @property integer $return_point
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllRedEnvelopeDetail extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_red_envelope_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['reid', 'remoney', 'taken_time'], 'required'],
            [['reid', 'user_id', 'send_status', 'return_status', 'return_point', 'creater', 'updater', 'valid'], 'integer'],
            [['taken_time', 'created_at', 'updated_at'], 'safe'],
            [['remoney'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '红包详情编号',
            'reid' => '红包编号',
            'remoney' => '红包金额或序列号',
            'user_id' => '抢到用户编号',
            'taken_time' => '抢到时间',
            'send_status' => '发送状态：1未发送，2已发送',
            'return_status' => '返积分状态：1未返积分，2已返积分',
            'return_point' => '返积分值',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

}
