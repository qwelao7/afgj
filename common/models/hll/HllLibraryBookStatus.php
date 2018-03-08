<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_library_book_status".
 *
 * @property string $id
 * @property integer $library_id
 * @property string $qrcode
 * @property integer $book_info_id
 * @property integer $user_id
 * @property integer $status
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllLibraryBookStatus extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_library_book_status';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['library_id'], 'required'],
            [['library_id', 'book_info_id', 'user_id', 'status', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['qrcode'], 'string', 'max' => 100],
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
            'qrcode' => 'Qrcode',
            'book_info_id' => 'Book Info ID',
            'user_id' => 'User ID',
            'status' => 'Status',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }
}
