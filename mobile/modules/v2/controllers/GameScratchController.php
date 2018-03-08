<?php
namespace mobile\modules\v2\controllers;

use common\models\ecs\EcsUserAddress;
use common\models\hll\HllGameScratchCardDetail;
use common\models\hll\HllUserPointsLog;
use Yii;
use yii\base\Exception;
use Yii\db\Query;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use common\components\Util;
class GameScratchController extends ApiController{

    /**
     * 是否具备游戏资格
     * @param $id -> 刮刮卡id
     * @return ApiResponse
     */
    public function actionGameAuth($id){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $date = date('Y-m-d',time());
        $game = (new Query())->select(['community_id','end_time','start_time','play_times_rule','play_times'])
            ->from('hll_game_scratch_card')->where(['id'=>$id,'valid'=>1])->one();
        try{
            if(!$game){
                throw new Exception('数据错误',101);
            }

            $address = EcsUserAddress::getAuthAddressByUserId($user_id);
            if(!$address){
                throw new Exception('您还没有认证的房产',102);
            }

            if($game['community_id'] != 0){
                $num = (new Query())->from('hll_user_address')
                    ->where(['community_id'=>$game['community_id'],'account_id'=>$user_id,'owner_auth'=>1,'valid'=>1])->count();
                if($num == 0){
                    throw new Exception('您没有该小区认证的房产',102);
                }
            }
            //判断活动时间及黑名单
            $black = (new Query())->from('hll_game_blacklist')
                ->where(['user_id'=>$user_id,'valid'=>1,'game_id'=>$id])->count();
            if(strtotime($game['start_time']) > time()){
                throw new Exception('活动还没有开始',103);
            }
            if(strtotime($game['end_time']) < time()){
                throw new Exception('活动已结束',104);
            }
            if($black > 0){
                throw new Exception('您已被拉入黑名单，不能参加游戏',105);
            }
            //判断活动次数
            if($game['play_times_rule'] == 1){
                $num = (new Query())->from('hll_game_scratch_card_detail')
                    ->where(['user_id'=>$user_id,'scid'=>$id,'valid'=>1])->count();
                if($num >= $game['play_times']){
                    throw new Exception('您的活动次数已用完，感谢您的参与！',102);
                }else{
                    $num = $game['play_times'];
                }
            }else{
                $num = (new Query())->from('hll_game_scratch_card_detail')
                    ->where(['user_id'=>$user_id,'scid'=>$id,'valid'=>1,'game_date'=>$date])->count();
                if($num >= $game['play_times']){
                    throw new Exception('您今天的活动次数已用完，欢迎明天再来！',103);
                }else{
                    $num = $game['play_times'] - $num;
                }
            }
            //判断呱呱卡数量
            $card_detail = (new Query())->select(['id','scid','point'])->from('hll_game_scratch_card_detail')
                ->where(['scid'=>$id,'user_id'=>0,'valid'=>1,'send_status'=>1,'game_date'=>$date])->orderBy(['id'=>SORT_ASC])->one();
            if(!$card_detail){
                throw new Exception('刮刮卡已经用完！',104);
            }
            $card_detail['num'] = $num;
            $response->data = new ApiData(0,'开始游戏');
            $response->data->info = $card_detail;
        }catch (Exception $e){
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
    return $response;
    }

    /**
     * 开始游戏
     * @param $id -> scid
     * @params $card_id -> card.id
     * @return ApiResponse
     */
    public function actionGameStart($id,$card_id){
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $community_id = (new Query())->select(['community_id'])->from('hll_game_scratch_card')
            ->where(['id'=>$id,'valid'=>1])->scalar();

        $trans = Yii::$app->db->beginTransaction();

        try{
            $card_detail = HllGameScratchCardDetail::find()
                ->where(['id'=>$card_id,'user_id'=>0,'valid'=>1])->one();

            if(!$card_detail){
                throw new Exception('刮的太慢，被人抢先一步啦',104);
            }

            $card_detail->user_id = $user_id;
            $card_detail->send_status = 2;
            $card_detail->taken_time = date('Y-m-d H:i:s',time());

            if($card_detail->save()){
                $data['community_id'] = $community_id;
                $data['point'] = $card_detail->point;
                $data['remark'] = '刮刮卡获取积分';
                $data['expire_time'] = Util::expireTime(12);
                if(HllUserPointsLog::gameExpend($id,$user_id,$data)){
                    $trans->commit();
                    $response->data = new ApiData();
                    $response->data->info = $card_detail;
                    return $response;
                }
            }
            throw new Exception('姿势不对，建议换个时间再来碰碰运气',105);
        }catch (Exception $e){
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(),$e->getMessage());
        }
        return $response;
    }

    public function actionShowNum($id){

    }
}