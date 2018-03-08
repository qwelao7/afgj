<?php

namespace common\models\hll;

use common\models\ecs\EcsUsers;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use common\components\ActiveRecord;
/**
 * This is the model class for table "decorate_project".
 *
 * @property integer $id
 * @property string $title
 * @property integer $address_id
 * @property integer $company_id
 * @property double $budget
 * @property string $thumbnailpic
 * @property string $pics
 * @property integer $is_prototyperoom
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class DecorateProject extends ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'decorate_project';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['title', 'address_id'], 'required'],
            [['address_id', 'company_id', 'is_prototyperoom', 'creater', 'updater', 'valid'], 'integer'],
            [['budget'], 'number'],
            [['pics'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 50],
            [['thumbnailpic'], 'string', 'max' => 200],
            ['thumbnailpic', 'default', 'value' => 'message/img-2.0/decorate_default.jpg']
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'address_id' => 'Address ID',
            'company_id' => 'Company ID',
            'budget' => 'Budget',
            'thumbnailpic' => 'Thumbnailpic',
            'pics' => 'Pics',
            'is_prototyperoom' => 'Is Prototyperoom',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    public static function getDecorateCompany($catIds) {
        $brandIds  = (new Query())->select(['t1.brand_id'])->distinct(true)
            ->from("ecs_goods as t1")
            ->where(['t1.cat_id'=>$catIds,'t1.is_on_sale'=>1])
            ->orderBy('t1.goods_id DESC')->all();
        $brandList =[];
        if($brandIds) {
            $brandIds = ArrayHelper::getColumn($brandIds,'brand_id');
             $result =  (new Query())->select(['t1.brand_id','t1.brand_name'])
                ->from("ecs_brand as t1")
                ->where(['t1.brand_id'=>$brandIds,'t1.is_show'=>1])
                ->orderBy('t1.brand_id DESC')->all();
            $brandList = ArrayHelper::map($result,'brand_id','brand_name');
        }
        $brandList[0] ='其他';
        return $brandList;
    }

    public static function getDecorateProjectQueryByUser($userId, $fields='') {
        if (empty($fields)) {
            $fields = ['t1.id','t1.title','t1.thumbnailpic','t1.is_prototyperoom','t1.budget','t1.company_id','t2.address_desc'];
        }

        $query  = (new Query())->select($fields)->distinct(true)
            ->from("decorate_project as t1")
            ->leftJoin("hll_user_address as t2",'t2.id = t1.address_id')
            ->where(['t1.creater'=>$userId,'t1.valid'=>1,'t2.valid'=>1,'t2.account_id'=>$userId])
            ->orderBy('t1.id DESC');
        return $query;
    }

    public static function getDecorateProjectQueryById($id, $fields='') {
        if (empty($fields)) {
            $fields = ['t1.id','t1.title','t1.thumbnailpic','t1.is_prototyperoom','t1.budget','t1.company_id',"(t2.id) as address_id",'t2.address_desc'];
        }

        $query  = (new Query())->select($fields)->distinct(true)
            ->from("decorate_project as t1")
            ->leftJoin("hll_user_address as t2",'t2.id = t1.address_id')
            ->where(['t1.id'=>$id,'t1.valid'=>1,'t2.valid'=>1])
            ->orderBy('t1.id DESC');

        if (is_array($id)) {
            $query = $query->all();
        }else {
            $query = $query->one();
        }

        return $query;
    }

    /**
     * 装修日志查询
     * @param $addressId
     * @return static
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public static function getDecorateLogsQueryByAddressId($bbsId) {
        $query = (new Query())->select(['t1.id','t1.title','t1.content', 't1.attachment_type','t1.account_id','t1.admin_id',
            't1.attachment_content','t1.publish_time'])
            ->from('message as t1')
            ->where(['t1.message_type'=>5,'t1.bbs_id'=>$bbsId,'t1.attachment_type'=>[0,1],'t1.publish_status'=>[1,2],'t1.valid'=>1])
            ->orderBy('t1.publish_time DESC');
        return $query;
    }


}
