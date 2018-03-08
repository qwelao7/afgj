<?php

namespace common\models\ar\credit;

use Yii;

/**
 * This is the model class for table "credit_complain".
 *
 * @property string $id
 * @property string $account_id
 * @property integer $complain_account_id
 * @property string $complain_content
 * @property string $complain_pic
 * @property integer $status
 * @property string $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 */
class CreditComplain extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'credit_complain';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'complain_account_id', 'status', 'creater', 'updater'], 'integer'],
            [['complain_pic'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['complain_content'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '投诉编号',
            'account_id' => '用户编号',
            'complain_account_id' => '被投诉用户编号',
            'complain_content' => '投诉内容',
            'complain_pic' => '投诉图片',
            'status' => '状态：1新建投诉，2处理中，3处理完毕',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
        ];
    }
}
