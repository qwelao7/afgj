<?php

namespace common\models\ar\admin;

use common\models\ar\fang\FangLoupan;
use Yii;

/**
 * This is the model class for table "admin_loupan".
 *
 * @property integer $admin_id
 * @property integer $loupan_id
 * @property integer $areacode
 * @property integer $valid
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 */
class AdminLoupan extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'admin_loupan';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['admin_id', 'loupan_id'], 'required'],
            [['admin_id', 'loupan_id', 'areacode', 'valid', 'creater', 'updater'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            ['areacode', 'default', 'value' => function($model){
                $areacode=0;
                if($model->loupan_id){
                    $areacode = FangLoupan::find()->select('area_id')->where(['id'=>$model->loupan_id])->asArray()->scalar();
                }
                return $areacode;
            }],
            [['admin_id', 'loupan_id','areacode'], 'unique', 'targetAttribute' => ['admin_id', 'loupan_id'], 'message' => '记录已经存在'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'admin_id' => '管理员ID',
            'loupan_id' => '楼盘ID',
            'areacode' => '城市区域代码',
            'valid' => '状态',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
        ];
    }
}
