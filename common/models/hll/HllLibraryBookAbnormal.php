<?php

namespace common\models\hll;

use Yii;

/**
 * This is the model class for table "hll_library_book_abnormal".
 *
 * @property string $id
 * @property integer $book_id
 * @property integer $user_id
 * @property integer $abnormal_type
 * @property string $abnormal_desc
 * @property integer $admin_id
 * @property integer $result_type
 * @property string $result
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllLibraryBookAbnormal extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_library_book_abnormal';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['book_id', 'abnormal_type','user_id'], 'required'],
            [['book_id', 'user_id', 'abnormal_type', 'admin_id', 'result_type', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['abnormal_desc', 'result'], 'string', 'max' => 500],
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
            'abnormal_type' => 'Abnormal Type',
            'abnormal_desc' => 'Abnormal Desc',
            'admin_id' => 'Admin ID',
            'result_type' => 'Result Type',
            'result' => 'Result',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
