<?php
namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;

/**
 * This is the model class for table "hll_item_sharing".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $account_id
 * @property integer $share_type
 * @property integer $borrow_item_type
 * @property integer $sell_item_type
 * @property integer $sell_item_price
 * @property string $item_desc
 * @property string $item_pics
 * @property integer $thanks_num
 * @property integer $praise_num
 * @property integer $comment_num
 * @property integer $status
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class ItemSharing extends ActiveRecord
{
    /**
     * Created by PhpStorm.
     * User: kaikai.qin
     * Date: 2016/10/9
     * Time: 17:40
     */
    public static function tableName() {
        return 'hll_item_sharing';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['community_id','account_id','share_type'], 'required'],
            [['id','community_id','account_id','thanks_num','praise_num','comment_num'], 'integer'],
            [['item_desc'], 'string', 'max' => 500],
            [['item_pics'], 'string', 'max' => 5000],
            [['sell_item_price'], 'double'],
            [['valid','status','share_type','borrow_item_type','sell_item_type'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '物品共享信息编号',
            'community_id' => '物品共享信息归属小区编号',
            'account_id' => '用户编号',
            'share_type' => '分享类型',
            'borrow_item_type' => '借用物品类型',
            'sell_item_type' => '小市物品类型',
            'sell_item_price' => '小市物品价格',
            'item_desc' => '物品描述',
            'item_pics' => '物品照片集',
            'thanks_num' => '感谢数',
            'praise_num' => '赞数',
            'comment_num' => '评论数',
            'status' => '物品状态',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '有效',
        ];
    }

    /**
     * @param $type
     * @return string
     * 获取借用类型
     */
    public static function getBorrowType($type){
        $result = '';
        if($type == 1){
            $result = '工具';
        }
        if($type == 2){
            $result = '卡券';
        }
        if($type == 3){
            $result = '图书';
        }
        if($type == 4){
            $result = '其它';
        }
        return $result;
    }

    /**
     * @param $type
     * @return string
     * 获取小市列表
     * 1女装，2数码，3母婴，4美妆，5童装，6其它
     */
    public static function getSellType($type){
        $result = '';
        if($type == 1){
            $result = '女装';
        }
        if($type == 2){
            $result = '数码';
        }
        if($type == 3){
            $result = '母婴';
        }
        if($type == 4){
            $result = '美妆';
        }
        if($type == 5){
            $result = '童装';
        }
        if($type == 6){
            $result = '其它';
        }
        return $result;
    }
}