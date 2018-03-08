<?php

namespace common\models\hll;

use common\components\Util;
use common\components\WxTmplMsg;
use common\models\ecs\EcsUsers;
use Yii;
use yii\db\Query;
use yii\base\Exception;
use common\components\ActiveRecord;
use common\models\hll\UserAddress;
use common\models\ecs\EcsUserAddress;
use common\models\hll\Community;
use common\models\hll\HllPointCommunity;
/**
 * This is the model class for table "hll_item_sharing_comment".
 *
 * @property integer $id
 * @property string $hname
 * @property string $mobilephone
 * @property integer $community_id
 * @property string $group_name
 * @property string $building_num
 * @property string $house_num
 * @property string $unit_num
 * @property integer $creater
 * @property string  $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllHouseOwner extends ActiveRecord
{
    public static function tableName() {
        return 'hll_house_owner';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'community_id'], 'integer'],
            [['hname', 'mobilephone', 'group_name', 'building_num', 'unit_num', 'house_num'], 'string'],
            [['id', 'community_id', 'building_num', 'hname', 'mobilephone'], 'required'],
            [['created_at','updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '编号',
            'hname' => '房主姓名',
            'mobilephone' => '电话号码',
            'community_id' => '小区编号',
            'group_name' => '组团',
            'building_num' => '楼栋号',
            'unit_num' => '单元号',
            'house_num' => '房号',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    public static function createInitialAddress($mobile) {
        $fields = ['id AS hid','community_name','customer_name', 'mobile_phone', 'community_id','group_name', 'building_num', 'unit_num', 'house_num'];
        $query = (new Query())->select($fields)->from('hll_cust_house')->where(['mobile_phone'=>$mobile])->all();
        if(empty($query)) return false;
        $userId = Yii::$app->user->id;
        $flag = true;
        $trans = Yii::$app->db->beginTransaction();

        try{
            foreach($query as &$item) {
                //是否已绑定过房产
                $address = UserAddress::find()->where(['community_id'=>$item['community_id'],'group_name'=>$item['group_name'],
                    'building_num'=>$item['building_num'],'unit_num'=>$item['unit_num'],'house_num'=>$item['house_num'],'account_id'=>$userId])->one();
                if($address){
                    $address->owner_auth = 1;
                    $address->save();
                    $flag = static::BingAddressSendPoint($userId,$address,$item['community_id'],$item['community_name']);
                    if($flag){
                        continue;
                    }else{
                        $flag = false;
                        Yii::error("发放友元失败", 'address');
                        break;
                    }
                }
                else{
                    $hll['account_id'] = $userId;
                    $hll['consignee'] = $item['customer_name'];
                    $hll['mobile'] = $item['mobile_phone'];
                    $hll['owner_auth'] = 1;
                    $hll['address_desc'] = UserAddress::generateAddressDesc($item);
                    $hll = array_merge($hll, $item);
                    $address = new UserAddress();
                    if($address->load($hll, '') && $address->validate() && $address->save()) {
                        $flag = static::BingAddressSendPoint($userId,$hll,$item['community_id'],$item['community_name']);
                        if($flag){
                            continue;
                        }else{
                            $flag = false;
                            Yii::error("发放友元失败", 'address');
                            break;
                        }
                    }else {
                        $flag = false;
                        Yii::error("新建房产失败", 'address');
                    }
                }
            }
            if($flag == true){
                $trans->commit();
            }else{
                throw new Exception('数据出错',100);
            }
        }catch (Exception $e){
            $trans->rollBack();
        }
        return $flag;
    }

    public static function BingAddressSendPoint($userId,$hll,$community_id,$community_name){
        //是否发放过
        $send_log = (new Query())->from('hll_community_point_log')
            ->where(['desc'=>$hll['address_desc'],'type_id'=>1,'valid'=>1])->count();
        if($send_log == 0){
            //赠送友元
            $flag = static::sendPointByCommunity($userId,$community_id,$hll['address_desc']);
            if($flag > 1){
                $user = EcsUsers::getUser($userId, ['t1.user_id', 't2.openid','t2.nickname']);
                $left_point = HllUserPoints::getUserPoints($userId);
                $type = $community_id == 19668 ? 3 : 2;
                WxTmplMsg::PointChangeNotice($user,$flag,$left_point,$community_name,$type);
            }
        }else{
            return true;
        }
        return $flag;
    }

    public static function sendPointByCommunity($user_id,$community_id,$address_desc){
        $unique_id = uniqid('hll_point_');
        $time = date("Y-m-d H:i:s");
        $data = (new Query())->select(['id','community_id','points','business_id','expire_time','expire_type','point_type'])
            ->from('hll_community_point')->where(['community_id'=>$community_id,'type_id'=>1])
            ->andWhere(['>','deadline',$time])->all();

        if(!$data){
            return true;
        }else{
            $point = array_sum(array_column($data,'points'));
            $flag = true;
            foreach($data as $item){
                if($item['expire_type'] == 2){
                    $expire_time = Util::expireTime($item['expire_time']);
                }else{
                    $expire_time = $item['expire_time'];
                }
                if($item['point_type'] == 1){
                    $item['community_id'] = 0;
                }
                $result = static::addPointToUser($item,$user_id,$unique_id,$expire_time,$community_id);

                if($result){
                    continue;
                }else{
                    $flag = false;
                    break;
                }
            }

            if($flag){
                $community_point = HllPointCommunity::find()
                    ->where(['community_id'=>$community_id,'valid'=>1])->one();

                if($community_point){
                    $community_point->point += $point;
                    $community_point->give_point += $point;
                }else{
                    $community_point = new HllPointCommunity();
                    $community_point->community_id = $community_id;
                    $community_point->give_point = $point;
                    $community_point->point = $point;
                }
                if($community_point->save()){
                    $community_point_log = new HllPointCommunityLog();
                    $community_point_log->unique_id = $unique_id;
                    $community_point_log->community_id = $community_id;
                    $community_point_log->user_id = $user_id;
                    $community_point_log->point = $point;
                    $community_point_log->type = HllPointCommunityLog::GIVE_POINT_TYPE;
                    $community_point_log->left_points = HllPointCommunity::getPointsByCommunity($community_id);
                    $community_point_log->change_reason = '绑定房产发放';
                    //保存发放记录
                    $send_log = new HllCommunityPointLog();
                    $send_log->desc = $address_desc;
                    $send_log->community_id = $community_id;
                    $send_log->receive_user = $user_id;
                    $send_log->type_id = 1;
                    if($community_point_log->save() && $send_log->save()){
                        return $point;
                    }else{
                        $flag = false;
                    }
                }else{
                    $flag = false;
                }
            }
            return $flag;
        }
    }

    public static function addPointToUser($data,$user_id,$unique_id,$expire_time,$community_id){

        $community = (new Query())->select(['t2.name','t1.thumbnail','t1.type_desc'])->from('hll_community_point as t1')
            ->leftJoin('hll_community as t2','t2.id = t1.community_id')
            ->where(['t1.id'=>$data['id'],'t1.valid'=>1])->one();

        $point_log = new HllUserPointsLog();
        $point_log->user_id = $user_id;
        $point_log->point = $data['points'];
        $point_log->unique_id = $unique_id;
        $point_log->community_id = $data['community_id'];
        $point_log->business_id = $data['business_id'];
        $point_log->scenes = HllUserPointsLog::$scenes_type[4];
        $point_log->type = HllUserPointsLog::INCOME_POINT_TYPE;
        $point_log->remark = $community['name'].$community['type_desc'];
        $point_log->icon = $community['thumbnail'];
        if($point_log->save()){
            $user_point = HllUserPoints::findOne(['expire_time'=>$expire_time,'valid'=>1,'user_id'=>$user_id,'point_type'=>$data['point_type'],
                'community_id'=>$data['community_id'],'business_id'=>$data['business_id']]);
            if(!$user_point){
                $user_point = new HllUserPoints();
                $user_point->user_id = $user_id;
                $user_point->point_type = $data['point_type'];
                $user_point->point = $data['points'];
                $user_point->expire_time = $expire_time;
                $user_point->community_id = $data['community_id'];
                $user_point->business_id = $data['business_id'];
            }else{
                $user_point->point += $data['points'];
            }
            if($user_point->save()){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }

    }
}
