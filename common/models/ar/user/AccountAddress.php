<?php

namespace common\models\ar\user;

use Yii;
use common\models\ar\user\Account;
use common\models\ar\user\AccountSkill;
use common\models\ar\user\AccountFriend;
use common\models\ar\fang\FangHouse;
use common\models\ar\system\Area;
use common\models\ar\community\CommunityVolunteer;
use common\components\Util;
use yii\helpers\Json;
use yii\db\Query;

/**
 * This is the model class for table "account_address".
 *
 * @property string $id
 * @property integer $account_id
 * @property string $title
 * @property string $is_default
 * @property string $zip_code
 * @property string $areacode
 * @property string $street
 * @property string $mansion
 * @property string $building_house_num
 * @property string $contact_to
 * @property string $mobile
 * @property string $longitude
 * @property string $latitude
 * @property string $created_at
 * @property string $loupan_id
 * @property string $house_id
 * @property string $updated_at
 * @property integer $valid
 */
class AccountAddress extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'account_address';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mobile', 'contact_to', 'areacode','mansion', 'building_house_num'], 'required'],
            [['account_id', 'areacode', 'valid'], 'integer'],
            [['account_id', 'valid'], 'integer'],
            [['is_default'], 'string'],
            ['is_default', 'default', 'value' => function($model){
                if ( static::findOne(['account_id'=>$model->account_id]) ) {
                    return 'no';
                } else {
                    return 'yes';
                }
            }],
            [['created_at', 'updated_at'], 'safe'],
            [['title', 'contact_to'], 'string', 'max' => 100],
            [['zip_code'], 'string', 'max' => 10],
            [['longitude', 'latitude'], 'string', 'max' => 64],
            [['street'], 'string', 'max' => 30],
            [['mansion', 'building_house_num', 'mobile'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account_id' => '用户',
            'title' => '房产名称',
            'is_default' => '是否默认',
            'zip_code' => '邮编',
            'areacode' => '地区',
            'street' => '房产街巷地址',
            'mansion' => '小区或大厦名称',
            'building_house_num' => '楼号门牌号',
            'contact_to' => '联系人',
            'mobile' => '联系人手机',
            'valid' => '状态',
            'longitude' => '经度',
            'latitude' => '维度',
        ];
    }

    /**
     * 设置默认地址
     * @param type $id  要设置的地址id
     */
    public static function setDefault($id, $uid) {
        static::updateAll(['is_default'=>'no'], ['account_id'=>$uid]);
        $model = static::find()->where(['id'=>$id, 'account_id'=>$uid])->one();
        $model->is_default = 'yes';
        return $model->save();
    }


    /**
     * 关联所有地址
     * @return type
     */
    public function getAccount() {
        return $this->hasOne(Account::className(), ['id' => 'account_id']);
    }

    /**
     * 关联房子
     * @return ActiveQuery
     */
    public function getHouse(){
        return $this->hasOne(\common\models\ar\fang\FangHouse::className(), ['id'=>'house_id']);
    }

    /**
     * 查询该手机号是否是客户的手机号，如果是，讲客户的房产添加到account_address表
     * @param $phone 手机号
     * @param $accountID 理论上通过手机号就能查询出account记录，为了防止手机号不唯一(有可能用户换手机号了，新手机号原来的主人也是我们的用户)需要
     */
    public static function addCustomerHouse($phone, $accountID){
        $account = Account::findOne($accountID);
        if(is_null($account))return;
        $customer = Customer::find()->joinWith('customerHouse')->where(['customer.mobilephone'=>$phone, 'customer.valid'=>1])->one();
        if(is_null($customer))return;
        if(empty($account->full_name)){
            $account->full_name = $customer->real_name;
            $ret = $account->save();
            if($accountID == Yii::$app->user->id)Yii::$app->user->identity->full_name = $account->full_name;//如果刚好是当前用户，更新Yii::$app->user->identity
        }
        if(empty($customer['customerHouse']))return;

        /* 查询用户是否已经有默认房产 */
        define('HAS_NO_DEFAULT', is_null(static::find()->select('id')->where(['account_id'=>$accountID, 'is_default'=>'yes', 'valid'=>1])->asArray()->one()));
        $index = 1;
        foreach($customer['customerHouse'] as $cHouse){
            if(1 != $cHouse->valid || 1 != $cHouse->fangHouse->valid)continue;
            /* 查询这套房产是否已经添加过 */
            $accountAddress = static::find()->where(['account_id'=>$accountID, 'house_id'=>$cHouse->fangHouse->id])->one();
            if(!is_null($accountAddress)){
                if(1 != $accountAddress->valid || 1 != $accountAddress->owner_auth){
                    $accountAddress->valid = 1;
                    $accountAddress->owner_auth = 1;
                    $accountAddress->save();
                }
                continue;
            }

            $accountAddress = new static;
            $accountAddress->account_id = $accountID;
            if(1 == $index++ && HAS_NO_DEFAULT)$accountAddress->is_default = 'yes';
            $accountAddress->areacode = $cHouse->fangHouse->loupan->area_id;
            $accountAddress->street = $cHouse->fangHouse->loupan->address;
            $accountAddress->mansion = $cHouse->fangHouse->loupan->name;
            $accountAddress->building_house_num = $cHouse->fangHouse->building_num.FangHouse::$units['building_num']
                .$cHouse->fangHouse->unit_num.FangHouse::$units['unit_num']
                .$cHouse->fangHouse->house_num.FangHouse::$units['house_num'];
            $accountAddress->contact_to = $account->full_name;
            $accountAddress->mobile = $phone;
            $accountAddress->owner_auth = 1;
            $accountAddress->loupan_id = $cHouse->fangHouse->loupan->id;
            $accountAddress->house_id = $cHouse->fangHouse->id;
            $accountAddress->save();
            if(f_params('light_activity_loupan') && in_array($cHouse->fangHouse->loupan->id,f_params('light_activity_loupan') )){
                Yii::$app->db->createCommand("update fang_house_lighting set is_light = 1,update_time = :update_time,user_id= :user_id where house_id= :house_id")
                    ->bindValues([':update_time' => time(),':user_id'=>$accountID,':house_id'=>$accountAddress->house_id])
                    ->execute();
            }
        }
        return;
    }

    /**
     * 查询某个小区的业主数量,不包括某个用户(通常是当前登录用户)
     */
    public static function numByLoupan($loupanID, $exceptAccountID=""){
        $query = (new Query())->select('t1.account_id')->from("account_address as t1")
            ->leftJoin("account as t2","t2.id = t1.account_id")
            ->where(['t1.owner_auth'=>1,'t1.valid'=>1,'t1.loupan_id'=>$loupanID,'t2.status'=>1])
            ->andFilterWhere(['!=','t1.account_id',$exceptAccountID])->distinct(true);
        return $query->count();
    }

    /**
     * 很高兴认识你页面，分组邻居
     * 如果有组团，楼栋号，没单元号，户号，则组团是一级，楼栋是二级，如果有户号，则组团，楼栋加起来是一级，单元号，户号是二级
     */
    public static function group($neighbors){
        $noHouseNum=[];
        $hasHouseNum=[];
        foreach($neighbors as &$neighbor){
            if(empty($neighbor['house_num'])){
                $neighbor = FangHouse::joinUnit($neighbor, TRUE);
                $neighbor['firstLevel'] = $neighbor['group_name'];
                $neighbor['secondLevel'] = $neighbor['building_num'].$neighbor['unit_num'];
                $noHouseNum[$neighbor['firstLevel']][] = $neighbor;
            }else{
                $neighbor = FangHouse::joinUnit($neighbor, TRUE);
                $neighbor['firstLevel'] = $neighbor['group_name'].$neighbor['building_num'];
                $neighbor['secondLevel'] = $neighbor['unit_num'].$neighbor['house_num'];
                $hasHouseNum[$neighbor['firstLevel']][] = $neighbor;
            }
        }
        return (array)$noHouseNum + (array)$hasHouseNum;
    }

    /**
    * 判断用户当前楼盘房产是否有一套经过认证
    */
    public static function ownerAuthNum($loupanId) {
        return AccountAddress::find()->where(['loupan_id'=>$loupanId,'account_id'=>Yii::$app->user->id])
                                    ->andWhere(['owner_auth'=>1])->count();
    }

    /**
    *   根据account_address.id
    *   管理员匹配房产id
    *   判断某个房产是否认证
    */
    public static function isAuth($accountAddressID) {
        $data = AccountAddress::find()->where(['id'=>$accountAddressID, 'valid'=>1])->select(['house_id'])->one();
        if($data == 0){
            $model = false;
        }else {
            $model = AccountAddress::find()->where(['house_id'=>$data->house_id, 'owner_auth'=>1, 'valid'=>1])->count();
            $model = (bool)$model;
        }
        return $model;
    }

    /**
    * 隐私化时,拼接业主姓名为楼栋号+单元号+户号
    */
    public static function concatNickname($accountID, $loupanID) {
        $data = static::addressInfo($accountID, $loupanID, true);
        if($data) {
            $data = implode('', $data);
        }else {
            $data = '';
        }
        return $data;
    }

    /**
    * 搜索用户(昵称， 房号)
    */
    public static function searchByInfo($loupanID, $houseNumInfo, $nickNameInfo) {
        if($houseNumInfo) $houseNumInfo = (int)$houseNumInfo;
        $list = static::find()
            ->join('RIGHT JOIN', 'fang_house', 'account_address.house_id=fang_house.id')
            ->join('INNER JOIN', 'account', 'account_address.account_id=account.id')
            ->join('LEFT JOIN', 'community_volunteer', 'account_address.loupan_id=community_volunteer.loupan_id AND account_address.account_id=community_volunteer.account_id')
            ->select('account_address.id,account_address.account_id,account_address.house_id,account.avatar, account_address.loupan_id')
            ->addSelect('account.nickname,account.sex,account.full_name')
            ->addSelect('fang_house.building_num,fang_house.unit_num,fang_house.house_num,fang_house.group_name')
            ->addSelect('community_volunteer.account_id AS volunteerID,community_volunteer.declaration')
            ->where('account_address.loupan_id='.$loupanID.' AND account_address.valid=1 AND account_address.owner_auth=1 AND account_address.account_id !='.Yii::$app->user->id)
            ->andFilterWhere(['account.nickname'=>$nickNameInfo, 'fang_house.house_num'=>$houseNumInfo])
            ->orderBy('length(fang_house.building_num),fang_house.building_num,length(fang_house.unit_num),fang_house.unit_num, length(fang_house.house_num),fang_house.house_num')
            ->asArray()->all();
        return $list;
    }

    /**
     * 搜索用户(技能)
     */
    public static function searchBySkill($loupanID, $searchInfo) {
        $arr = [];
        $data = AccountSkill::find()->where(['skill'=>$searchInfo])->select('account_id')->asArray()->all();
        foreach($data as &$item) {
            array_push($arr, $item['account_id']);
        }
        $list = AccountAddress::find()
            ->join('RIGHT JOIN', 'fang_house', 'account_address.house_id=fang_house.id')
            ->join('INNER JOIN', 'account', 'account_address.account_id=account.id')
            ->join('LEFT JOIN', 'community_volunteer', 'account_address.loupan_id=community_volunteer.loupan_id AND account_address.account_id=community_volunteer.account_id')
            ->select('account_address.id,account_address.account_id,account_address.house_id,account.avatar, account_address.loupan_id')
            ->addSelect('account.nickname,account.sex,account.full_name')
            ->addSelect('fang_house.building_num,fang_house.unit_num,fang_house.house_num,fang_house.group_name')
            ->addSelect('community_volunteer.account_id AS volunteerID,community_volunteer.declaration')
            ->where('account_address.loupan_id='.$loupanID.' AND account_address.valid=1 AND account_address.owner_auth=1 AND account_address.account_id !='.Yii::$app->user->id)
            ->andWhere([ 'account.id'=>$arr])
            ->orderBy('length(fang_house.building_num),fang_house.building_num,length(fang_house.unit_num),fang_house.unit_num, length(fang_house.house_num),fang_house.house_num')
            ->asArray()->all();
        return $list;
    }

    /**
    * 用户的楼房号信息
    * isSingle boolen
    */
    public static function addressInfo($accountID, $loupanID, $isSingle) {
        if($isSingle) {
            $data = (new Query())->select('t1.building_num, t1.unit_num, t1.house_num')
                ->from('fang_house as t1')->leftJoin('account_address as t2','t1.id=t2.house_id')
                ->where(['t2.valid'=>1,'t2.owner_auth'=>1])->one();
        }else {
            $data = FangHouse::find()->join('LEFT JOIN', 'account_address', 'fang_house.id = account_address.house_id')
                ->where(['account_address.loupan_id'=>$loupanID, 'account_address.account_id'=>$accountID])
                ->andWhere('account_address.valid=1 AND account_address.owner_auth=1')
                ->select(['fang_house.building_num', 'fang_house.unit_num', 'fang_house.house_num'])
                ->asArray()->all();
        }
        return $data;
    }

    /**
    *   某处房产的具体信息 by account_address.id
    */
    public static function detailByFang($accountAddressID) {
        $data = AccountAddress::find()->where(['id'=>$accountAddressID, 'valid'=>1])->asArray()->one();
        if($data) {
            $data['area'] = Area::parentsStr($data['areacode']);
        }
        return $data;
    }

    /**
     * 为用户创建虚拟房产
     * @param $houseId 房产id
     * @param $user 用户信息
     */
    public static function addAccountAddress($houseId, $user, $buildingNum, $unitNum, $houseNum){
        $fictitious_id = 14;
        $address = new static;
        $address->contact_to = $user->nickname;
        $address->mobile = '12345678901';
        $address->areacode = '320113';
        $address->mansion = '回来啦体验社区';
        $address->building_house_num = $buildingNum.'栋'.$unitNum.'单元'.$houseNum.'室';
        $address->account_id = $user->id;
        $address->owner_auth = 1;
        $address->loupan_id = $fictitious_id;
        $address->house_id = $houseId;
        $address->is_default = 'yes';
        if($address->save()) {
            return $address;
        }else {
            Yii::warning('虚拟地址添加失败:'.print_r($address->getErrors(), TRUE));
        }
    }
    /**
     * 获取用户所有社区统计信息
     * @param $userId
     * @author zend.wang
     * @date  2016-08-08 13:00
     */
    public static function getCommunitysStatByUser($userId) {

        $buildings = (new Query())->select(['t2.id','t2.name','t2.thumbnail'])->distinct(true)
            ->from("account_address as t1")
            ->leftJoin("fang_loupan as t2","t2.id = t1.loupan_id")
            ->where(['t1.owner_auth'=>1,'t1.valid'=>1,'t2.valid'=>1,'t1.account_id'=>$userId])
            ->orderBy('t1.id ASC')->all();
        if($buildings) {
            foreach($buildings as &$building) {
                $building['num'] = static::numByLoupan($building['id'],$userId);
            }
        }
        return $buildings;
    }
    
    
}
