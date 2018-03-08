<?php

namespace common\models\ar\community;

use Yii;

/**
 * This is the model class for table "community_help".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $volunteer_id
 * @property integer $loupan_id
 * @property string $title
 * @property string $content
 * @property string $pics
 * @property integer $reply_num
 * @property integer $status
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class CommunityHelp extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'community_help';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'volunteer_id', 'title'], 'required'],
            [['account_id', 'volunteer_id', 'loupan_id', 'reply_num', 'status', 'creater', 'updater', 'valid'], 'integer'],
            [['pics'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 50],
            [['content'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'account_id' => '用户编号',
            'volunteer_id' => '业工用户编号',
            'loupan_id' => '楼盘编号',
            'title' => '消息标题',
            'content' => '文本内容',
            'pics' => '图片',
            'reply_num' => '回复数',
            'status' => '状态：0未解决，1已解决',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    //与当前时间相距几天
    public static function formatTime($the_time) {
        $now_time = date("Y-m-d H:i:s",time()+8*60*60);
        $now_time = strtotime($now_time);
        $show_time = strtotime($the_time);
        $dur = $now_time - $show_time;
        if($dur < 0) {
            return $the_time;
        } else {
            if($dur < 86400) {
                return date('H:i', strtotime($the_time));
            }else {
                if($dur < 604800) {
                    return floor($dur/86400).'天前';
                }else {
                    if($dur < 2592000) {
                        return floor($dur/604800).'周前';
                    }else {
                        $the_time = date('Y-m', $show_time);
                        return $the_time;
                    }
                }
            }
        }
    }

    /**
     * 某个楼盘下的总帮助数，未解决的数量和已解决的数量
     * @param integer $loupanID
     */
    public static function numByloupan($loupanID){
        return static::find()->select('COUNT(id) num,SUM(`status` = 1) solvedNum,SUM(`status` != 1) unSolvedNum')
                    ->where('loupan_id='.(int)$loupanID.' AND valid=1')
                    ->asArray()
                    ->one();
    }
}
