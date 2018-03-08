<?php

namespace common\models\ar\message;

use Yii;
use common\models\ar\user\Account;
use common\models\ar\admin\Admin;
use yii\db\Query;

/**
 * This is the model class for table "message_chat".
 *
 * @property string $id
 * @property integer $account_id
 * @property integer $admin_id
 * @property integer $to_account_id
 * @property integer $to_admin_id
 * @property string $message
 * @property string $creater
 * @property string $created_at
 * @property integer $valid
 */
class MessageChat extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'message_chat';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'admin_id', 'to_account_id', 'to_admin_id', 'creater', 'valid'], 'integer'],
            [['created_at'], 'safe'],
            [['message'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'account_id' => '用户编号',
            'admin_id' => '管理员编号',
            'to_account_id' => '接收者用户编号',
            'to_admin_id' => '接收者管理员编号',
            'message' => '文本内容',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'valid' => '0已经删除，1有效',
        ];
    }

    
    /**
     * account_id字段关联account表
     * @param string|array $fields
     */
    public function getAccount($fields='*'){
        return $this->hasOne(Account::className(), ['id'=>'account_id'])->select($fields);
    }
    
    /**
     * admin_id字段关联admin表
     * @param string|array $fields
     */
    public function getAdmin($fields='*'){
        return $this->hasOne(Admin::className(), ['id'=>'admin_id'])->select($fields);
    }
    
    /**
     * 获取某个用户的好友们给他发送的最后一条消息
     * @param integer $acountID
     */
    public static function lastMsgFromFriends($acountID){
        $maxIDs = static::find()->select('MAX(id) maxID')
            ->where('valid=1 AND account_id!=0 AND to_account_id='.(int)$acountID)
            ->groupBy('account_id')
            ->asArray()
            ->column();
        if(!$maxIDs)return[];
         $result= (new Query())
             ->select('t1.id, t1.message, t1.created_at,("msgFromFriend") msgType')
             ->addSelect('t2.id AS fromID,t2.nickname,t2.avatar')
             ->from("message_chat as t1")
             ->leftJoin('account as t2','t1.account_id = t2.id')
             ->where(['t1.id'=>$maxIDs])->all();
        return $result;
    }
    
    /**
     * 楼盘管理员们给某个用户发送的最后一条消息
     * @param integer $acountID
     */
    public static function lastMsgFromAdmins($acountID){
        $maxIDs = static::find()->select('MAX(id) maxID')
            ->where('valid=1 AND admin_id!=0 AND to_account_id='.(int)$acountID)
            ->groupBy('admin_id')
            ->asArray()
            ->column();
        if(!$maxIDs)return[];
        $result= (new Query())
            ->select('t1.id, t1.message, t1.created_at,("msgFromAdmin") msgType')
            ->addSelect('t2.id AS fromID,t2.nickname,t2.avatar')
            ->from("message_chat as t1")
            ->leftJoin('admin as t2','t1.admin_id = t2.id')
            ->where(['t1.id'=>$maxIDs])->all();
        return $result;
    }
    /**
     * 用户给某个发送的楼盘管理员们最后一条消息
     * @param integer $adminID
     */
    public static function lastMsgToAdmins($adminID){
        $maxIDs = static::find()->select('MAX(id) maxID')
            ->where('valid=1 AND account_id!=0 AND to_admin_id='.(int)$adminID)
            ->groupBy('account_id')
            ->asArray()
            ->column();
        if(!$maxIDs)return[];
        return static::find()->select('message_chat.id, message_chat.message, message_chat.created_at,("msgFromFriend") msgType')
            ->addSelect('account.id AS fromID,account.nickname,account.avatar')
            ->joinWith('account', TRUE, 'INNER JOIN')
            ->where(['message_chat.id'=>$maxIDs])
            ->asArray()
            ->all();
    }
}
