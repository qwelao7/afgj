<?php

namespace common\models\hll;


use common\models\ecs\EcsUsers;
use Yii;
use yii\base\Event;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "hll_user_address_ext".
 *
 * @property integer $id
 * @property integer $address_id
 * @property integer $loupan_id
 * @property integer $house_id
 * @property integer $owner_auth
 * @property string $is_default
 * @property string $building_house_num
 * @property string $latitude
 * @property string $longitude
 * @property integer $creater
 * @property string $created_at
 * @property string $updated_at
 * @property integer $valid
 */
class UserAddressExt extends \yii\db\ActiveRecord
 {
    /**
     * 添加阿里百川即时通讯账户
     */
    const EVENT_ADD_ALIIM_ACCOUNT = 'addAliImAccount';
    /**
     * 添加主论坛
     */
    const EVENT_ADD_MAIN_FORUM = 'addMainForum';
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'hll_user_address_ext';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id'], 'required'],
            [['id', 'address_id','account_id', 'loupan_id', 'house_id', 'owner_auth', 'creater', 'valid'], 'integer'],
            [['is_default'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['desc'], 'string', 'max' => 40],
            [['latitude', 'longitude'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'address_id' => 'Address ID',
            'account_id' => 'Account ID',
            'loupan_id' => 'Loupan ID',
            'house_id' => 'House ID',
            'owner_auth' => 'Owner Auth',
            'is_default' => 'Is Default',
            'desc' => 'Building House Num',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
    public function afterSave($insert, $changedAttributes) {
        if (!$insert) {
            if (empty($changedAttributes['owner_auth']) && $this->owner_auth == 1) {

                $this->on(self::EVENT_ADD_MAIN_FORUM,[$this,'addMainForum']);
                $this->on(self::EVENT_ADD_ALIIM_ACCOUNT,[$this,'addAliImAccount']);

                $this->trigger(self::EVENT_ADD_MAIN_FORUM);
                $this->trigger(self::EVENT_ADD_ALIIM_ACCOUNT);
            }
        }
    }
    /**
     * 获取用户房产信息
     * @param $userId
     * @author zend.wang
     * @date  2016-08-26 13:00
     */
    public static function getHouseByUser($userId) {
        $result =[];
        $house = (new Query())->select(['address_id','desc'])
            ->from("hll_user_address_ext as t1")
            ->where(['t1.account_id'=>$userId,'t1.owner_auth'=>1,'t1.valid'=>1])
            ->orderBy('t1.id DESC')->all();
        if($house) {
            $result = ArrayHelper::map($house,'address_id','desc') ;
        } else{
            $addressList = (new Query())->select(['address_id','province','city','district','sign_building'])
                ->from("ecs_user_address as t1")
                ->where(['t1.user_id'=>$userId])
                ->orderBy('t1.address_id DESC')->all();
            foreach($addressList as $address) {
                $result[$address['address_id']] = join('-',EcsUsers::getUserRegionDetailName($address['province'],$address['city'],$address['district'])).' '.$address['sign_building'];
            }
        }
        return $result;
    }

    /**
     * 获取用户所有社区统计信息
     * @param $userId
     * @author zend.wang
     * @date  2016-08-26 13:00
     */
    public static function getCommunitysStatByUser($userId) {
        $buildings = static::getUserCommunitys($userId,['t2.id','t2.name','t2.thumbnail']);
        if($buildings) {
            foreach($buildings as &$building) {
                $building['num'] = static::numByLoupan($building['id'],$userId);
            }
        }
        return $buildings;
    }

    /**
     * 查询某个小区的业主数量,不包括某个用户(通常是当前登录用户)
     */
    public static function numByLoupan($loupanId, $exceptAccountId=""){
        $query = (new Query())->select('t1.account_id')
            ->from("hll_user_address_ext as t1")
            ->where(['t1.loupan_id'=>$loupanId,'t1.owner_auth'=>1,'t1.valid'=>1])
            ->andFilterWhere(['!=','t1.account_id',$exceptAccountId])->distinct(true);
        return $query->count();
    }

    public static function getUserCommunitys($userId,$fileds='*') {
        $buildings = (new Query())->select($fileds)->distinct(true)
            ->from("hll_user_address_ext as t1")
            ->leftJoin("fang_loupan as t2","t2.id = t1.loupan_id")
            ->where(['t1.account_id'=>$userId,'t1.owner_auth'=>1,'t1.valid'=>1])
            ->orderBy('t1.id DESC')->all();
        return $buildings;
    }

    public static function getIntersectCommunitys($userId0,$userId1) {
        $result=[];
        $user0Buildings = static::getUserCommunitys($userId0,['t1.loupan_id','t1.desc']);

        $user1Buildings = static::getUserCommunitys($userId1,['t1.loupan_id','t1.desc']);


        if($user0Buildings && $user1Buildings) {
            $user0BuildingsKeys = ArrayHelper::getColumn($user0Buildings,'loupan_id','desc');
            $user1Buildings = ArrayHelper::map($user1Buildings,'loupan_id','desc');


                foreach($user1Buildings as $key1=>$val1) {
                    if(in_array($key1,$user0BuildingsKeys)) {
                        $result[] = $val1;
                    }
            }
        }
        return $result;
    }
    /**
     * 认证用户房产地址信息
     */
    public static function authHouse($data)
    {
        //地址信息是否存在
        $addressIsExist = (new Query())->select('*')
                                ->from("ecs_user_address")
                                ->where(['address_id'=>$data['address_id'],'user_id'=>$data['account_id']])
                                ->count();
        if(!$addressIsExist) {
            return false;
        }

        $model = UserAddressExt::findOne(['address_id'=>$data['address_id'],
                                'account_id'=>$data['account_id'],'valid'=>1]);
        if($model) {
            $houseInfo = static::generateOwnerHouseNameInfo($data['house_id']);
            $model->address_id = $data['address_id'];
            $model->house_id = $data['house_id'];
            $model->loupan_id = $houseInfo['loupan_id'];
            $model->owner_auth = 1;
            $model->is_default = 'NO';
            $model->desc =$houseInfo['desc'];
            return $model->save();
        }
       return false;
    }

    /**
     * 获取房产的详情信息
     * @param $house_id
     * @return bool|string
     * @author zend.wang
     * @date  2016-08-31 13:00
     */
    public static function generateOwnerHouseNameInfo($house_id) {
        $houseNameInfo = (new Query())->select('t1.loupan_id,t2.name,t1.group_name,t1.building_num,t1.unit_num,t1.house_num')
            ->from("fang_house as t1")
            ->leftJoin("fang_loupan as t2",'t2.id = t1.loupan_id')
            ->where(['t1.id'=>$house_id])
            ->one();
        if(!$houseNameInfo) return false;

        $result['loupan_id'] = $houseNameInfo['loupan_id'];

        array_shift($houseNameInfo);

        $houseDesc=$houseNameInfo['name']." ";

        if($houseNameInfo['group_name']){
            $houseNameInfo['group_name']." ";
        }
        if($houseNameInfo['building_num']) {
            $houseDesc = $houseDesc.$houseNameInfo['building_num'].'-';
        }
        if($houseNameInfo['unit_num']) {
            $houseDesc = $houseDesc.$houseNameInfo['unit_num'].'-';
        }
        if($houseNameInfo['house_num']) {
            $houseDesc = $houseDesc.$houseNameInfo['house_num'];
        }

        $result['desc'] = $houseDesc;
        return $result;
    }

    /**
     * 根据userId和loupanId查找房产
     */
    public static function getHouseByUserId($userId,$loupanId)
    {
        $userAddress = (new Query())->select(['t2.nickname','t2.ect_uid','t2.headimgurl','t1.desc'])
            ->from("hll_user_address_ext as t1")
            ->leftJoin("ecs_wechat_user as t2",'t2.ect_uid = t1.account_id')
            ->where(['t1.account_id'=>$userId,'t1.loupan_id'=>$loupanId,'t1.owner_auth'=>1,'t1.valid'=>1])
            ->all();
        return $userAddress;
    }
    /**
     * 根据loupanId查找所有住户，不包括本人
     */

    public static function getUserByLoupanId($userId,$loupanId,$keywords)
    {
        $query = (new Query())->select(['t2.ect_uid', 't2.nickname', 't2.headimgurl', 't1.desc','t4.building_num'])
            ->from("hll_user_address_ext as t1")->distinct()
            ->leftJoin("ecs_wechat_user as t2", "t1.account_id = t2.ect_uid")
            ->leftJoin("fang_house as t4", "t4.id = t1.house_id")
            ->where(['t1.valid' => 1, 't1.loupan_id' => $loupanId, 't1.owner_auth'=>1])
            ->andWhere('t2.ect_uid != ' . $userId . ' and t1.house_id !=' . 0)
            ->orderBy('length(t4.building_num),t4.building_num,length(t4.unit_num),t4.unit_num,length(t4.house_num),t4.house_num,');
        //根据用户提交的信息查找住户
        $keywords = trim($keywords);
        if ($keywords) {
            if (preg_match("/^\d*$/", $keywords)) {
                $query->andWhere('t4.house_num= ' . $keywords);
            } else {
                $query->leftJoin("account_skill as t3", "t3.account_id = t2.ect_uid")
                      ->andWhere(['or', ['like', 't2.nickname', $keywords], ['like', 't3.skill', $keywords]]);

            }
        }

        return $query;
    }

    /**
     * 添加主论坛
     * @param $event
     * @author zend.wang
     * @date  2016-09-02 13:00
     */
    public function addMainForum($event) {

        $addressExt = $event->sender;

        $bbs = Bbs::findOne(['loupan_id'=>$addressExt->loupan_id,'valid'=>1,'is_main'=>1]);
        if(!$bbs) {
            $bbs = Bbs::createMainBbsOfLoupan($addressExt->loupan_id);
            if(!$bbs) {
                Yii::error(" EVENT_ADD_MAIN_FORUM {$addressExt->account_id}");
                Yii::error("create loupan id {$addressExt->loupan_id} main forum failed");
            }
        }
        $result = Bbs::joinBbs($bbs,$addressExt->account_id);

        if(!$result['state']) {
            Yii::error(" EVENT_ADD_MAIN_FORUM {$addressExt->account_id} join bbs error faied:{$result['msg']}");
        }

    }
    public function addAliImAccount($event) {
        $addressExt = $event->sender;
        $result = Yii::$app->im->addUser($addressExt->account_id);
        if(is_string($result)) {
            Yii::error(" EVENT_ADD_ALIIM_ACCOUNT {$addressExt->account_id} add aliim account error faied:{$result}");
        }
    }
}
