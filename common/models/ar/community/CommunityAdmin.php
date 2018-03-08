<?php

namespace common\models\ar\community;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "community_admin".
 *
 * @property string $id
 * @property integer $loupan_id
 * @property integer $admin_id
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class CommunityAdmin extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'community_admin';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['loupan_id', 'admin_id', 'creater', 'updater', 'valid'], 'integer'],
            [['admin_id'], 'required'],
            [['created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'loupan_id' => '楼盘编号',
            'admin_id' => '管家编号',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }
    /**
     *
     * 管家用户可以管理楼盘
     * @param $accountId 用户ID
     * @param $adminId 管家ID
     * @return array 楼盘列表 例如: [1=>['id'=>1,'name'=>'xxxx'],...]
     * @author zend.wang
     * @date  2016-06-12 13:00
     */
    public static function getLoupansByAdminID($adminId){

        $loupans = (new Query())->select('t2.id,t2.name')
                        ->from('{{community_admin}} AS t1')
                        ->leftJoin('{{fang_loupan}} AS t2', 't2.id = t1.loupan_id')
                        ->where(['t1.admin_id' => $adminId,'t1.valid'=>1])
                        ->all();

        return $loupans;
    }

    /**
     * 是否楼盘管家
     * @param $loupanId 楼盘ID
     * @param $adminId 管家
     * @author zend.wang
     * @date  2016-06-21 13:00
     */

    public static function isAdminOfLoupan($loupanId,$adminId) {
        $count = static::find()->where(['admin_id' => $adminId,'loupan_id'=>$loupanId,'valid'=>1])->count();
        return $count > 0;
    }
}
