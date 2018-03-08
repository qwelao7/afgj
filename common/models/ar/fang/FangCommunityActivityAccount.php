<?php

namespace common\models\ar\fang;

use Yii;

/**
 * This is the model class for table "fang_community_activity_account".
 *
 * @property string $id
 * @property string $activity_id
 * @property string $account_id
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class FangCommunityActivityAccount extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'fang_community_activity_account';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['activity_id', 'account_id'], 'required'],
            [['activity_id', 'account_id', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'activity_id' => '活动id',
            'account_id' => '用户id',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    /**
     * 获取指定活动得报名人数
     * @param $activityId 活动ID
     * @return int|string
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public static function getCountByActivity($activityId) {
        return static::find()->where(['activity_id'=>$activityId,'valid'=>1])->count();
    }
}
