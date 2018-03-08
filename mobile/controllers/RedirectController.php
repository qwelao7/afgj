<?php
namespace mobile\controllers;

use Yii;
use mobile\components\Controller;
/**
 * Description of CallbackController
 *
 * @author Don.T
 */
class RedirectController extends Controller {

    /**
     * 从外部进入社区链接的页面跳转
     * @param string $url
     * @return \yii\web\Response
     * @author zend.wang
     * x@date  2016-06-30 13:00
     */
    public function actionIndex(){
        $state=f_get('state','');
        $code = f_get('code',Yii::$app->session->get('code'));
        if($state && $code) {
            return $this->redirect("/?code={$code}&state=#{$state}");
        }
        return $this->redirect("/");
    }

}
