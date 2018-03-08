<?php

namespace mobile\components\filter;
use Yii;
use yii\filters\auth\QueryParamAuth;
use yii\web\UnauthorizedHttpException;
class WxQueryParamAuth extends QueryParamAuth {
    /**
     *
     * @inheritdoc
     */
    public function handleFailure($response) {
        throw new UnauthorizedHttpException('签名校验失败');
    }

}