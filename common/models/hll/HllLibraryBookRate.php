<?php

namespace common\models\hll;

use common\models\ecs\EcsUsers;
use Yii;
use common\components\ActiveRecord;
use yii\db\Query;

/**
 * This is the model class for table "hll_library_book_rate".
 *
 * @property string $id
 * @property integer $book_info_id
 * @property integer $user_id
 * @property integer $rate_star
 * @property string $rate_comment
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllLibraryBookRate extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_library_book_rate';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['book_info_id', 'user_id', 'rate_star', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['rate_comment'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'book_info_id' => 'book_info ID',
            'user_id' => 'User ID',
            'rate_star' => 'Rate Star',
            'rate_comment' => 'Rate Comment',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    //获取书本的评论
    public static function getBookComment($id){
        $comment = (new Query())->select(['rate_star','rate_comment','user_id','created_at'])->from('hll_library_book_rate')
            ->where(['book_info_id'=>$id,'valid'=>1])->all();
        if($comment){
            foreach($comment as &$item){
                $user = EcsUsers::getUser($item['user_id']);
                $item['nickname'] = $user['nickname'];
                $item['headimgurl'] = $user['headimgurl'];
            }
        }
        return $comment;
    }
}
