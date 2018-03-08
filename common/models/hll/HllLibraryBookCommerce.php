<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_library_book_commerce".
 *
 * @property string $id
 * @property integer $book_info_id
 * @property integer $commerce_id
 * @property string $book_url
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllLibraryBookCommerce extends ActiveRecord
{

    public static $commerce = [
        '1' => '当当网',
        '2' => '京东',
        '3' => '中国图书网'
    ];

//    public static function getCommerce() {
//        return $this->$commerce;
//    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_library_book_commerce';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['book_info_id', 'commerce_id', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['book_url'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'book_info_id' => 'Book Info ID',
            'commerce_id' => 'Commerce ID',
            'book_url' => 'Book Url',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //获取该书本所在的商店
    public static function getBookStore($id){
        $store = (new Query())->select(['commerce_id','book_url'])
            ->from('hll_library_book_commerce')->where(['book_info_id'=>$id,'valid'=>1])->all();

        foreach($store as &$item) {
            $item['commerce'] = static::$commerce[$item['commerce_id']];
        }

        return $store;
    }

    //保存图书商店信息
    public static function setBookStore($store_info,$book_info_id){
        $store_list = [];
        foreach($store_info as $key=>$val){
            $store_data = [];
            $store_data['book_info_id'] = $book_info_id;
            switch($key){
                case'dangdang':
                    $store_data['commerce_id'] = 1;
                    $store_data['book_url'] = $val['url'];
                    break;
                case'jingdong':
                    $store_data['commerce_id'] = 2;
                    $store_data['book_url'] = $val['url'];
                    break;
                case'bookschina':
                    $store_data['commerce_id'] = 3;
                    $store_data['book_url'] = $val['url'];
                    break;
                default:
                    break;
            }
            array_push($store_list,$store_data);
        }
        $db = Yii::$app->db;
        try{
            $db->createCommand()->batchInsert('{{hll_library_book_commerce}}', ['book_info_id','commerce_id','book_url'],$store_list)->execute();
        }catch (\Exception $e){
            throw $e;
        }
    }
}
