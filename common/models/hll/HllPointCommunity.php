<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
use yii\db\Query;
/**
 * This is the model class for table "hll_point_community".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $give_point
 * @property string $point
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllPointCommunity extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_point_community';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'give_point', 'point', 'creater', 'updater', 'valid'], 'integer'],
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
            'community_id' => 'Community ID',
            'give_point' => 'Give Point',
            'point' => 'Point',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //获取小区积分余额
    public static function getPointsByCommunity($community_id){
        $point = (new Query())->select(['point'])->from('hll_point_community')
            ->where(['community_id'=>$community_id,'valid'=>1])->scalar();
        $point = (!$point) ? 0 : intval($point);
        return $point;
    }
}
