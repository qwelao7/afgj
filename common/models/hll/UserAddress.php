<?php

namespace common\models\hll;


use common\models\ecs\EcsUsers;
use Yii;
use yii\base\Event;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use common\models\hll\UserAddressTemp;

/**
 *
 * 房产地址信息管理模型v2.1
 *
 */
class UserAddress extends \yii\db\ActiveRecord
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
    public static function tableName()
    {
        return 'hll_user_address';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'account_id', 'community_id', 'owner_auth', 'creater', 'valid'], 'integer'],
            [['is_default', 'consignee', 'mobile'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['address_desc'], 'string', 'max' => 40],
            [['group_name', 'building_num', 'unit_num', 'house_num'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => 'Account ID',
            'community_id' => 'community id',
            'consignee' => 'Consignee',
            'mobile' => 'Mobile',
            'owner_auth' => 'Owner Auth',
            'is_default' => 'Is Default',
            'desc' => 'Building House Num',
            'group_name' => 'group_name',
            'building_num' => 'building_num',
            'unit_num' => 'unit_num',
            'house_num' => 'house_num',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        if (!$insert) {
            if (empty($changedAttributes['owner_auth']) && $this->owner_auth == 1) {

                $this->on(self::EVENT_ADD_MAIN_FORUM, [$this, 'addMainForum']);
                $this->on(self::EVENT_ADD_ALIIM_ACCOUNT, [$this, 'addAliImAccount']);

                $this->trigger(self::EVENT_ADD_MAIN_FORUM);
                $this->trigger(self::EVENT_ADD_ALIIM_ACCOUNT);
            }
        }
    }

    /**
     * 获取用户房产信息
     * @param $userId
     * @author zend.wang
     * @date  2016-10-08 13:00
     */
    public static function getHouseByUser($userId)
    {
        $result = [];
        $house = (new Query())->select(['address_id', 'address_desc'])
            ->from("hll_user_address as t1")
            ->where(['t1.account_id' => $userId, 't1.owner_auth' => 1, 't1.valid' => 1])
            ->orderBy('t1.id DESC')->all();
        if ($house) {
            $result = ArrayHelper::map($house, 'address_id', 'address_desc');
        } else {
            $addressList = (new Query())->select(['address_id', 'province', 'city', 'district', 'sign_building'])
                ->from("ecs_user_address as t1")
                ->where(['t1.user_id' => $userId])
                ->orderBy('t1.address_id DESC')->all();
            foreach ($addressList as $address) {
                $result[$address['address_id']] = join('-', EcsUsers::getUserRegionDetailName($address['province'], $address['city'], $address['district'])) . ' ' . $address['sign_building'];
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
    public static function getCommunitysStatByUser($userId)
    {
        $buildings = static::getUserCommunitys($userId, ['t2.id', 't2.name', 't2.thumbnail']);
        if ($buildings) {
            foreach ($buildings as &$building) {
                $building['num'] = static::numByLoupan($building['id'], $userId);
            }
        }
        return $buildings;
    }

    /**
     * 查询某个小区的业主数量,不包括某个用户(通常是当前登录用户)
     * @param $communityId
     * @param string $exceptAccountId
     * @return int|string
     * @author zend.wang
     * @date  2016-10-08 13:00
     */
    public static function numByLoupan($communityId, $exceptAccountId = "")
    {
        $query = (new Query())->select('t1.account_id')
            ->from("hll_user_address as t1")
            ->where(['t1.community_id' => $communityId, 't1.owner_auth' => 1, 't1.valid' => 1])
            ->andFilterWhere(['!=', 't1.account_id', $exceptAccountId])->distinct(true);
        return $query->count();
    }

    /**
     * 查询用户小区列表
     * @param $userId
     * @param string $fileds
     * @return array
     * @author zend.wang
     * @date  2016-10-08 13:00
     */
    public static function getUserCommunitys($userId, $fileds = '*')
    {
        $result = (new Query())->select($fileds)->distinct(true)
            ->from("hll_user_address as t1")
            ->leftJoin("hll_community as t2", "t2.id = t1.community_id")
            ->where(['t1.account_id' => $userId, 't1.owner_auth' => 1, 't1.valid' => 1])
            ->orderBy('t1.is_default')->all();
        return $result;
    }

    /**
     * 查询两个用户共同的小区列表
     * @param $userId0
     * @param $userId1
     * @return array
     * @author zend.wang
     * @date  2016-10-08 13:00
     */
    public static function getIntersectCommunitys($userId0, $userId1)
    {
        $result = [];
        $user0Buildings = static::getUserCommunitys($userId0, ['t1.community_id', 't1.address_desc']);
                $user1Buildings = static::getUserCommunitys($userId1, ['t1.community_id', 't1.address_desc']);

        if($user0Buildings && $user1Buildings) {
            $user0BuildingsKeys = ArrayHelper::getColumn($user0Buildings,'community_id','address_desc');
            $user1Buildings = ArrayHelper::map($user1Buildings,'community_id','address_desc');


                foreach($user1Buildings as $key1=>$val1) {
                    if(in_array($key1,$user0BuildingsKeys)) {
                        $result = (new Query())->select(['t1.address_desc'])->distinct(true)
                            ->from("hll_user_address as t1")
                            ->where(['t1.account_id'=>$userId1,'t1.owner_auth'=>1,'t1.valid'=>1,'t1.community_id'=>$key1])
                            ->orderBy('t1.is_default')->all();
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
            ->where(['address_id' => $data['address_id'], 'user_id' => $data['account_id']])
            ->count();
        if (!$addressIsExist) {
            return false;
        }

        $model = static::findOne(['address_id' => $data['address_id'], 'account_id' => $data['account_id'], 'valid' => 1]);
        if ($model) {
            $model->address_id = $data['address_id'];
            $model->community_id = $data['community_id'];
            $model->group_name = $data['group_name'];
            $model->building_num = $data['building_num'];
            $model->unit_num = $data['unit_num'];
            $model->house_num = $data['house_num'];
            $model->owner_auth = 1;
            $model->is_default = 'no';
            $model->address_desc = static::generateOwnerHouseNameInfo($data);
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
    public static function generateOwnerHouseNameInfo($data)
    {
        $houseNameInfo = (new Query())->select('t1.cname')
            ->from("fang_house as t1")
            ->where(['t1.id' => $data['community_id']])
            ->one();
        if (!$houseNameInfo) return false;

        $houseDesc = $houseNameInfo['cname'] . " ";

        if ($data['group_name']) {
            $houseDesc .= $data['group_name'] . " ";
        }
        if ($data['building_num']) {
            $houseDesc .= $data['building_num'] . '-';
        }
        if ($data['unit_num']) {
            $houseDesc .= $data['unit_num'] . '-';
        }
        if ($houseNameInfo['house_num']) {
            $houseDesc .= $data['house_num'];
        }

        return $houseDesc;
    }

    /**
     * 根据userId和loupanId查找房产
     */
    public static function getHouseByUserId($userId, $loupanId)
    {
        $userAddress = (new Query())->select(['t2.nickname', 't2.ect_uid', 't2.headimgurl', 't1.address_desc'])
            ->from("hll_user_address as t1")
            ->leftJoin("ecs_wechat_user as t2", 't2.ect_uid = t1.account_id')
            ->where(['t1.account_id' => $userId, 't1.community_id' => $loupanId, 't1.owner_auth' => 1, 't1.valid' => 1])
            ->all();
        return $userAddress;
    }

    /**
     * 根据userId查找房产
     */
    public static function getHouse($userId)
    {
        $userAddress = (new Query())->select(['t1.id', 't1.address_desc'])
            ->from("hll_user_address as t1")
            ->where(['t1.account_id' => $userId, 't1.valid' => 1])
            ->all();
        return $userAddress;
    }

    /** 当前用户是否拥有房产， 取第一套 **/
    public static function hasHouse($userId = null) {
        if (!$userId) {
            $userId = Yii::$app->user->id;
        }

        $userAddress = (new Query())->select(['t1.community_id'])
            ->from("hll_user_address as t1")
            ->where(['t1.account_id' => $userId, 't1.valid' => 1, 't1.owner_auth' => 1])
            ->orderBy(['t1.created_at' => SORT_DESC])
            ->one();

        return $userAddress;
    }

    /**
     * 根据loupanId查找所有住户，不包括本人
     */

    public static function getUserByLoupanId($userId, $loupanId, $keywords)
    {
        $query = (new Query())->select(['t2.ect_uid', 't2.nickname', 't2.headimgurl', 't1.group_name', 't1.building_num', 't1.unit_num', 't1.house_num'])
            ->from("hll_user_address as t1")->distinct()
            ->leftJoin("ecs_wechat_user as t2", "t1.account_id = t2.ect_uid")
            ->where(['t1.valid' => 1, 't1.community_id' => $loupanId, 't1.owner_auth' => 1])
            ->andWhere('t2.ect_uid != ' . $userId)
            ->orderBy('t1.building_num,t1.unit_num,t1.house_num');
        //f_d($query->createCommand()->rawSql);
        //根据用户提交的信息查找住户
        $keywords = trim($keywords);
        if ($keywords) {
            if (preg_match("/^\d*$/", $keywords)) {
                $query->andWhere('t1.house_num= ' . $keywords);
            } else {
                $query->leftJoin("account_skill as t3", "t3.account_id = t2.ect_uid")
                    ->andWhere(['or', ['like', 't2.nickname', $keywords], ['like', 't3.skill', $keywords]]);

            }
        }

        return $query;
    }

    /**
     * 设置默认地址
     * @param type $id 要设置的地址id
     */
    public static function setDefault($id, $uid)
    {
        static::updateAll(['is_default' => 'no'], ['account_id' => $uid]);
        UserAddressTemp::updateAll(['is_default' => 'no'], ['account_id' => $uid]);
        $model = static::find()->where(['id' => $id, 'account_id' => $uid, 'valid'=>1])->one();
        if ($model) {
            $model->is_default = 'yes';

            if ($model->save()) {
                return true;

            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * 添加主论坛
     * @param $event
     * @author zend.wang
     * @date  2016-09-02 13:00
     */
    public function addMainForum($event)
    {

        $addressObj = $event->sender;

        $bbs = Bbs::findOne(['loupan_id' => $addressObj->community_id, 'valid' => 1, 'is_main' => 1]);
        if (!$bbs) {
            $bbs = Bbs::createMainBbsOfLoupan($addressObj->community_id);
            if (!$bbs) {
                Yii::error(" EVENT_ADD_MAIN_FORUM {$addressObj->account_id}");
                Yii::error("create loupan id {$addressObj->community_id} main forum failed");
            }
        }
        $result = Bbs::joinBbs($bbs, $addressObj->account_id);

        if (!$result['state']) {
            Yii::error(" EVENT_ADD_MAIN_FORUM {$addressObj->account_id} join bbs error faied:{$result['msg']}");
        }

    }

    public function addAliImAccount($event)
    {
        $addressObj = $event->sender;
        $result = Yii::$app->im->addUser($addressObj->account_id);
        if (is_string($result)) {
            Yii::error(" EVENT_ADD_ALIIM_ACCOUNT {$addressObj->account_id} add aliim account error faied:{$result}");
        }
    }

    /**
     * 生成地址详情信息
     * @param $data
     * @return string 地址详情
     */
    public static function generateAddressDesc($data)
    {
        $desc = $data['community_name'] . " ";

        if ($data['group_name']) {
            $desc .= $data['group_name'] . '-';
        }
        if ($data['building_num']) {
            $desc .= $data['building_num'];
        }
        if ($data['unit_num']) {
            $desc .= '-' . $data['unit_num'];
        }
        if ($data['house_num']) {
            $desc .= '-' . $data['house_num'];
        }
        return $desc;
    }
}
