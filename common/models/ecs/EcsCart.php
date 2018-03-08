<?php

namespace common\models\ecs;

use Yii;

/**
 * This is the model class for table "ecs_cart".
 *
 * @property string $rec_id
 * @property string $user_id
 * @property string $session_id
 * @property string $goods_id
 * @property string $goods_sn
 * @property string $product_id
 * @property string $goods_name
 * @property string $market_price
 * @property string $goods_price
 * @property integer $goods_number
 * @property string $goods_attr
 * @property integer $is_real
 * @property string $extension_code
 * @property string $parent_id
 * @property integer $rec_type
 * @property integer $is_gift
 * @property integer $is_shipping
 * @property integer $can_handsel
 * @property string $goods_attr_id
 * @property string $group_id
 */
class EcsCart extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_cart';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'goods_id', 'product_id', 'goods_number', 'is_real', 'parent_id', 'rec_type', 'is_gift', 'is_shipping', 'can_handsel'], 'integer'],
            [['market_price', 'goods_price'], 'number'],
            [['goods_attr'], 'string'],
            [['session_id'], 'string', 'max' => 32],
            [['goods_sn'], 'string', 'max' => 60],
            [['goods_name'], 'string', 'max' => 120],
            [['extension_code'], 'string', 'max' => 30],
            [['goods_attr_id', 'group_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rec_id' => 'Rec ID',
            'user_id' => 'User ID',
            'session_id' => 'Session ID',
            'goods_id' => 'Goods ID',
            'goods_sn' => 'Goods Sn',
            'product_id' => 'Product ID',
            'goods_name' => 'Goods Name',
            'market_price' => 'Market Price',
            'goods_price' => 'Goods Price',
            'goods_number' => 'Goods Number',
            'goods_attr' => 'Goods Attr',
            'is_real' => 'Is Real',
            'extension_code' => 'Extension Code',
            'parent_id' => 'Parent ID',
            'rec_type' => 'Rec Type',
            'is_gift' => 'Is Gift',
            'is_shipping' => 'Is Shipping',
            'can_handsel' => 'Can Handsel',
            'goods_attr_id' => 'Goods Attr ID',
            'group_id' => 'Group ID',
        ];
    }
}
