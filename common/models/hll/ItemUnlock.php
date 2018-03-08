<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_item_unlock".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $goods_name
 * @property string $goods_pic
 * @property integer $need_key_num
 * @property integer $use_key_num
 * @property integer $item_status
 * @property integer $receiver_id
 * @property integer $sharing_id
 * @property string $remark
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 * @property string $item_desc
 * @property string $item_pics
 */
class ItemUnlock extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_item_unlock';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'need_key_num', 'use_key_num', 'item_status', 'receiver_id', 'sharing_id', 'creater', 'updater', 'valid'], 'integer'],
            [['goods_name', 'goods_pic'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['goods_name'], 'string', 'max' => 50],
            [['goods_pic'], 'string', 'max' => 100],
            [['item_desc'], 'string', 'max' => 500],
            [['item_pics'], 'string', 'max' => 1000],
            [['remark'], 'string', 'max' => 500],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '借用物品解锁编号',
            'community_id' => '物品归属小区编号',
            'goods_name' => '商品名称',
            'goods_pic' => '商品图片',
            'need_key_num' => '需要钥匙数量',
            'use_key_num' => '使用钥匙数量',
            'item_status' => '物品状态：1、待解锁，2、待领取，3、可借用,4、待寄送',
            'receiver_id' => '领取人编号',
            'sharing_id' => '分享编号',
            'remark' => '备注',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
            'item_desc' => '描述',
            'item_pics' => '图片'
        ];
    }

    /**
     * 获取小区当前活动物品列表信息
     * @param $id 小区ID
     * @return array
     */
    public static function getItemsByCommunityId($id) {

        $items = static::find()->select(['id','goods_name','goods_pic','need_key_num','use_key_num','item_status'])
                                ->where(['community_id'=>$id,'valid'=>1])->asArray()->all();
        return $items;
    }

    /**
     * 解锁物品
     * @param $id 物品
     * @param $communityId 小区
     * @param $userId　用户
     * @return bool
     */
    public static function unlockItem($id,$communityId,$userId) {
        $db =  Yii::$app->db;
        $trans =$db->beginTransaction();
        try {
            $item = static::findOne(['id'=>$id,'community_id'=>$communityId,'valid'=>1,'item_status'=>1]);
            if($item && $item->need_key_num > $item->use_key_num) {
                $item->use_key_num = $item->use_key_num + 1;
                if($item->need_key_num == $item->use_key_num) {
                    $item->item_status = 2;
                }
                if($item->save(false)){
                    $db->createCommand()->insert('hll_item_unlock_log',['unlock_id'=>$id,'user_id'=>$userId,'unlock_time'=>f_date(time()), 'community_id'=>$communityId])->execute();
                    $trans->commit();
                    $result['status'] = $item->item_status;
                    $result['total'] = $item->need_key_num;
                    $result['now'] = $item->use_key_num;
                } else  {
                    Yii::warning('unlock save error');
                }
            }
        }catch (\yii\db\Exception $e) {
            $trans->rollBack();
            Yii::warning('unlock save exception:'.print_r($e));
        }
        return $result;
    }
    public static function isUnlockItem($community,$userId) {
        $result = true;

        $curDate = f_date(time(),2);
        $lastUnlockTime = ItemUnlockLog::find()->select('unlock_time')->where(['user_id'=>$userId,'community_id'=>$community, 'valid'=>1])->orderBy('id DESC')->scalar();
        if($lastUnlockTime) {
            $lastUnlockTime = f_date(strtotime($lastUnlockTime),2);
            if($curDate == $lastUnlockTime) {
                $result = false;
            }
        }

        return $result;
    }
}
