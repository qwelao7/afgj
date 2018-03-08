<?php

namespace common\models\hll;

use common\models\ecs\EcsGoods;
use common\models\hll\HllPointCommunity;
use Yii;
use common\components\ActiveRecord;
use yii\base\Exception;
use yii\db\Query;
/**
 * This is the model class for table "hll_user_points_log".
 *
 * @property integer $id
 * @property integer $unique_id
 * @property integer $order_id
 * @property integer $community_id
 * @property integer $point_id
 * @property integer $item_id
 * @property integer $user_id
 * @property integer $point
 * @property integer $left_points
 * @property integer $type
 * @property integer $scenes
 * @property integer $business_id
 * @property integer $period
 * @property string $used_time
 * @property string $category
 * @property string $icon
 * @property string $remark
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllUserPointsLog extends ActiveRecord
{


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'hll_user_points_log';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['user_id', 'point','type', 'business_id', 'period', 'creater', 'updater', 'valid','left_points','point_id'], 'integer'],
			[['created_at', 'updated_at','remark','scenes','unique_id','icon','category','order_id','community_id','item_id'], 'safe'],
            ['period','default','value'=>intval(f_date(time(),4))]
		];
	}
    CONST EXPEND_POINT_TYPE=1;
    CONST EXPIRED_POINT_TYPE=2;
    CONST INCOME_POINT_TYPE=3;

    public static $scenes_type = [0=>"admin",1=>"system",2=>"user_shopping",3=>"neighbor",4=>'community'];

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'id',
			'unique_id' => 'Unique Id',
			'user_id' => '用户 id',
            'left_points' => '剩余积分',
			'point' => '积分',
            'type' => '日志类型',
            'scenes' => '变动场景',
            'remark' => '备注',
			'business_id' => '商家 id 1为回来啦',
			'period' => '周期',
			'used_time' => '使用时间',
			'creater' => '创建者id',
			'created_at' => '创建时间',
			'updater' => '更新者id',
			'updated_at' => '更新时间',
			'valid' => '0已经删除，1有效',
		];
	}

    public function beforeSave($insert)
    {

        if (!parent::beforeSave($insert)) return FALSE;
        switch ($this->type){
            case 1:
                $this->left_points = HllUserPoints::getUserPoints($this->user_id,$this->community_id,0) - $this->point;
                break;
            case 3:
                $this->left_points = HllUserPoints::getUserPoints($this->user_id,$this->community_id,0) + $this->point;
                break;
        }
        return TRUE;
    }

    /**
     * 用户在商家消费
     * @param $userId
     * @param $businessId
     * @param $points
     * @param int $needMoney
     * @param string $remark
     * @author zend.wang
     * @time 2017-11-14 15:00
     */
	public static function businessExpend($userId,$businessId,$money,$remark='') {

        $community = HllUserPoints::getCommunityByBusiness($businessId);
        $userPoints = HllUserPoints::getUserPoints($userId,$community, 1);
        if(!$userPoints ) {
            throw new Exception("友元余额不足", 105);
        }
        $needPoints = intval($money*100);
        if( $needPoints> $userPoints ) {
            throw new Exception("友元余额不足", 106);
        }
        $businessName = (new Query())->select(['name','logo'])
            ->from('hll_business')->where(['id' => $businessId,'is_show'=>1])->one();

        if(!$businessName) {
            throw new Exception("商家不存在", 107);
        }
        $data['item_id'] = 0;
        $data['icon'] = 'http://pub.huilaila.net/'.$businessName['logo'];
        $data['remark'] = $businessName['name'] ." ".$remark;
        $data['type'] = static::EXPEND_POINT_TYPE;
        $data['scenes'] = static::$scenes_type[2];
        $data['category'] = ' ';
        $data['change_reason'] = '线下商家消费收入';
        $data['business_id'] = $businessId;
        $data['order_id'] = 0;

        $community = HllUserPoints::getCommunityByBusiness($businessId);
        try {
            $result = HllUserPoints::expendPoints($userId, $needPoints,$data,$community,$businessId,2);//给用户减积分
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), 103);
        }
    }

    /**
     * 用户购物消费
     * @param $user_id
     * @param $goods_id
     * @param $points
     * @param $type 1 为需要付现金  2 为不要付现金
     * @return bool
     * @throws Exception
     */
    public static function shippingExpend($order_id, $user_id,$goods_id,$points,$type){
        $goods = EcsGoods::findOne($goods_id);
        $data['item_id'] = $goods->goods_id;
        $data['category'] = 'goods';
        if(strtolower(substr($goods->goods_thumb, 0, 4)) == 'data'){
            $data['icon'] = 'http://mall.afguanjia.com/'.$goods->goods_thumb;
        }else{
            $data['icon'] = 'http://pub.huilaila.net/'.$goods->goods_thumb;
        }
        $data['remark'] = $goods->goods_name;
        $data['type'] = static::EXPEND_POINT_TYPE;
        $data['scenes'] = static::$scenes_type[2];
        $data['change_reason'] = '线上购物消费收入';
        $data['business_id'] = $goods->business_id;
        $data['order_id'] = $order_id;
        $community = HllUserPoints::getCommunityByBusiness($goods['business_id']);
        try{
            HllUserPoints::expendPoints($user_id, $points,$data,$community,$goods->business_id,$type);
            return true;
        }catch (Exception $e){
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 游戏积分获取
     * @param $item_id
     * @param $user_id
     * @param $data
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function gameExpend($item_id,$user_id,$data){

        $unique_id = uniqid('hll_point_');
        try{
            $point_log = new HllUserPointsLog();
            $point_log->unique_id = $unique_id;
            $point_log->user_id = $user_id;
            $point_log->item_id = $item_id;
            $point_log->category = 'games';
            $point_log->point = intval($data['point']);
            $point_log->community_id = $data['community_id'];
            $point_log->type = static::INCOME_POINT_TYPE;
            $point_log->scenes = static::$scenes_type[4];
            $point_log->remark = $data['remark'];
            if($point_log->save()){
                $community_log = new HllPointCommunityLog();
                $community_log->unique_id = $unique_id;
                $community_log->community_id = $data['community_id'];
                $community_log->user_id = $user_id;
                $community_log->point = $data['point'];
                $community_log->left_points = HllPointCommunity::getPointsByCommunity($data['community_id']);
                $community_log->type = HllPointCommunityLog::GIVE_POINT_TYPE;
                $community_log->change_reason = $data['remark'];
                if($community_log->save()){
                    $user_point = HllUserPoints::findOne(['user_id'=>$user_id,'community_id'=>$data['community_id'],
                        'expire_time'=>$data['expire_time'],'point_type'=>2]);
                    if(!$user_point){
                        $user_point = new HllUserPoints();
                        $user_point->user_id = $user_id;
                        $user_point->expire_time = $data['expire_time'];
                        $user_point->point = $data['point'];
                        $user_point->point_type = 2;
                        $user_point->community_id = $data['community_id'];
                    }else{
                        $user_point->point += $data['point'];
                    }
                    if($user_point->save()){

                        return true;
                    }else{
                        throw new Exception('保存失败',103);
                    }
                }else{
                    throw new Exception('社区友元记录添加失败',102);
                }
            }else{
                throw new Exception('用户友元记录添加失败',101);
            }
        }catch (Exception $e){
            throw new Exception($e->getCode(),$e->getMessage());
        }
    }

    /**
     * 活动签到积分获取
     * @param $item_id
     * @param $user_id
     * @param $data
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function signExpend($user_id,$data){
        $trans = Yii::$app->db->beginTransaction();
        $unique_id = uniqid('hll_point_');
        try{
            $point_log = new HllUserPointsLog();
            $point_log->unique_id = $unique_id;
            $point_log->user_id = $user_id;
            $point_log->item_id = 0;
            $point_log->category = 'sign';
            $point_log->point = intval($data['point']);
            $point_log->community_id = $data['community_id'];
            $point_log->type = static::INCOME_POINT_TYPE;
            $point_log->scenes = static::$scenes_type[4];
            $point_log->remark = $data['remark'];
            if($point_log->save()){
                $community_log = new HllPointCommunityLog();
                $community_log->unique_id = $unique_id;
                $community_log->community_id = $data['community_id'];
                $community_log->user_id = $user_id;
                $community_log->point = $data['point'];
                $community_log->left_points = HllPointCommunity::getPointsByCommunity($data['community_id']);
                $community_log->type = HllPointCommunityLog::GIVE_POINT_TYPE;
                $community_log->change_reason = $data['remark'];
                if($community_log->save()){
                    $user_point = HllUserPoints::findOne(['user_id'=>$user_id,'community_id'=>$data['community_id'],
                        'expire_time'=>$data['expire_time'],'point_type'=>2]);
                    if(!$user_point){
                        $user_point = new HllUserPoints();
                        $user_point->user_id = $user_id;
                        $user_point->expire_time = $data['expire_time'];
                        $user_point->point = $data['point'];
                        $user_point->point_type = 2;
                        $user_point->community_id = $data['community_id'];
                    }else{
                        $user_point->point += $data['point'];
                    }
                    if($user_point->save()){
                        $trans->commit();
                        return true;
                    }else{
                        throw new Exception('保存失败',103);
                    }
                }else{
                    throw new Exception('社区友元记录添加失败',102);
                }
            }else{
                throw new Exception('用户友元记录添加失败',101);
            }
        }catch (Exception $e){
            $trans->rollBack();
            throw new Exception($e->getCode(),$e->getMessage());
        }
    }
}

