<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
use yii\db\Query;

/**
 * This is the model class for table "hll_library".
 *
 * @property string $id
 * @property string $library_name
 * @property integer $share_type
 * @property integer $community_id
 * @property string $qrcode
 * @property double $longitude
 * @property double $latitude
 * @property string $library_address
 * @property string $library_phone
 * @property string $library_book
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllLibrary extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_library';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['library_name'], 'required'],
            [['share_type', 'community_id', 'creater', 'updater', 'valid'], 'integer'],
            [['longitude', 'latitude'], 'number'],
            [['created_at', 'updated_at','library_book','library_info'], 'safe'],
            [['library_name'], 'string', 'max' => 50],
            [['library_address'], 'string', 'max' => 100],
            [['library_phone'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'library_name' => 'Library Name',
            'share_type' => 'Share Type',
            'community_id' => 'Community ID',
            'longitude' => 'Longitude',
            'latitude' => 'Latitude',
            'library_address' => 'Library Address',
            'library_info' => 'Library Info',
            'library_phone' => 'Library Phone',
            'library_book' => 'Library Book',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //获取library列表
    public static function getLibraryList($user_id,$long,$lat){
        $list = (new Query())->select(['library_name','share_type','id','community_id', 'thumbnail','library_book',
            "GeoDistDiff('km',latitude,longitude,$lat,$long) as distance"])
            ->from('hll_library')->where(['valid'=>1,'status'=>1])->orderBy(['distance'=>SORT_ASC,'library_name'=>SORT_ASC])->all();
        if($list){
            foreach($list as &$item){
                $item['is_open'] = (bool)UserAddress::find()->where(['valid'=>1,'owner_auth'=>1,'community_id'=>$item['community_id'],'account_id'=>$user_id])->one();
            }
        }
        return $list;
    }

    public static function getList(){
        $list = (new Query())->select(['library_name','id','community_id'])
            ->from('hll_library')->where(['valid'=>1,'status'=>1])->orderBy(['library_name'=>SORT_ASC])->all();
        return $list;
    }
}
