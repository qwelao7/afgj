<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_community_pub_info".
 *
 * @property integer $id
 * @property integer $community_id
 * @property integer $name
 * @property string $phone
 * @property string $status
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllCommunityPubInfo extends ActiveRecord
{
    //公共信息类型
    public static $info_type = [
        1 => '物业电话',
        2 => '会所电话',
        3 => '社区电话',
        4 => '快递电话',
        5 => '社区民警信息',
        6 => '其他'
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_community_pub_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'status', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 50],
            [['phone'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => 'Community ID',
            'name' => 'Name',
            'phone' => 'Phone',
            'status' => 'Status',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 获取指定小区的公共信息
     * @param $id
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getCommunityInfo($id,$type){
        $list = static::find()->select(['id','name','phone',"CONCAT(name,' ',phone) as info"])
            ->where(['community_id'=>$id, 'status'=>1, 'valid'=>1])->asArray()->all();
        if(!$list){
            return [];
        }
        if($type == 1){
            return $list;
        }else{
            $pub_info['id'] = array_column($list,'id');
            $pub_info['name'] = array_column($list,'info');
            return $pub_info;
        }
    }

    /**
     * 获取社区信息报错类型
     * @return array
     */
    public static function getCommunityFeedback(){
        $list = HllKv::find()->select(['kv_key','kv_value'])
            ->where(['kv_type'=>4,'valid'=>1])->asArray()->all();
        if(!$list){
            return [];
        }
        $feedback['key'] = array_column($list,'kv_key');
        $feedback['value'] = array_column($list,'kv_value');
        return $feedback;
    }
}
