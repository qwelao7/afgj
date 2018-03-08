<?php

namespace mobile\modules\rest\controllers;

use Yii;
use mobile\components\ActiveController;
use common\models\ar\user\AccountAddress;
use common\models\ar\user\Account;
use common\models\ar\system\Area;
use common\models\ar\user\AccountSkill;
use common\models\ar\user\HomePagePic;
use common\models\ar\fang\FangHouse;

use yii\db\Query;
use common\models\hll\UserAddressExt;
use common\models\ecs\EcsUsers;

class UserController extends ActiveController {

    public function actions() {
        return [
            //网页验证码
            'captcha' => [
                'class' => 'common\components\NumberCaptchaAction',
                'minLength' => 4,
                'maxLength' => 4,
                'backColor' => 0xf3f3f3
            ],
            //手机验证码
            'captchaphone' => [
                'class' => 'common\components\NumberCaptchaAction',
                'height' => 1,
                'width' => 1,
                'minLength' => 6,
                'maxLength' => 6,
                'backColor' => 0xf3f3f3
            ],
        ];
    }

    public function actionInfo() {
        return $this->renderRest([
            'name' => Yii::$app->user->identity->full_name,
            'sex' => Yii::$app->user->identity->sex,
            'nickname'=>Yii::$app->user->identity->nickname,
            'phone' => Yii::$app->user->identity->primary_mobile,
            'address' => Yii::$app->user->identity->address,
        ]);
    }

    /**
     * 获取用户地址列表
     */
    public function actionAddressList() {
        $data = AccountAddress::find()
                ->select(['id', 'account_id', 'title', 'mobile', 'areacode','mansion', 'contact_to', 'owner_auth', 'is_default','building_house_num', 'house_id', 'loupan_id'])
                ->where(['account_id'=>Yii::$app->user->id, 'valid'=>1])->orderBy(['is_default'=>SORT_ASC])->asArray()->all();
                foreach($data as $key=>$value) {
                  $data[$key]['realcode'] = $data[$key]['areacode'];
                  $data[$key]['areacode'] = Area::parentsStr($data[$key]['areacode']);
                };
        return $this->renderRest($data);
    }

    /**
     * 获取用户地址列表(v2)
     * @params $userId
     */
    public function actionAddressItems($userId = null) {
        if(!$userId)  $userId = Yii::$app->user->id;
        $data = (new Query())->select(['t1.address_id', 't1.address', 't1.sign_building', 't2.is_default', 't2.owner_auth'])->from('ecs_user_address as t1')
            ->leftJoin('hll_user_address_ext as t2', 't2.address_id = t1.address_id')
            ->where(['t1.user_id'=>$userId, 't2.valid'=>1])->orderBy(['t2.is_default'=>SORT_ASC, 't2.owner_auth'=>SORT_DESC])->all();

        return $this->renderRest($data);
    }

    /**
     * 获取某处房产的信息(v2)
     * @params $addressId
     */
    public function actionInfoByAdd($addressId) {
        if(!$addressId) return false;

        $info = (new Query())->select(['t1.address_id', 't1.consignee', 't1.province', 't1.city', 't1.district', 't1.address', 't1.mobile', 'sign_building', 't2.house_id', 't2.owner_auth', 't2.is_default'])
                ->from('ecs_user_address as t1')->leftJoin('hll_user_address_ext as t2', 't2.address_id = t1.address_id')
                ->where(['t1.address_id'=>$addressId, 't2.valid'=>1])->one();
        return $info;
    }

