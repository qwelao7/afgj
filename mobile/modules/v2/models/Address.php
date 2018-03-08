<?php

namespace mobile\modules\v2\models;
use common\models\ar\user\AccountAddress;
class Address extends  AccountAddress {
    public function scenarios() {
        return ArrayHelper::merge(parent::scenarios(), [
            //获取token
           // 'token' => ['grant_type', 'appid', 'secret'],
        ]);
    }
}
