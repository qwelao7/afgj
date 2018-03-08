{<?php
















































































































































































































































































































































namespace mobile\modules\rest\controllers;

use Yii;
use common\components\Util;
use mobile\components\ActiveController;
use common\models\ar\user\Account;
use common\models\ar\user\AccountAddress;
use common\models\ar\user\AccountAuth;
use common\models\ar\fang\FangHouse;

class EstateController extends ActiveController {

    /**
    *   根据account_address.id获取某处认证房产的房主信息及房产信息
    */
    public function actionAccountinfoByAuth($accountAddressID) {
        /* 房产信息 */
        $address = AccountAddress::find()->where(['id'=>$accountAddressID, 'valid'=>1])->select(['mansion', 'building_house_num'])->asArray()->all();
        $address = implode('', $address[0]);
        /* 某个房产是否认证 */
        $isAuth = AccountAddress::isAuth($accountAddressID);
        /* 房主信息 */
        if($isAuth) $detail = AccountAddress::detailByFang($accountAddressID);
        $info = ($isAuth)?Account::userInfoByHouseID($detail['house_id']):'';
        return $this->renderRest(['info'=>$info, 'address'=>$address, 'isAuth'=>$isAuth]);
    }
    
    /**
    *   新增房产认证(身份证、房产证)
    */
    public function actionAddAuthToBack($data) {
        $transaction = Yii::$app->db->beginTransaction();
        try{
            $model = new AccountAuth;
            $model->account_id = Yii::$app->user->id;
            $model->auth_type = 3;
            $model->failnum = 0;
            $model->address_id = $data['address'];
            $model->authdata = json_encode($data['imgs']);
            $created_at = date('Y-m-d H:i:s', time());
            $model->save();
            $transaction->commit();
        } catch(\yii\db\Exception $e) {
            $transaction->rollback();
            return $this->renderRestErr('提交失败');
        }
        return $this->renderRest($model);
    }

    /**
    *  当前用户对已认证房主的申请房主认证
    */
    public function actionApplyAuthToOwner($data) {
        $transaction = Yii::$app->db->beginTransaction();
        try{
            $model = new AccountAuth;
            $model->account_id = Yii::$app->user->id;
            $model->auth_type = 2;
            $model->failnum = 0;
            $model->address_id = $data['address'];
            $model->authdata = $data['desc'];
            $model->created_at = date('Y-m-d H:i:s',time());
            $model->save();
            $transaction->commit();
        }catch(\yii\db\Exception $e) {
            $transaction->rollback();
            return $this->renderRestErr('提交失败');
        }
        return $this->renderRest($model);
    }

    /**
    *   房产信息详情
    */
    public function actionInfoByFang($accountAddressID) {
        $detail = '';
        /* 房产信息 */
        $detail = AccountAddress::detailByFang($accountAddressID);
        /* 房主信息 */
        $info = [];
        if((int)($detail['house_id']) > 0) {
            $info = Account::userInfoByHouseID($detail['house_id']);
        }
        /* 待审核认证 */
        $to_auth = AccountAuth::find()->where(['auth_type'=>2, 'address_id'=>$accountAddressID, 'failnum'=>0])->asArray()->all();
        if($to_auth) {
            foreach($to_auth as &$item) {
                $item['account_info'] = Account::getAccountInfo($item['account_id']);
                $item['isShow'] = true;
            }
        }
        /* 在某个房产下判断当前用户是否为认证房主，若不是看是否有认证请求 */
        $current = 'estater';
        $refusedCause = "";
        $authEstateCount = AccountAddress::find()->where('id='.$accountAddressID)->andWhere('valid=1 and owner_auth=1')->count();
        if($authEstateCount==0) {
            $authInfo = AccountAuth::find()->where(['address_id'=>$accountAddressID, 'account_id'=>Yii::$app->user->id, 'failnum' => 0])->select(['failnum','failcause'])->one();
            if($authInfo){
                if ($authInfo->failnum > 0 && $authInfo->failcause) {
                    $current = 'refused';
                    $refusedCause =$authInfo->failcause;
                }else {
                    $current = 'unestated';
                }
            }else{
                $current = 'noestate';
            }
        }
        return $this->renderRest(['detail'=>$detail, 'info'=>$info, 'to_auth'=>$to_auth, 'current'=>$current, 'cause'=>$refusedCause]);
    }

    /**
    *   已认证房主对认证进行处理
    */
    public function actionEstateChoose($id, $type, $cause) {
        $data = AccountAuth::find()->where(['id'=>$id])->one();
        if($type) {
            $data->delete();
        }else {
            if($cause != undefined) {
                $data->failnum += 1;
                $data->failcause = $cause;
                $data->save();
            }
        }
        return $this->renderRest($data);
    }

    /**
    * 删除房产
    */
    public function actionFangDelete($accountAddressID, $current) {
        $data = AccountAddress::find()->where(['id'=>$accountAddressID, 'account_id'=>Yii::$app->user->id, 'valid'=>1])->one();
        if($data) {
            $data->valid = 0;
            $data->save();
        }
        return $this->renderRest($data);

    }

    /**
     * 恢复虚拟房产
     */
    public function actionRecoverFang() {
        $fictitious_id = 14;
        $recover = AccountAddress::find()->where('account_id='.Yii::$app->user->id)->andWhere('loupan_id='.$fictitious_id)->one();
        if($recover) {
            $recover->valid = 1;
            $recover->save();
        }
        return $this->renderRest($recover);
    }
}