    public function actionIndexPic() {
        $result =  HomePagePic::find()->join('INNER JOIN','account_favor','home_page_pic.loupan_id=account_favor.item_id')
                                                  ->where(['account_favor.account_id'=>Yii::$app->user->id])
                                                  ->count();
        if($result>0){
            $data = HomePagePic::find()->join('INNER JOIN','account_favor','home_page_pic.loupan_id=account_favor.item_id')
                                        ->where(['account_favor.account_id'=>Yii::$app->user->id])
                                        ->select(['loupan_logo', 'loupan_pics','loupan_url'])->asArray()->all();
            foreach($data as $key=>$value)  {
                $data[$key]['loupan_pics'] = json_decode($value['loupan_pics']);
            }
        }else{
            $data= HomePagePic::find()->where(['isdefault'=>1])->select(['loupan_logo', 'loupan_pics','loupan_url'])->asArray()->all();
        }
        return $this->renderRest($data);
    }

    /**
     * 添加地址
     */
    public function actionAddAddress($contact_to, $mobile, $mansion,$building_house_num, $areacode, $is_default) {
        $model = new AccountAddress();
        $model->account_id = Yii::$app->user->id;
        $model->contact_to = $contact_to;
        $model->mobile = $mobile;
        $model->areacode = $areacode;
        $model->mansion = $mansion;
        $model->is_default = $is_default;
        $model->building_house_num = $building_house_num;
        if (AccountAddress::findOne(['account_id'=>Yii::$app->user->id])) {
            $model->is_default = 'no';
        } else {
            $model->is_default = 'yes';
        }
        if ($model->save()) {
            return $this->renderRest($model->attributes);
        } else {
            return $this->renderRestErr('添加失败');
        }
    }

    /**
     * 编辑地址
     * @param type $id
     * @param type $name
     * @param type $phone
     * @param type $address
     * @return type
     */
    public function actionUpdateAddress($id, $contact_to, $mobile, $mansion,$is_default,$building_house_num, $areacode) {
        $model = AccountAddress::findOne($id);
        if ($model) {
            $model->account_id = Yii::$app->user->id;
            $model->contact_to = $contact_to;
            $model->mobile = $mobile;
            $model->areacode = $areacode;
            $model->mansion = $mansion;
            if('是' == $is_default)AccountAddress::setDefault($id, Yii::$app->user->id);
            $model->building_house_num = $building_house_num;
            if ($model->save()) {
                return $this->renderRest($model->attributes);
            }
        }
        return $this->renderRestErr('编辑失败');
    }

    /**
     * 设置默认地址
     * @param type $id
     * @return type
     */
    public function actionSetDefaultAddress($id) {
        $flag = AccountAddress::setDefault($id, Yii::$app->user->id);
        if ($flag) {
            return $this->renderRest('设置成功');
        } else {
            return $this->renderRestErr('设置失败');
        }
    }

    /**
     * 删除地址
     */
    public function actionDeleteAddress($id) {
        $model = AccountAddress::find()->where([
            'id'=>$id,
            'account_id'=>Yii::$app->user->id,
            'valid' => 1
        ])->one();
        if ($model) {
            if ($model->is_default=='yes') {
                return $this->renderRestErr('默认地址无法删除');
            }
            $model->valid = 0;
            if ($model->save()) {
                return $this->renderRest('删除成功');
            }
        }
        return $this->renderRestErr('删除失败');
    }

    /**
     * 更新用户信息
     * @param type $key
     * @param type $val
     */
    public function actionUpdate($key, $val) {
        if (!in_array($key, ['name', 'sex', 'nick', 'avatar'])) {
            return $this->renderRestErr('非法字段，不可修改！');
        }
        if ($key=='name') {
            $key = 'full_name';
        };
        if($key=='nick') {
            $key = 'nickname';
        };
        if($key=='avatar') {
            $key = 'avatar';
            $val= substr(parse_url($val,PHP_URL_PATH ), 1);
        };
        $model = Account::findOne(Yii::$app->user->id);
        $model->setAttribute($key, $val);
        if ($model->save()) {
            return $this->renderRest('设置成功');
        } else {
            return $this->renderRestErr('设置失败');
        }
    }

