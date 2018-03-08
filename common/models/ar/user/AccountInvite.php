<?php

namespace common\models\ar\user;

use common\models\ar\activity\ActivityAward;
use common\models\ar\award\Award;
use common\models\ar\award\AwardItem;
use Yii;
use yii\db\Query;
/**
 * This is the model class for table "account_invite".
 *
 * @property string $id
 * @property integer $account_id
 * @property string $invite_account_id
 * @property integer $award_id
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 * @property integer $valid
 */
class AccountInvite extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'account_invite';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['account_id', 'invite_account_id'], 'required'],
            [['account_id', 'award_id', 'status', 'valid'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['invite_account_id'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '用户邀请编号',
            'account_id' => '用户编号',
            'invite_account_id' => '被邀请邻居编号',
            'award_id' => '奖励方案编号',
            'status' => '奖励状态：1未奖励，2已奖励，3已收回',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'valid' => '存在状态：0删除，1存在',
        ];
    }

    /**
     * 获取成功邀请的人数、已奖励的金额
     * @param $userId
     * @return array
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public static function getInvitationStatByUserId($userId,$award_id) {

        //获取成功邀请的人数
        $invitationSuccessCount = (new Query())->from("account_invite")->where(['account_id'=>$userId,'valid'=>1,'status'=>[1,2]])
                            ->distinct(true)->count();
        //已奖励的金额
        $invitationSuccessMoney = (new Query())->select('t2.award_num')
            ->from("account_invite as t1")
            ->leftJoin("award as t2","t2.id = t1.award_id")
            ->where(['t1.account_id'=>$userId,'t1.valid'=>1,'t1.award_id'=>$award_id,'t1.status'=>2 ])
            ->sum("t2.award_num");
        return [(int)$invitationSuccessCount,(int)$invitationSuccessMoney];

    }
    /**
     * 邀请奖励日志
     * @param $userId
     * @return array
     * @author zend.wang
     * @date  2016-08-12 13:00
     */
    public static function getInvitationListByUserId($userId,$award_id) {
        $invitationLogs= (new Query())->select('t1.invite_account_id as friend_id,t2.award_num as money,t3.nickname,t3.avatar')
            ->from("account_invite as t1")
            ->leftJoin("award as t2","t2.id = t1.award_id")
            ->leftJoin("account as t3","t3.id = t1.invite_account_id")
            ->where(['t1.account_id'=>$userId,'t1.valid'=>1,'t1.award_id'=>$award_id,'t1.status'=>2 ])->all();
        foreach($invitationLogs as &$logs) {
            $logs['desc'] = AccountFriend::getHouseInfo($logs['friend_id']);
        }
        return $invitationLogs;

    }
    /**
     * 邀请奖励
     * @param $fromUserId 邀请人
     * @param $toUserId 被邀请人
     * @param $awardId 发放奖励ID
     */
    public static function getAwardByInvitation($fromUserId,$toUserId,$activityId) {

        //不能有重复记录
        $data = AccountInvite::find()->where(['account_id'=>$fromUserId, 'invite_account_id'=>$toUserId, 'valid'=>1])->asArray()->one();
        if ($data) {
            return false;
        }

        $model = new AccountInvite();
        $model->account_id = $fromUserId;
        $model->invite_account_id = $toUserId;
        //插之前 活动是否存在
        $data = ActivityAward::find()->select('award_id, end_time, start_time')
            ->where(['activity_id'=>$activityId,'valid'=>1])->asArray()->one();
        $start_time = strtotime($data['start_time']);
        $end_time = strtotime($data['end_time']);
        $model->award_id = $data['award_id'];
        if($data && $start_time < time() && $end_time > time()) {
            //插之前 奖品是否存在 数量是否
            $award = Award::find()->join('INNER JOIN', 'award_item', 'award.award_item_id = award_item.id')
                ->select('award.award_num')
                ->addSelect('award_item.ai_num, award_item.id')
                ->where(['award.id' => $data['award_id'], 'award.valid' => 1])
                ->andWhere(['>','award_item.ai_num','award.award_num'])
                ->andWhere(['award_item.valid'=>1])->asArray()->one();
            if($award) {
                $num = $award['ai_num'] - $award['award_num'] ;
                $result = AwardItem::find()->where(['id'=>$award['id']])->one();
                $result->ai_num = (int)$num;
                if ($result && $result->save()) {
                    $model->status=2;
                    $model->save();
                    return true;
                }
            }
        } else{
            $model->save();
            return false;
        }
    }
}
