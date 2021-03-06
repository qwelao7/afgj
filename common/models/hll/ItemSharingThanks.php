<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_item_sharing_thanks".
 *
 * @property integer $id
 * @property integer $is_id
 * @property integer $thanks_point
 * @property string $content
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class ItemSharingThanks extends ActiveRecord
{
    /**
     * Created by PhpStorm.
     * User: kaikai.qin
     * Date: 2016/10/9
     * Time: 18:54
     */
    public static function tableName() {
        return 'hll_item_sharing_thanks';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'is_id', 'creater','thanks_point','updater'], 'integer'],
            [['is_id'], 'required'],
            [['created_at','updated_at'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '物品共享点赞编号',
            'is_id' => '物品共享编号',
            'thanks_point' => '感谢积分',
            'content' => '感谢内容',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    /**
     * @param $id int 共享物品Id
     * @return array
     */
    public static function getThanksBySharingId($id){
        $thanks = (new Query())->select(['t1.created_at',"(t1.thanks_point* 0.01) as thanks_point",'t1.content','t2.nickname','t2.headimgurl'])
            ->from('hll_item_sharing_thanks as t1')
            ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.creater')
            ->where(['t1.is_id'=>$id,'t1.valid'=>1])->orderBy(['created_at'=> SORT_DESC])->all();
        return $thanks;
    }
}
