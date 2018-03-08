<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_library_book_borrow".
 *
 * @property string $id
 * @property integer $book_id
 * @property integer $user_id
 * @property string $borrow_time
 * @property integer $status
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllLibraryBookBorrow extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_library_book_borrow';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['book_id', 'user_id', 'status', 'creater', 'updater', 'valid'], 'integer'],
            [['borrow_time'], 'required'],
            [['borrow_time', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'book_id' => 'Book ID',
            'user_id' => 'User ID',
            'borrow_time' => 'Borrow Time',
            'status' => 'Status',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //根据user_id获取借阅图书
    public static function getBorrowListByUser($user_id, $type){

        $data = (new Query())->select(['t1.book_id','t2.id','t3.book_name','t3.thumbnail','t3.rate_num',
            't3.borrow_num',"IF(t3.rate_num=0,0,ROUND(t3.rate_star/t3.rate_num,1)) as rate_star"])
            ->from('hll_library_book_borrow as t1')
            ->leftJoin('hll_library_book_status as t2','t2.id = t1.book_id')
            ->leftJoin('hll_library_book_info as t3','t3.id = t2.book_info_id')
            ->where(['t1.user_id'=>$user_id,'t1.valid'=>1,'t1.status'=>$type,'t3.valid'=>1,'t2.valid'=>1])
            ->orderBy(['t1.created_at'=>SORT_DESC])->all();
        return $data;
    }

    //判断是否能借书
//    public static function borrowBook($qrcode, $user_id){
//        $data = [];
//        $book = (new Query())->select(['t2.library_id','t2.status','t1.item_id'])->from('qr_code as t1')
//            ->leftJoin('hll_library_book as t2','t2.id = t1.item_id')
//            ->where(['t1.qr_code'=>$qrcode,'t1.valid'=>1,'t1.item_type'=>3,'t2.valid'=>1])
//            ->one();
//        if(empty($book)){
//            $data['code'] = '1';
//            $data['content'] = '此书尚未绑定！';
//            return $data;
//        }
//        if($book['status'] != 1){
//            $string = '';
//            switch ($book['status']){
//                case 2:
//                    $string = '此书已借出！';
//                    break;
//                case 3:
//                    $string = '此书正在申请报损！';
//                    break;
//                case 4:
//                    $string = '此书已损坏！';
//                    break;
//                case 5:
//                    $string = '此书已回收！';
//                    break;
//            }
//            $data['code'] = '101';
//            $data['content'] = $string;
//        }else{
//            $auth = (new Query())->from('hll_library as t1')
//                ->leftJoin('hll_user_address as t2','t2.community_id = t1.community_id')
//                ->where(['t2.valid'=>1,'t1.valid'=>1,'t2.owner_auth'=>1,'t2.account_id'=>$user_id])->count();
//            if($auth){
//                $transaction = Yii::$app->db->beginTransaction();
//                try{
//                    $result = new HllLibraryBookBorrow();
//                    $result->user_id = $user_id;
//                    $result->book_id = $book['item_id'];
//                    $result->borrow_time = date('Y-m-d H:i:s', time());
//                    $result->status = 1;
//                    if($result->save()){
//                        $book_card = HllLibraryCard::findOne(['user_id'=>$user_id]);
//                        $book_card->borrow_num += 1;
//                        $book_detail = HllLibraryBook::findOne($book['item_id']);
//                        $book_detail->status = 2;
//                        $book_detail->borrow_num += 1;
//                        if($book_detail->save() && $book_card->save()){
//                            $transaction->commit();
//                            $data['code'] = '0';
//                            $data['content'] = $result->id;
//                        }else{
//                            $data['code'] = '103';
//                            $data['content'] = '保存数据失败！';
//                        }
//                    }else{
//                        $data['code'] = '103';
//                        $data['content'] = '创建借阅记录失败！';
//                    }
//                }catch (\yii\db\Exception $e){
//                    $transaction->rollback();
//                    $data['code'] = '104';
//                    $data['content'] = '借书失败！';
//                }
//            }else{
//                $data['code'] = '102';
//                $data['content'] = '您无此小区的房产！';
//            }
//        }
//        return $data;
//    }

    //用户还书
    public static function returnBook($book_id,$library_id, $user_id){
        $result = HllLibraryBookBorrow::find()->where(['book_id'=>$book_id,'user_id'=>$user_id,'valid'=>1,'status'=>1])->one();
        if(empty($result)){
            $data['code'] = '101';
            $data['content'] = '您没有借入该书本！';
            return $data;
        }
        $transaction = Yii::$app->db->beginTransaction();
        try{
            $result->status = 2;
            $book_card = HllLibraryCard::findOne(['user_id'=>$user_id]);
            $book_card->borrow_num -= 1;
            $book_card->return_num += 1;
            $book_detail = HllLibraryBookStatus::findOne($book_id);
            $book_detail->status = 1;
            $book_detail->library_id = $library_id;
            $library = HllLibrary::findOne($library_id);
            $library->library_book +=1;
            if($result->save() && $book_card->save() && $book_detail->save() && $library->save()){
                $transaction->commit();
                $data['code'] = 0;
                $data['content'] = '还书成功！';
            }else{
                $transaction->rollBack();
                $data['code'] = 101;
                $data['content'] = '还书失败！';
            }
        }catch (\yii\db\Exception $e){
            $transaction->rollBack();
            $data['code'] = 101;
            $data['content'] = '还书失败！';
        }
        return $data;
    }

}
