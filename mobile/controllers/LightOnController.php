<?php

namespace mobile\controllers;

use Yii;
use yii\helpers\Url;

/**
 *  点灯控制器,支持多楼盘同时点灯
 */
class LightOnController extends \yii\web\Controller {

    public $layout = false;

    /**
     * 点灯首页
     * @return string
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionIndex() {
        $loupanId =5;
        if(!empty(f_params('light_activity_loupan')) && in_array($loupanId,f_params('light_activity_loupan'))){

            $this->view->title = '徐州东方润园楼盘点灯活动-绿城东方';
            $result =  $this->getData($loupanId);
            return $this->render('index',['loupanId'=>$loupanId,'result'=>$result]);
        }
    }
    /**
     * 点灯首页2
     * @return string
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionIndex2() {
        $loupanId =5;
        if(!empty(f_params('light_activity_loupan')) && in_array($loupanId,f_params('light_activity_loupan'))){

            $this->view->title = '徐州东方润园楼盘点灯活动-绿城东方';
            $result =  $this->getData($loupanId);
            return $this->render('index2',['loupanId'=>$loupanId,'result'=>$result]);
        }
    }
    /**
     * 增量更新点灯
     * @param $loupanId
     * @param int $time
     * @return string
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionLighting($loupanId,$time=0) {
        if(!empty(f_params('light_activity_loupan')) && in_array($loupanId,f_params('light_activity_loupan'))) {
            $result = $this->getData($loupanId, $time);
            return json_encode($result);
        }
    }

    /**
     * 虚拟点灯,默认30秒
     * @param string $loupanId
     * @param int $time
     * @throws \yii\db\Exception
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionVirtualLighting($time=30) {
        $loupanId='东方润园(徐州)';
        Yii::$app->db->createCommand('call sp_lightup(:in_a,:in_b)')->bindValues([":in_a" => $loupanId, ":in_b" => $time])->execute();
    }

    /**
     * 取消虚拟点灯
     * @throws \yii\db\Exception
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionCancelVirtualLighting() {
        $sql="update fang_house_lighting set fake_light=0 where loupan_id=5 and fake_light=1";
        Yii::$app->db->createCommand($sql)->execute();
        //$this->redirect("/light-on/index");
    }

    private function getData($loupanId,$time=0) {

        $userSql="select t2.nickname,t2.avatar,t1.building_num,t1.unit_num,t1.house_num
                    from fang_house_lighting as t1
                    left join account as t2 on t2.id= t1.user_id
                    where t1.loupan_id={$loupanId} and t1.is_light=1";
        $sql = "select `top`,`left`,`width`,`height`,`lighting_img`,`update_time` from fang_house_lighting where loupan_id={$loupanId} ";
        if($time > 0 ) {
            $sql .=" and ( (is_light=1 and update_time>{$time} ) or fake_light=1)";
            $userSql .=" and update_time>{$time}";
        }else {
            $sql .=" and (is_light=1 or fake_light=1)";
        }
        $sql.=' order by update_time desc';

        $data = Yii::$app->db->createCommand($sql)->queryAll();
        if ($data) {
            $time =$data[0]['update_time'];
        }

        $userSql.=' order by update_time desc limit 15';
        $users = Yii::$app->db->createCommand($userSql)->queryAll();
        return ['data'=>$data,'time'=>$time,'users'=>$users];
    }
}
