<?php

namespace common\components\templatemessage;

use Yii;
use common\models\ecs\EcsWechatUser;
use yii\base\Object;
use yii\base\Exception;
use common\models\ar\message\MessageNotification;

/**
 * 模板消息基础类
 * Class TemplateMessage
 * @package common\components\templatemessage
 */
class TemplateMessage extends Object {

    public $userId;
    public $touser;
    public $template_id;
    public $url;
    public $topcolor = "#6AA84F";

    public $data;

    public function init() {
        parent::init();
        $this->touser = EcsWechatUser::find()->select(['openid'])->where(['ect_uid'=>$this->userId,'subscribe'=>1])->scalar();
        if(!$this->touser) {
            throw new Exception("微信用户不存在", 102);
        }
        $this->url   = Yii::$app->params['afgjDomain'].$this->url;
    }
    public static function execute($className,$data) {
        $className = "common\\components\\templatemessage\\{$className}";
        $obj = new $className($data);
        $data  = $obj->pack();
        $sendResult = Yii::$app->wx->messageTemplateSend($data,$obj->touser,$obj->template_id,$obj->url);
        f_d($sendResult);
        $obj->saveMessageNotification($sendResult,$data);
    }

    protected function pack() {
    }
    protected function saveMessageNotification($sendResult,$data) {

        $msgNotification = new MessageNotification();
        $msgNotification->account_id = $this->userId;
        $msgNotification->account_type = 1;
        $msgNotification->content = json_encode($data);
        $msgNotification->send_way = 2;
        $msgNotification->to_url = $this->url;
        $msgNotification->send_time = f_date(time());

        if($sendResult['errcode']==0){
            $msgNotification->send_result = 2;
        }else {
            $msgNotification->send_result=3;
            $msgNotification->fail_reason = $sendResult['errcode'];
        }
        $msgNotification->save(false);
    }
}