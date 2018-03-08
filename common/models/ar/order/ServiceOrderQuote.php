<?php

namespace common\models\ar\order;

use Yii;
use yii\db\Query;
use common\models\ar\service\ServiceQuote;

/**
 * This is the model class for table "service_order_quote".
 *
 * @property string $id
 * @property integer $order_id
 * @property integer $quote_id
 * @property integer $quality
 * @property string $total_price
 * @property string $created_at
 */
class ServiceOrderQuote extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'service_order_quote';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'quote_id', 'quality'], 'integer'],
            [['total_price'], 'number'],
            [['created_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => '订单ID',
            'quote_id' => '报价ID',
            'quality' => '基于该报价的订购数量',
            'total_price' => '该项报价总额，service_quote(quote_id).price * amount',
            'created_at' => 'Created At',
        ];
    }

	public function getServicequote() {
		return $this->hasOne(ServiceQuote::className(), ['id' => 'quote_id']);
	}

    /**
     * 获取订单报价xinxi
     * @param id 订单id
     */
    public static function quoteDetail($id) {
        $data = (new Query())->select(['t1.title', 't2.quality', 't2.total_price'])
            ->from('service_quote as t1')
            ->leftJoin('service_order_quote as t2', 't2.quote_id=t1.id')
            ->where('t2.order_id='.$id)
            ->all();
        return $data;
        
    }
}
