<?php

namespace mobile\controllers;

use Yii;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends \yii\web\Controller {

    public function actionIndex() {
//        header("Location: http://www.afgj.com/v2/incoming-letter.html");
//        exit;
        $this->view->title = '回来啦社区';
        return $this->render('index');
    }
    public function actionShorturl($url) {
        $shortUrl = Yii::$app->wechat->app->url->shorten($url);
        Yii::$app->response->format=Response::FORMAT_JSON;
        return $shortUrl;
    }
      public function actionLiveTest() {
            $data = $this->getLightData();
            return $this->renderPartial('live-test', ['data'=>$data]);

        }
        public function actionLiveDataUpdate() {
            $data = $this->getLightData();
            return \yii\helpers\Json::encode($data);
        }
        private function getLightData() {
           $data = [];
            $sql = 'SELECT b.building_num, b.house_num FROM account_address a
                    LEFT JOIN fang_house b ON a.house_id=b.id
                    WHERE a.house_id>0 and b.loupan_id=1';
            $sqlData = Yii::$app->db->createCommand($sql)->queryAll();
            if ($sqlData) {
                foreach ($sqlData as $v) {
                    if (isset($data[$v['building_num']]) && in_array($v['house_num'], $data[$v['building_num']])) {
                        continue;
                    } else {
                        $data[$v['building_num']][] = $v['house_num'];
                    }
                }
            }
            return $data;
        }
}
