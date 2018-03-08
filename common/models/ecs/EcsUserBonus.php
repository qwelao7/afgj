<?php

namespace common\models\ecs;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "ecs_user_bonus".
 *
 * @property string $bonus_id
 * @property integer $bonus_type_id
 * @property string $bonus_sn
 * @property string $user_id
 * @property string $used_time
 * @property string $order_id
 * @property integer $emailed
 */
class EcsUserBonus extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_user_bonus';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bonus_type_id', 'bonus_sn', 'user_id', 'used_time', 'order_id', 'emailed'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bonus_id' => 'Bonus ID',
            'bonus_type_id' => 'Bonus Type ID',
            'bonus_sn' => 'Bonus Sn',
            'user_id' => 'User ID',
            'used_time' => 'Used Time',
            'order_id' => 'Order ID',
            'emailed' => 'Emailed',
        ];
    }

    /**
     * 根据用户id获取红包
     * @param $user_id
     * @param $order_money
     * @return array
     */
    public static function getBonusByUserId($user_id,$goods_id,$order_money,$bonus_id = null,$fields=null){
        $now_time = intval(time());
        if($fields == null){
            $fields = ['t1.bonus_id','t2.type_name','t2.type_money','t2.min_goods_amount','t3.goods_list',
                "IF(t2.min_goods_amount<=$order_money,IF(t2.use_start_date<$now_time && $now_time<t2.use_end_date,1,0),0) as is_use",
                "IF(t2.use_start_date<$now_time && $now_time<t2.use_end_date,1,0) as is_expire",
                "FROM_UNIXTIME(t2.use_start_date, '%Y-%m-%d' ) as use_start_date","FROM_UNIXTIME(t2.use_end_date, '%Y-%m-%d' ) as use_end_date"];
        }

        if($bonus_id){
            $query = (new Query())->select($fields)->from('ecs_user_bonus as t1')
                ->leftJoin('ecs_bonus_type as t2','t2.type_id = t1.bonus_type_id');
            $bonus_list = $query->where(['t1.user_id'=>$user_id,'t1.order_id'=>0])
                ->andWhere(['t1.bonus_id'=>$bonus_id])->one();
        }else{
            $bonus_list = (new Query())->select($fields)->from('ecs_user_bonus as t1')
                ->leftJoin('ecs_bonus_type as t2','t2.type_id = t1.bonus_type_id')
                ->leftJoin('hll_bonus_goods as t3','t3.bonus_type_id = t1.bonus_type_id')
                ->where(['t1.user_id'=>$user_id,'t1.order_id'=>0])
                ->orderBy(['is_use'=>SORT_DESC,'bonus_id'=>SORT_ASC])->all();

            foreach($bonus_list as &$item){
                if($item['goods_list']){
                    $goods_list = explode(',',$item['goods_list']);
                    if($item['is_use'] == 1 && in_array($goods_id,$goods_list)){
                        $item['is_use'] = 1;
                    }else{
                        $item['is_use'] = 0;
                    }
                }
            }
            $is_use = array_column($bonus_list,'is_use');
            $time = array_column($bonus_list,'use_end_date');
            array_multisort($is_use,SORT_DESC,$time,SORT_ASC,$bonus_list);
        }
        return $bonus_list;
    }

    public static function getBonusNumUserId($user_id,$goods_id,$money){
        $now_time = intval(time());
        $num = 0;
        $bonus_list = (new Query())->from('ecs_user_bonus as t1')
            ->leftJoin('ecs_bonus_type as t2','t2.type_id = t1.bonus_type_id')
            ->leftJoin('hll_bonus_goods as t3','t3.bonus_type_id = t1.bonus_type_id')
            ->where(['t1.user_id'=>$user_id,'t1.order_id'=>0])
            ->andWhere(['<','t2.min_goods_amount',$money])
            ->andWhere(['<','t2.use_start_date',$now_time])
            ->andWhere(['>','t2.use_end_date',$money])->all();
        foreach($bonus_list as &$item){
            if($item['goods_list']){
                $goods_list = explode(',',$item['goods_list']);
                if(in_array($goods_id,$goods_list)){
                    $num++;
                }else{
                    continue;
                }
            }else{
                $num++;
            }
        }
        return $num;
    }
}
