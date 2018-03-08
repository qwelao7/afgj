<?php
/**
 *
 * @author: xuyi
 * @date: 2017/3/20
 */

namespace common\models\event;


use common\components\Util;
use common\models\hll\HllEventGiftLog;
use common\models\hll\HllEventPhone;
use common\models\hll\HllUserPoints;
use common\models\hll\HllUserPointsLog;
use yii\base\Model;
use yii;


class SignModel extends Model
{
	private $res;

	private $error;

	private $except;

	private $community_id;

	/**
	 * @param mixed $community_id
	 */
	public function setCommunityId($community_id)
	{
		$this->community_id = $community_id;
	}



	/**
	 * @return mixed
	 */
	public function getRes()
	{
		return $this->res;
	}

	/**
	 * @param mixed $res
	 */
	private function setRes($res)
	{
		$this->res = $res;
	}

	/**
	 * @return mixed
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @param mixed $error
	 */
	private function setError($error)
	{
		$this->error = $error;
	}

	/**
	 * @return mixed
	 */
	public function getExcept()
	{
		return $this->except;
	}

	/**
	 * @param mixed $except
	 */
	private function setExcept($except)
	{
		$this->except = $except;
	}



	public  function sign($user_id,$event_id,$phone)
	{
		try{
			if (HllEventGiftLog::checkHasReceiveGift($user_id,$event_id)){
				$this->setError('该帐号已经领取过了');
				return ['code'=>0,'message'=>'succeed'];
			}
			$check = HllEventPhone::checkPhoneUsed($phone);
			if ($check['is_used']){
				if($check['res_id'] == 0){
					$this->setError('该号码不对!');
					return ['code'=>0,'message'=>'not_staff'];
				}else{
					$this->setError('该号码已被使用!');
					return ['code'=>0,'message'=>'is_used'];
				}

			}

			return $this->giveGift($user_id,$check['res_id'],$event_id);

		}catch (\Exception $e){
			$this->setError('发生异常:' .$e->getMessage());
			$this->setExcept($e);
			return ['code'=>0,'message'=>$e->getMessage()];
		}
	}

	private function giveGift($user_id,$res_id,$event_id)
	{
		$data['community_id'] = $this->community_id;
		$data['point'] = 5000;
		$data['remark'] = '中花岗圣诞活动';
		$data['expire_time'] = Util::expireTime(1);
		try{
			HllUserPointsLog::signExpend($user_id,$data);
			$model = new HllEventGiftLog();
			$model->user_id = $user_id;
			$model->event_id = $event_id;
			$model->res_id = $res_id;
			$model->gitf_id = 1;
			$model->gift_content = '中花岗圣诞活动5000友元';
			$model->save();

			$point = HllEventPhone::findOne($res_id);
			if ($point->phone_type==2){//贵宾码不记录用户

			}else{
				$point->user_id = $user_id;
				$point->save(false);
			}
		}catch (\Exception $e){
			$this->setError('发生异常: '.$e->getMessage());
			$this->setExcept($e);
			return ['code'=>0,'message'=>$e->getMessage()];
		}
		return ['code'=>1,'message'=>'success'];

	}
}