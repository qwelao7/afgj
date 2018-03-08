<?php

namespace common\models\hll;

use common\components\Util;
use common\models\ar\message\Message;
use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_bbs_user".
 *
 * @property integer $id
 * @property integer $bbs_id
 * @property integer $account_id
 * @property integer $user_role
 * @property integer $status
 * @property string $banned_time
 * @property integer $ban_admin_id
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllBbsUser extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_bbs_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bbs_id', 'account_id', 'user_role', 'status', 'ban_admin_id', 'creater', 'updater', 'valid'], 'integer'],
            [['banned_time', 'created_at', 'updated_at'], 'safe']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'bbs_id' => 'Bbs ID',
            'account_id' => 'Account ID',
            'user_role' => 'User Role',
            'status' => 'Status',
            'banned_time' => 'Banned Time',
            'ban_admin_id' => 'Ban Admin ID',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 根据bbsId查找成员
     */
    public static function getUserListByBbs($bbsId, $field=null,$status=null, $user_role=null)
    {
        if($field == null){
            $field = ['t1.banned_time', 't1.bbs_id', 't1.account_id', 't1.status', 't1.user_role', 't2.nickname', 't2.ect_uid', 't2.headimgurl'];
        }
        if($status==null){
            $status = [2,3];
        }
        if($user_role==null){
            $user_role=[1,2,3];
        }
        $account = (new Query())->select($field)
            ->from("hll_bbs_user as t1")
            ->leftJoin("ecs_wechat_user as t2", 't2.ect_uid = t1.account_id')
            ->leftJoin('ecs_users as t3','t3.user_id = t1.account_id')
            ->where(['t1.bbs_id' => $bbsId, 't1.valid' => 1,'t1.status'=>$status,'t1.user_role'=>$user_role])
            ->orderBy(['t1.user_role'=>SORT_ASC,'t1.account_id'=>SORT_DESC]);
        return $account;
    }

    public static function getBbsListByUser($userId)
    {
        $bbsList = (new Query())->select(['t1.bbs_id', 't2.loupan_id', 't2.bbs_name', 't2.thumbnail'])
            ->from("hll_bbs_user as t1")
            ->leftJoin("hll_bbs as t2", 't2.id = t1.bbs_id')
            ->where(['t1.account_id' => $userId, 't1.valid' => 1, 't2.valid' => 1])
            ->andWhere(['<', 't1.status', 4])
            ->orderBy("t2.is_main DESC,t2.id ASC")
            ->all();
        if ($bbsList) {
            foreach ($bbsList as &$item) {
                $msg = (new Query())->select(['title', 'publish_time'])->from('message')->where(['bbs_id' => $item['bbs_id'], 'message_type' => 4, 'publish_status' => [1, 2], 'valid' => 1])
                    ->orderBy(['publish_time' => SORT_DESC])->one();
                if ($msg) {
                    $item['msg']['title'] = $msg['title'];
                    $item['msg']['publish_time'] = Util::formatTime($msg['publish_time']);
                }
            }
        }
        return $bbsList;
    }

    public static function addBbsOfAuthUser($loupanId, $accountId, $isMain)
    {

    }

    /**
     * 单个bbs论坛当前用户的简略信息
     */
    public static function getUserByBbsSingle($bbsId, $userId = null)
    {
        if (!$userId) $userId = Yii::$app->user->id;

        $val = (new Query())->select('loupan_id')->from('hll_bbs')->where(['id' => $bbsId, 'valid' => 1])->scalar();

        $user = (new Query())->select(['t1.ect_uid','t1.nickname', 't1.headimgurl'])
            ->from("ecs_wechat_user as t1")
            ->where(['t1.ect_uid'=>$userId])->one();

        return $user;
    }

    /**
     * bbs用户信息
     */
    public static function getUserStatus($bbsId, $fields = null, $userId = null) {
        if(!$fields) {
            $fields  =['t1.user_role','t1.status', 't1.banned_time', 't2.nickname', 't2.headimgurl'];
        }
        if(!$userId) {
            $userId = Yii::$app->user->id;
        }

        $query = (new Query)->select($fields)->from('hll_bbs_user as t1')
                            ->leftJoin('ecs_wechat_user as t2', 't2.ect_uid = t1.account_id')
                            ->where(['t1.bbs_id'=>$bbsId, 't1.account_id'=>$userId])->one();

        return $query;
    }

    /**
     * 判断用户是否解禁
     */
    public static function isLocked($lockTime){
        $time = strtotime($lockTime);
        $now = time();
        if($time > $now){
            $day = ceil(($time - $now) / 3600 / 24);
        }else{
            $day = 0;
        }
        return $day;
    }
}