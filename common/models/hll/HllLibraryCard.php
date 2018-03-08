<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_library_card".
 *
 * @property string $id
 * @property integer $user_id
 * @property integer $borrow_limit
 * @property integer $status
 * @property string $freeze_time
 * @property integer $borrow_num
 * @property integer $return_num
 * @property integer $donate_num
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllLibraryCard extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_library_card';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'borrow_limit', 'status', 'borrow_num', 'return_num', 'donate_num', 'creater', 'updater', 'valid'], 'integer'],
            [['freeze_time', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'borrow_limit' => 'Borrow Limit',
            'status' => 'Status',
            'freeze_time' => 'Freeze Time',
            'borrow_num' => 'Borrow Num',
            'return_num' => 'Return Num',
            'donate_num' => 'Donate Num',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //借书卡详情
    public static function getCardDetail($user_id){
        $field =['borrow_limit','status','freeze_time','borrow_num','return_num','donate_num'];
        $detail = (new Query())->select($field)->from('hll_library_card')
            ->where(['user_id'=>$user_id,'valid'=>1])->one();
        return $detail;
    }

    public static function getCardChange($donate_code){
        $data = [];
        if($donate_code == 0 || empty($donate_code)){
            $data['flag'] = 1;
            $data['user_id'] = 0;
            return $data;
        }
        else{
            $user_id = HllLibraryBookDonate::find()->select(['user_id'])
                ->where(['donate_code'=>$donate_code,'valid'=>1])->scalar();
            if(!$user_id){
                $data['flag'] = 0;
                $data['user_id'] = '捐书码错误';
            }
            else{
                $user_card = HllLibraryCard::find()->where(['user_id'=>$user_id,'valid'=>1])->one();
                $user_card->borrow_limit +=1;
                if($user_card->save()){
                    $data['flag'] = 1;
                    $data['user_id'] = $user_id;
                }else{
                    $data['flag'] = 0;
                    $data['user_id'] = '修改借书卡错误';
                }
            }
            return $data;
        }
    }
}
