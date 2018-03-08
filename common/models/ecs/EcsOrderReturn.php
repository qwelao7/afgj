<?php

namespace common\models\ecs;

use Yii;

/**
 * This is the model class for table "ecs_order_return".
 *
 * @property integer $ret_id
 * @property string $service_sn
 * @property integer $goods_id
 * @property integer $user_id
 * @property integer $rec_id
 * @property integer $order_id
 * @property string $order_sn
 * @property integer $service_id
 * @property integer $cause_id
 * @property string $add_time
 * @property string $should_return
 * @property string $actual_return
 * @property string $remark
 * @property integer $country
 * @property integer $province
 * @property integer $city
 * @property integer $district
 * @property string $addressee
 * @property string $phone
 * @property string $address
 * @property integer $zipcode
 * @property integer $return_status
 * @property integer $refund_status
 * @property string $back_shipping_name
 * @property string $back_other_shipping
 * @property string $back_invoice_no
 * @property string $out_shipping_name
 * @property string $out_invoice_no
 * @property integer $seller_id
 * @property integer $is_check
 * @property string $to_buyer
 */
class EcsOrderReturn extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_order_return';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['service_sn', 'goods_id', 'user_id', 'rec_id', 'order_id', 'order_sn', 'service_id', 'cause_id', 'add_time', 'should_return', 'actual_return', 'remark', 'country', 'province', 'city', 'district', 'addressee', 'phone', 'address', 'return_status', 'refund_status', 'back_shipping_name', 'back_other_shipping', 'back_invoice_no', 'out_shipping_name', 'out_invoice_no', 'seller_id', 'is_check', 'to_buyer'], 'required'],
            [['goods_id', 'user_id', 'rec_id', 'order_id', 'service_id', 'cause_id', 'country', 'province', 'city', 'district', 'zipcode', 'return_status', 'refund_status', 'seller_id', 'is_check'], 'integer'],
            [['should_return', 'actual_return'], 'number'],
            [['remark'], 'string'],
            [['service_sn', 'order_sn', 'phone'], 'string', 'max' => 20],
            [['add_time'], 'string', 'max' => 120],
            [['addressee', 'back_shipping_name', 'back_other_shipping', 'out_shipping_name'], 'string', 'max' => 30],
            [['address'], 'string', 'max' => 100],
            [['back_invoice_no', 'out_invoice_no'], 'string', 'max' => 50],
            [['to_buyer'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ret_id' => 'Ret ID',
            'service_sn' => 'Service Sn',
            'goods_id' => 'Goods ID',
            'user_id' => 'User ID',
            'rec_id' => 'Rec ID',
            'order_id' => 'Order ID',
            'order_sn' => 'Order Sn',
            'service_id' => 'Service ID',
            'cause_id' => 'Cause ID',
            'add_time' => 'Add Time',
            'should_return' => 'Should Return',
            'actual_return' => 'Actual Return',
            'remark' => 'Remark',
            'country' => 'Country',
            'province' => 'Province',
            'city' => 'City',
            'district' => 'District',
            'addressee' => 'Addressee',
            'phone' => 'Phone',
            'address' => 'Address',
            'zipcode' => 'Zipcode',
            'return_status' => 'Return Status',
            'refund_status' => 'Refund Status',
            'back_shipping_name' => 'Back Shipping Name',
            'back_other_shipping' => 'Back Other Shipping',
            'back_invoice_no' => 'Back Invoice No',
            'out_shipping_name' => 'Out Shipping Name',
            'out_invoice_no' => 'Out Invoice No',
            'seller_id' => 'Seller ID',
            'is_check' => 'Is Check',
            'to_buyer' => 'To Buyer',
        ];
    }
}
