<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_item_sharing_praise".
 *
 * @property integer $id
 * @property integer $is_id
 * @property integer $creater
 * @property string $created_at
 */
class ItemSharingPraise extends ActiveRecord
{
    /**
     * Created by PhpStorm.
     * User: kaikai.qin
     * Date: 2016/10/9
     * Time: 18:54
     */
    public static function tableName() {
        return 'hll_item_sharing_praise';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'is_id', 'creater'], 'integer'],
            [['is_id'], 'required'],
            [['created_at'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '物品共享点赞编号',
            'is_id' => '物品共享编号',
            'creater' => '创建者id',
            'created_at' => '创建时间',
        ];
    }
    /**
     * @param $id int 共享物品Id
     * @return array
     */
    public static function getPraiseBySharingId($id){
        $praise = (new Query())->select(['t1.id','t1.created_at','t2.nickname','t2.headimgurl'])
            ->from('hll_item_sharing_praise as t1')
            ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.creater')
            ->where(['t1.is_id'=>$id])->orderBy(['id'=>SORT_ASC])->all();
        return $praise;
    }
}
