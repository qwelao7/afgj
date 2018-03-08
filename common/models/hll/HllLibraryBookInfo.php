<?php

namespace common\models\hll;

use common\models\ecs\EcsUsers;
use common\models\hll\HllLibraryBookCategory;
use common\components\ActiveRecord;
use Yii;
use yii\base\Exception;
use yii\db\Query;
/**
 * This is the model class for table "hll_library_book_info".
 *
 * @property string $id
 * @property string $isbn
 * @property string $book_name
 * @property string $thumbnail
 * @property string $pics
 * @property string $book_type
 * @property integer $book_num
 * @property integer $borrow_num
 * @property integer $rate_num
 * @property integer $rate_star
 * @property integer $is_complete
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllLibraryBookInfo extends ActiveRecord
{
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
        return 'hll_library_book_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['isbn'], 'required'],
            [['book_num', 'borrow_num', 'book_type','rate_num', 'rate_star', 'creater', 'updater', 'valid','is_complete'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['isbn', 'thumbnail'], 'string', 'max' => 100],
            [['book_name'], 'string', 'max' => 50],
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
            'isbn' => 'Isbn',
            'book_name' => 'Book Name',
            'thumbnail' => 'Thumbnail',
            'pics' => 'Pics',
            'book_type' => 'Book Type',
            'book_num' => 'Book Num',
            'borrow_num' => 'Borrow Num',
            'rate_num' => 'Rate Num',
            'rate_star' => 'Rate Star',
            'is_complete' => 'Is Complete',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //获取图书详情
    public static function getBookDetail($id){
        $field = ['t1.id','t1.book_info_id','t2.book_name','t2.pics','t1.status','t2.rate_num','t2.borrow_num','t1.user_id',
            "IF(t2.rate_num=0,0,ROUND(t2.rate_star/t2.rate_num,1)) as rate_star"];
        $result = (new Query())->select($field)->from('hll_library_book_status as t1')
            ->leftJoin('hll_library_book_info as t2','t2.id = t1.book_info_id')
            ->where(['t2.id'=>$id,'t2.valid'=>1,'t1.valid'=>1])->one();
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
            $fields = ['t1.id','t2.book_name','t2.thumbnail','t2.borrow_num','t2.rate_num','t2.book_type',"IF(t2.rate_num=0,0,ROUND(t2.rate_star/t2.rate_num,1)) as rate_star"];

            $query = (new Query())->select($fields)
                ->from('hll_library_book_status as t1')
                ->leftJoin('hll_library_book_info as t2','t2.id = t1.book_info_id')
                ->where(['t1.valid'=>1,'t1.user_id'=>$user_id,'t2.valid'=>1,'t1.status'=>1,'t2.is_complete'=>1]);
        }
        else {
            $selectStr = ['t1.id', 't2.book_name','t2.book_type','t2.thumbnail','t2.borrow_num','t2.rate_num',
                't2.rate_star',"IF(t2.rate_num=0,0,ROUND(t2.rate_star/t2.rate_num,1)) as avg_rate_star"];
            $query = (new Query())->select($selectStr)->from('hll_library_book_status as t1')
                ->leftJoin('hll_library_book_info as t2','t2.id = t1.book_info_id')
                ->where(['t1.library_id'=>$library_id,'t1.status'=>1,'t1.valid'=>1,'t2.valid'=>1,'t2.is_complete'=>1]);
            if($book_type != 0){
                $query->andWhere(['t2.book_type'=>$book_type]);
            }
            if($sort == 2){
                if($keyword != '0'){
                    $query->andWhere(['like','t2.book_name',$keyword]);
                }
                $query->orderBy(['t2.borrow_num'=>SORT_DESC,'avg_rate_star'=>SORT_DESC,'t2.book_name'=>SORT_ASC]);
            }
            else{
                if($keyword != '0'){
                    $query->andWhere(['like','t2.book_name',$keyword]);
                }
                $query->orderBy(['avg_rate_star'=>SORT_DESC,'t2.borrow_num'=>SORT_DESC,'t2.book_name'=>SORT_ASC]);
            }
        }
        return $query;
    }

    //搜索指定图书
    public static function getSearchList($book_name, $book_type, $sort, $longitude, $latitude){
        $fields = ['t1.id','t2.book_name','t2.thumbnail','t2.borrow_num','t2.rate_num','t2.book_type',
            "IF(t2.rate_num=0,0,ROUND(t2.rate_star/t2.rate_num,1)) as avg_rate_star",'t3.library_name','t3.longitude','t3.latitude',
            "GeoDistDiff('km',t3.latitude,t3.longitude,$latitude,$longitude) as distance"];
        
        $query = (new Query())->select($fields)->from('hll_library_book_status as t1')
            ->leftJoin('hll_library_book_info as t2','t2.id = t1.book_info_id')
            ->leftJoin('hll_library as t3','t3.id = t1.library_id')
            ->where(['t1.valid'=>1,'t1.status'=>1,'t3.valid'=>1,'t2.valid'=>1,'t2.is_complete'=>1])
            ->andWhere(['like','t2.book_name',$book_name]);

        if($book_type != 0){
            $query->andWhere(['t2.book_type'=>$book_type]);
        }
        if($sort == 2){
            $query->orderBy(['t2.borrow_num'=>SORT_DESC,'avg_rate_star'=>SORT_DESC,'t2.book_name'=>SORT_ASC]);
        }else if($sort == 3){
            $query->orderBy(['distance'=>SORT_ASC,'avg_rate_star'=>SORT_DESC,'t2.borrow_num'=>SORT_DESC,'t2.book_name'=>SORT_ASC]);
        }else{
            $query->orderBy(['avg_rate_star'=>SORT_DESC,'t2.borrow_num'=>SORT_DESC,'t2.book_name'=>SORT_ASC]);
        }
        return $query;
    }

    //存储爬虫所得的图书信息
    public static function setBookInfo($info,$isbn){
        $bookInfo = HllLibraryBookInfo::find()->where(['isbn'=>$isbn,'valid'=>1])->one();

        $tran = Yii::$app->db->beginTransaction();
        try{
            $bookInfo->thumbnail = $info['data']['img']['thumbnails']['large'];
            $bookInfo->pics = json_encode($info['data']['img']['slide']);
            if($bookInfo->pics == '[]'){
                $bookInfo->pics = json_encode(explode(',',$info['data']['img']['thumbnails']['large']));
            }
            $bookInfo->book_name = $info['data']['info']['title'];
            if(array_key_exists('category',$info['data']['info'])){
                $bookInfo->book_type = HllLibraryBookCategory::getCategoryByName($info['data']['info']['category']);
            }else{
                $bookInfo->book_type = 0;
            }
            if($bookInfo->save()){
                $bookCommerce = HllLibraryBookCommerce::find()->where(['book_info_id'=>$bookInfo->id,'valid'=>1])->count();
                if($bookCommerce == 0){
                    HllLibraryBookCommerce::setBookStore($info['data']['buy'],$bookInfo->id);
                }
                $bookInfo->is_complete = 1;
                if($bookInfo->save()){
                    $tran->commit();
                    return $bookInfo->id;
                }
            }
        }catch (\yii\db\Exception $e){
            $tran->rollBack();
            $bookInfo->is_complete = 0;
            if($bookInfo->save()){
                return $bookInfo->id;
            }else{
                return false;
            }
        }
    }
}
