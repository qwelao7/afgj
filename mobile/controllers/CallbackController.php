<?php
namespace mobile\controllers;

use Yii;
use yii\web\Controller;
//use  common\components\WxTmplMsg;
/**
 * Description of CallbackController
 *
 * @author Don.T
 */
class CallbackController extends Controller {
    
    public $enableCsrfValidation = false;
    
    public function actionWeiXinPay() {
        Yii::$app->pay->weiXinCallback();
    }
    public function actionIndex(){
        //$data =  ['first'=>['value'=>"蒋柯,你在回来啦社区预订的清洁服务！"],
        //    'keynote1'=>['value'=>"已完成", "color"=>"#173177"],
        //    'keynote2'=>['value'=>date('Y-m-d H:i:s'), "color"=>"#173177"],
        //    'remark'=>['value'=>"感谢关注回来啦社区。"]];
        //$redirectUrl = "http://www.afguanjia.com";//oDj6HjpZ1ZNUd2dRoWPl-l3Kn8Y4

        //WxTmplMsg::changeAccountRemind(85,'您收到新的业主信息','发消息',"testtest","#/community-sayhi/");
        //$redirectUrl = "#/community-neighbor/2/0";
        //$redirectUrl =  Yii::$app->request->hostInfo.'/redirect?r='.$redirectUrl;
        //echo $redirectUrl;
        //echo Yii::$app->request->hostInfo.'/'.Yii::$app->request->pathInfo;
        //echo Yii::$app->request->hostInfo;
        //echo "<br/>";
        //echo Yii::$app->request->url;
        //echo Yii::$app->request->hostInfo.Yii::$app->request->url;
        f_d(Yii::getAlias('@uploads'));
    }



}
