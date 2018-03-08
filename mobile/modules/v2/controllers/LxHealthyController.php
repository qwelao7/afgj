<?php
namespace mobile\modules\v2\controllers;

use common\components\WxTmplMsg;
use common\models\hll\HllLxhealthyTemp;
use mobile\components\ApiController;
use Yii;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
/**
 * 蓝熙健康接口控制器
 * Created by PhpStorm.
 * User: nancy
 * Date: 2016/12/27
 * Time: 12:12
 */
class LxHealthyController extends ApiController{

    public function actionReceiveData(){

        $response = new ApiResponse();
        $templateId = Yii::$app->request->post('templateId');
        $openId = Yii::$app->request->post('openId');
        $title = Yii::$app->request->post('title');
        $data = Yii::$app->request->post('data');
        if(empty($templateId) || empty($openId)){
            $response->data = new ApiData('101','缺少openId或模板Id');
            return $response;
        }

        if(empty($data) || empty($title)){
            $response->data = new ApiData('102','缺少title或data');
            return $response;
        }
        $info = HllLxhealthyTemp::saveTempData($templateId,$openId,$title,$data);
        if($info){
            $response->data = new ApiData('0','存储成功！');
            $response->data->info = $info;
            return $response;
        }else{
            $response->data = new ApiData('100','存储失败！');
            return $response;
        }
    }
}