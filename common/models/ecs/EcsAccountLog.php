<?php
namespace common\models\ecs;
use common\models\hll\HllUserPointsLog;
use common\models\hll\HllUserPoints;
use Yii;
use yii\base\Exception;
use yii\db\Query;
/**
 * This is the model class for table "{{%ecs_account_log}}".
 *
 * @property integer $log_id
 * @property integer $user_id
 * @property integer $user_money
 * @property integer $frozen_money
 * @property integer $rank_points
 * @property integer $pay_points
 * @property integer $discount
 * @property integer $change_time
 * @property string $change_desc
 * @property integer $change_type
 */
class EcsAccountLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_account_log';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'rank_points', 'pay_points', 'user_money', 'frozen_money', 'change_time','change_type'], 'required'],
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'user_money' => 'User Money',
            'frozen_money' => 'Frozen Money',
            'rank_points' => 'Rank Points',
            'pay_points' => 'Pay Points',
            'change_time' => 'Change Time',
            'change_type' => 'Change Type',
            'change_desc' => 'Change Desc',
        ];
    }

    /**
     * 用户账户变动
     * @params array community community_id 数组
     * @param int $user_id 用户Id
     * @param int $user_money 用户资金
     * @param int $frozen_money 冻结资金
     * @param int $rank_points
     * @param int $pay_points 感谢积分
     * @param string $change_desc 变更注释
     * @param int $change_type 变更类型
     * @return bool 返回类型  true为成功  false为失败
     */

    public static function log_account_change($community_id, $user_id, $to_user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = 99) {
        /* 插入帐户变动记录 */
        $model = new EcsAccountLog();
        $account_log = array(
            'user_id' => $user_id,
            'user_money' => $user_money,
            'frozen_money' => $frozen_money,
            'rank_points' => $rank_points,
            'pay_points' => $pay_points,
            'change_time' => time(),
            'change_desc' => $change_desc,
            'change_type' => $change_type
        );
        $trans = Yii::$app->db->beginTransaction();
        try{
            $model->load($account_log,'');
            if($model->save()) {
                $user_name = EcsUsers::getUser($user_id,['t2.nickname','t2.headimgurl']);
                $to_user_name = EcsUsers::getUser($to_user_id,['t2.nickname','t2.headimgurl']);
                $data = [
                    'unique_id'=>uniqid('hll_point_'),
                    'category'=>'thanks',
                    'user_img'=>$user_name['headimgurl'],
                    'to_user_img'=>$to_user_name['headimgurl'],
                    'remark'=>'感谢'.$to_user_name['nickname'],
                    'to_remark'=>$user_name['nickname'].'感谢您',
                    'type'=>HllUserPointsLog::EXPEND_POINT_TYPE,
                    'to_type'=>HllUserPointsLog::INCOME_POINT_TYPE,
                    'scenes'=>HllUserPointsLog::$scenes_type[3],
                    'community_id'=>$community_id
                ];

                HllUserPoints::givePoints($user_id, $to_user_id, $pay_points, $data);//给用户减积分
                $trans->commit();
                return true;
            }
        }catch(\yii\db\Exception $e){
            $trans->rollBack();
            return false;
        }
    }
}