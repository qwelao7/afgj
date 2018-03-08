<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use console\models\Pay;

use console\models\apix\UtilityRecharge;
use console\models\apix\OrderState;

use console\models\juhe\Broadband;
use console\models\juhe\Ordersta;

/*
* 缴费自动化脚本
* yii pay/order 命令自动扫描账单表进行充值缴费
* yii pay/check-order 命令自动请求第三方服务查询充值状态并更新账单表
*/

class PayController extends Controller{
	public function queryApix($bill){
		$servicedetail = json_decode($bill->servicedetail);
		if(isset($servicedetail->list) && !empty($servicedetail->list)){
			foreach($servicedetail->list as &$val){											
				$utilityrecharge = new UtilityRecharge();						
				$orderid = Pay::getOrderid($bill->id,$val->PayMentDay);
				$type = Pay::getTypeByPid($bill->product->pid);

				//发布时替换面值$val->Balance
				$result = $utilityrecharge->setType($type)->setAccount($servicedetail->account)->setContractid($val->ContractNo)->setPaymentday($val->PayMentDay)->setOrderid($orderid)->setFee(0)->query();
				
				var_dump($result);
				Yii::info($result,'job');
				if('0' == $result->Code){
					$val->SporderId = $result->Data->SporderId;
				}else{
					$val->error = $result->Msg;
				}
				unset($utilityrecharge);
			}
		}
		return $servicedetail;
	}

	public function queryJuhe($bill){							
		$servicedetail = json_decode($bill->servicedetail);

		if($servicedetail && !empty($servicedetail)){
			$broadband = new Broadband();						
			$orderid = Pay::getOrderid($bill->id);

			//发布时替换面值$servicedetail->pervalue
			$result = $broadband->setPhoneno($servicedetail->account)->setPervalue(0)->setOrderid($orderid)->query();
			
			var_dump($result);
			Yii::info($result,'job');
			if('0' == $result->error_code && isset($result->result->uorderid) && $result->result->uorderid == $orderid){
				$servicedetail->sporder_id = $result->result->sporder_id;
			}else{
				$servicedetail->error = $result->reason;
			}
			unset($broadband);
		}
		return $servicedetail;
	}

    public function actionOrder() {
		$pay = new Pay();
		
		$bills = $pay->getBills();
		if($bills && !empty($bills)){
			foreach($bills as $value){
				echo "正在处理账单".$value->id."\r\n";
				Yii::info("正在处理账单".$value->id,'job');
				$api = Pay::getApiByPid($value->product->pid);
				if('apix' == $api){
					$pay->apixBegin($value->id);
					$servicedetail = $this->queryApix($value);
					$pay->apixEnd($value->id,$servicedetail);
				}else if('juhe' == $api){
					$pay->juheBegin($value->id);
					$servicedetail = $this->queryJuhe($value);
					$pay->juheEnd($value->id,$servicedetail);
				}
			}
		}
		return 0;
	}

	public function checkApix($bill){
		$servicedetail = json_decode($bill->servicedetail);
		if(isset($servicedetail->list) && !empty($servicedetail->list)){	
			foreach($servicedetail->list as &$val){
				$orderid = Pay::getOrderid($bill->id,$val->PayMentDay);
				$OrderState = new OrderState();
				$result = $OrderState->setOrderid($orderid)->query();
				var_dump($result);						
				Yii::info($result,'job');
				if('0' == $result->Code){
					if($result->Data && is_array($result->Data)){
						if($result->Data[0]->UserOrderId == $orderid && $result->Data[0]->State == '1'){
							$val->OrderStatus = 1;
						}
					}
				}				
				unset($OrderState);
			}
		}
		return $servicedetail;
	}

	public function checkJuhe($bill){
		$servicedetail = json_decode($bill->servicedetail);
		$orderid = Pay::getOrderid($bill->id);
		$Ordersta = new Ordersta();
		$result = $Ordersta->setOrderid($orderid)->query();
		var_dump($result);
		Yii::info($result,'job');
		if('0' == $result->error_code){
			if(isset($result->result) && $result->result){
				if($result->result->sporder_id == $servicedetail->sporder_id && $result->result->game_state == '1'){
					$servicedetail->OrderStatus = 1;
				}
			}
		}				
		unset($Ordersta);		
		return $servicedetail;
	}

	public function actionCheckOrder(){    
		$pay = new Pay();
		$bills = $pay->getBills(3);

		if($bills && !empty($bills)){
			foreach($bills as $value){
				echo "正在检查账单".$value->id."\r\n";
				Yii::info("正在检查账单".$value->id,'job');
				$api = Pay::getApiByPid($value->product->pid);
				if('apix' == $api){
					$servicedetail = $this->checkApix($value);
					$pay->orderEndByApix($value->id,$servicedetail);
				}else if('juhe' == $api){
					$servicedetail = $this->checkJuhe($value);
					$pay->orderEndByJuhe($value->id,$servicedetail);
				}
			}
		}
	}
}
