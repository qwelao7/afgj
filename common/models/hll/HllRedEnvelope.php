<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_red_envelope".
 *
 * @property string $id
 * @property string $title
 * @property string $wishing
 * @property integer $retype
 * @property string $total_money
 * @property integer $total_num
 * @property string $start_time
 * @property string $end_time
 * @property integer $share_return
 * @property integer $return_point
 * @property integer $taken_num
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllRedEnvelope extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_red_envelope';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['retype', 'total_num', 'share_return', 'return_point', 'taken_num', 'creater', 'updater', 'valid'], 'integer'],
            [['total_money', 'start_time', 'end_time'], 'required'],
            [['total_money'], 'number'],
            [['start_time', 'end_time', 'created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 20],
            [['wishing'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '红包编号',
            'title' => '红包标题',
            'wishing' => '祝福语',
            'retype' => '红包类型：1拼手气红包，2普通红包，3购物红包',
            'total_money' => '红包总金额',
            'total_num' => '红包总份数',
            'start_time' => '开始发放时间',
            'end_time' => '结束发放时间',
            'share_return' => '分享返积分：1返等值积分，2返固定积分，3不返积分',
            'return_point' => '固定积分值',
            'taken_num' => '抢走份数',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
}