    /**
     * 发送短信验证码
     */
    public function actionSendCaptch($phone, $code=0) {
        $captchPhone = $this->createAction('captchaphone')->getVerifyCode(true);
        $captch = $this->createAction('captcha')->getVerifyCode();
        $this->createAction('captcha')->getVerifyCode(true);
        if ($code==$captch) {
            if (Yii::$app->sms->send($phone, 'bindMobile', ['code' => $captchPhone])) {
                return $this->renderRest('发送成功');
            } else {
                return $this->renderRestErr('发送失败');
            }
        } else {
            return $this->renderRestErr('验证码错误，请重新获取验证码');
        }
    }

    /**
     * 绑定手机
     */
    public function actionBindMobile($code, $phone) {
        $phoneCode = $this->createAction('captchaphone')->getVerifyCode();
        //刷新手机验证码
        $this->createAction('captchaphone')->getVerifyCode(true);
        if($phoneCode != $code)return $this->renderRestErr('验证码错误，请重新获取验证码');
        $trans = Yii::$app->db->beginTransaction();
        try{
            Account::bindMobile(Yii::$app->user->id, $phone);
            AccountAddress::addCustomerHouse($phone, Yii::$app->user->id);
            $trans->commit();
            return $this->renderRest(['realName'=>Yii::$app->user->identity->full_name, 'hasNoSkill'=>0==count(Yii::$app->user->identity->skills)]);
        }catch(\yii\db\Exception $e){
            $trans->rollBack();
            return $this->renderRestErr('绑定失败，请重试');
        }
    }

    /**
     * 上传图片
     * @param type $mediaId
     * @return type
     */
    public function actionUploadImg($mediaId) {
        $img = Yii::$app->upload->saveImgToUrl('https://api.weixin.qq.com/cgi-bin/media/get?access_token='.Yii::$app->wx->wxToken.'&media_id='.$mediaId, 'fang');
        return $this->renderRest(Yii::$app->upload->domain.$img['path']);
    }

    /***
    * 技能列表
    **/
    public function actionUserSkill() {
        $data = AccountSkill::findBySql('SELECT id,skill FROM account_skill GROUP BY skill ORDER BY count(1) desc,skill asc limit 100')->all();
        return $this->renderRest($data);
    }

    /*
    *增加用户技能
    */
    public function actionAddSkill($skill) {
        $now = date('Y-m-d H:i:s', time());
        $model = new AccountSkill();
        $model->account_id = Yii::$app->user->id;
        $model->skill = $skill;
        $model->created_at = $now;
        if ($model->save()) {
            return $this->renderRest($model->attributes);
        } else {
            return $this->renderRestErr('添加失败');
        }
    }

    /*
    * 删除用户技能
    */
    public function actionDeleteSkill($skill) {
        $model = AccountSkill::find()->where([
            'account_id'=>Yii::$app->user->id,
            'skill' => $skill
        ])->one();
        if($model) {
            $model->delete();
            return $this->renderRest('删除成功');
        }else {
            return $this->renderRestErr('删除失败');
        }
    }

    /*
    * 获取用户技能
    */
    public function actionGetSkill() {
        $data = AccountSkill::find()->where(['account_id'=>Yii::$app->user->id])->orderBy(['created_at'=>SORT_DESC])->select(['id','skill'])->distinct()->asArray()->all();
        return $this->renderRest($data);
    }

    /**
    *获取用户头像
    */
    public function actionGetAvatar() {
        $data = Account::find()->where(['id'=>Yii::$app->user->id])->select(['avatar','nickname', 'sex', 'primary_mobile'])->one();
        $data->avatar = Account::getAvatar($data->avatar);
        return $this->renderRest($data);
    }

    /**
     * 判断用户是否有虚拟房产
     */
    public function actionIsOwnFictitiousFang() {
        $fictitious_id  = 14;
        $data = AccountAddress::find()->where(['loupan_id'=>$fictitious_id, 'account_id'=>Yii::$app->user->id])->count();
        $data = (bool)$data;
        return $this->renderRest($data);
    }
}
