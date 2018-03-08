<?php

namespace common\models\hll;

use common\models\ecs\EcsUsers;
use common\components\ActiveRecord;
use Yii;
use yii\db\Query;

/**
 * This is the model class for table "hll_library_book".
 *
 * @property string $id
 * @property integer $library_id
 * @property string $book_name
 * @property string $pics
 * @property string $qrcode
 * @property integer $user_id
 * @property integer $status
 * @property integer $borrow_num
 * @property integer $rate_num
 * @property integer $rate_star
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllLibraryBook extends ActiveRecord
{

    //图书分类
    public static $bookCategory=[
        '1' => ['id'=>'1','name'=>'小说'],
        '2' => ['id'=>'2','name'=>'文艺'],
        '3' => ['id'=>'3','name'=>'童书'],
        '4' => ['id'=>'4','name'=>'生活'],
        '5' => ['id'=>'5','name'=>'人文社科'],
        '6' => ['id'=>'6','name'=>'经营'],
        '7' => ['id'=>'7','name'=>'励志'],
        '8' => ['id'=>'8','name'=>'科技'],
    ];
    //猜你搜索
    public static $searchBook=[
        '1' => '读库7-9',
        '2' => '读库12岁以上',
        '3' => '神奇校车',
        '4' => '太空大揭秘',
        '5' => '小牛顿科学馆',
        '6' => '中国幽默儿童文学创作',
        '7' => '大师名作绘本馆',
    ];
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_library_book';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['library_id', 'book_name'], 'required'],
            [['library_id', 'user_id','category_id', 'status', 'borrow_num', 'rate_num', 'rate_star', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['book_name', 'qrcode'], 'string', 'max' => 50],
            [['pics'], 'string', 'max' => 800],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'library_id' => 'Library ID',
            'category_id' => 'Category ID',
            'book_name' => 'Book Name',
            'pics' => 'Pics',
            'qrcode' => 'Qrcode',
            'user_id' => 'User ID',
            'status' => 'Status',
            'borrow_num' => 'Borrow Num',
            'rate_num' => 'Rate Num',
            'rate_star' => 'Rate Star',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //获取图书详情
    public static function getBookDetail($id){
        $field = ['book_name','pics','user_id','status','rate_num','borrow_num',"IF(rate_num=0,0,ROUND(rate_star/rate_num,1)) as rate_star"];
        $result = (new Query())->select($field)->from('hll_library_book')->where(['id'=>$id,'valid'=>1])->one();
        if($result){
            $result['pics'] = json_decode($result['pics']);
            if($result['user_id'] == 0){
                $result['donate'] = '回来啦社区';
            }else{
                $user = EcsUsers::getUser($result['user_id']);
                $result['donate'] = $user['nickname'];

            }
        }
        return $result;
    }

    //获取图书列表
    public static function getBookList($library_id,$user_id,$sort=null,$keyword=null,$book_type=null){
        if($library_id == null && $user_id != null){
            $fields = ['id','book_name','thumbnail','borrow_num','rate_num','rate_star','$category_id'];

            $query = (new Query())->select($fields)
            ->from('hll_library_book')->where(['valid'=>1,'user_id'=>$user_id]);
        }
        else {
            $selectStr = ['id', 'book_name','category_id','thumbnail','borrow_num','rate_num','rate_star',"IF(rate_num=0,0,ROUND(rate_star/rate_num,1)) as avg_rate_star"];
            $query = (new Query())->select($selectStr)->from('hll_library_book')->where(['library_id'=>$library_id,'status'=>1]);
            if($book_type != 0){
                $query->andWhere(['category_id'=>$book_type]);
            }
            if($sort == 2){
                if($keyword != '0'){
                    $query->andWhere(['like','book_name',$keyword]);
                }
                $query->orderBy(['borrow_num'=>SORT_DESC,'avg_rate_star'=>SORT_DESC,'book_name'=>SORT_ASC]);
            }
            else{
                if($keyword != '0'){
                    $query->andWhere(['like','book_name',$keyword]);
                }
                $query->orderBy(['avg_rate_star'=>SORT_DESC,'borrow_num'=>SORT_DESC,'book_name'=>SORT_ASC]);
            }
        }
        return $query;
    }

    //搜索指定图书
    public static function getSearchList($book_name, $book_type, $sort, $longitude, $latitude){
        $fields = ['t1.id','t1.book_name','t1.thumbnail','t1.borrow_num','t1.rate_num','t1.category_id',
            "IF(t1.rate_num=0,0,ROUND(t1.rate_star/t1.rate_num,1)) as avg_rate_star",'t2.library_name','t2.longitude','t2.latitude',
            "GeoDistDiff('km',t2.latitude,t2.longitude,$latitude,$longitude) as distance"];

        $query = (new Query())->select($fields)->from('hll_library_book as t1')
            ->leftJoin('hll_library as t2','t2.id = t1.library_id')
            ->where(['t1.valid'=>1,'t1.status'=>1,'t2.valid'=>1])
            ->andWhere(['like','book_name',$book_name]);
        if($book_type != 0){
            $query->andWhere(['t1.category_id'=>$book_type]);
        }
        if($sort == 2){
            $query->orderBy(['t1.borrow_num'=>SORT_DESC,'avg_rate_star'=>SORT_DESC,'t1.book_name'=>SORT_ASC]);
        }else if($sort == 3){
            $query->orderBy(['distance'=>SORT_ASC,'avg_rate_star'=>SORT_DESC,'t1.borrow_num'=>SORT_DESC,'t1.book_name'=>SORT_ASC]);
        }else{
            $query->orderBy(['avg_rate_star'=>SORT_DESC,'t1.borrow_num'=>SORT_DESC,'t1.book_name'=>SORT_ASC]);
        }
        return $query;
    }
}
