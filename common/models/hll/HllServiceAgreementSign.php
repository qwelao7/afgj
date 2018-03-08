<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $satype
 * @property integer creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllServiceAgreementSign extends ActiveRecord
{
    public static function tableName() {
        return 'hll_service_agreement_sign';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'user_id', 'satype', 'creater','updater'], 'integer'],
            [['user_id'], 'required'],
            [['created_at','updated_at'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '签名编号',
            'user_id' => '用户id',
            'satype' => '服务协议类型',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
}