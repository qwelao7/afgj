<?php
namespace console\models;

use Yii;
use yii\base\model;
use common\models\bill\Bill;

class Pay extends model{
	public static function getTypeByPid($pid){
		$jiaofei = Yii::$app->params['jiaofei'];
		return isset($jiaofei[$pid]['type'])?$jiaofei[$pid]['type']:'';
	}

	public static function getApiByPid($pid){
		$jiaofei = Yii::$app->params['jiaofei'];
		return isset($jiaofei[$pid]['api'])?$jiaofei[$pid]['api']:'';
	}

	public static function getOrderid($id,$PayMentDay=''){
		return "00000000".($PayMentDay?$id.'.'.$PayMentDay:$id);
	}

	public function getBills($workstatus=2){
		return Bill::find()->where('ubtype=1 and paystatus=1 and ubstatus=1 and workstatus=:workstatus',['workstatus'=>$workstatus])->orderBy('edit_time asc')->all();
	}

	public function setWorkstatus($id,$workstatus){
		$bill =  Bill::findOne($id);
		$bill->workstatus = $workstatus;
		$bill->save();
	}

	public function apixBegin($id){
		$bill =  Bill::findOne($id);
		$bill->workstatus = 3;
		$bill->save();
	}

	public function juheBegin($id){
		$this->apixBegin($id);
	}

	public function apixEnd($id,$servicedetail){
		$bill = Bill::findOne($id);
		if(isset($servicedetail->list) && !empty($servicedetail->list)){
			foreach($servicedetail->list as $val){
				if(!isset($val->SporderId) || empty($val->SporderId)){
					$bill->workstatus = 7;
				}
			}
		}
		$bill->servicedetail = json_encode($servicedetail);
		$bill->save();
	}

	public function juheEnd($id,$servicedetail){
		$bill = Bill::findOne($id);
		if(!isset($servicedetail->sporder_id) || empty($servicedetail->sporder_id)){
			$bill->workstatus = 7;
		}			
		$bill->servicedetail = json_encode($servicedetail);
		$bill->save();
	}

	public function orderEndByApix($id,$servicedetail){
		$bill = Bill::findOne($id);
		if(isset($servicedetail->list) && !empty($servicedetail->list)){
			$i = 0;
			foreach($servicedetail->list as $val){
				if(isset($val->OrderStatus) && '1' == $val->OrderStatus){
					++$i;
				}
			}
			if($i == count($servicedetail->list)){
				$bill->workstatus = 4;
				$bill->ubstatus = 2;
				$bill->servicedetail = json_encode($servicedetail);
				$bill->save();
			}
		}
	}

	public function orderEndByJuhe($id,$servicedetail){
		$bill = Bill::findOne($id);
		if(isset($servicedetail->OrderStatus) && '1' == $servicedetail->OrderStatus){
				$bill->workstatus = 4;
				$bill->ubstatus = 2;
				$bill->servicedetail = json_encode($servicedetail);
				$bill->save();
		}		
	}
}