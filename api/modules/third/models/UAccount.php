<?php

namespace api\modules\third\models;

use Yii;
use common\models\ar\third\HllThirdLogin;

class UAccount extends HllThirdLogin
{

	public static function register($data)
	{
		$model = new UAccount();
		$model->tname = $data['tname'];
		$model->contact_name = $data['contact_name'];
		$model->contact_phone = $data['contact_phone'];
		$model->appid = $model->makeNewAppId();

		if ($model->save(false)){
			$model->createToken();
			return ['res' => true, 'data'=>$model->appsecret];
		}
		else{
			return ['res' => false, 'data' => $model->getErrors()];
		}
	}

	public function beforeSave($insert)
	{
		if (parent::beforeSaveTrue($insert)) {
			if ($insert) {
				$salt = rand(100000, 999999);
				$this->appsecret = md5($this->appsecret . $salt);
			} elseif ($this->appsecret != $this->oldAttributes['appsecret']) {
				//非插入数据 密码有改动时 重新生成混淆码
				$salt = rand(100000, 999999);
				$this->appsecret = md5($this->appsecret . $salt);
			}
			return true;
		} else {
			return false;
		}
	}

	public function createToken()
	{
		$str = time() . '_token_' . $this->appid . '_' . $this->appsecret . rand(100000, 999999);
		$this->apptoken = substr(md5($str),0,32);
		$this->updated_at = date('Y-m-d H:i:s');
		$this->save(false);
		return $this->apptoken;
	}


	public function makeNewAppId()
	{
		do{
			$count = static::find()->where(['valid'=>'1'])->count();

			$appId = substr(md5('appId'.$count.time().rand(10000,99999)),0,32);
			$a = static::findOne(['valid'=>'1','appid'=>$appId]);
			if (!$a){
				break;
			}
		}while(1);
		return $appId;
	}

}
