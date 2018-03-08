<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace common\components;
require_once(dirname(__FILE__) . '/sms/TopSdk.php');

/**
 * Description of Sms
 *
 * @author Don.T
 */
class Sms extends \yii\base\Component {
    
    public $appkey;
    public $secret;
    public $smsCom;
    
    protected $req;

    /**
     * 场景
     */
    public $scene = [
        'bindMobile' => [
            'templateCode' => 'SMS_9985194',
            'ttsCode' => 'TTS_6340564',
            'showNum' => '051482043260'
        ],
        'actInform' => [
            'templateCode' => 'SMS_27045001'
        ],
        'voteInform' => [
            'templateCode' => 'SMS_27035002'
        ],
		'springTask' => [
            'templateCode' => 'SMS_40640003'
        ],
		'customerInvite' => [
			'templateCode' => 'SMS_53155035'
		],
    ];
    
    public function init() {
        $this->smsCom = new \TopClient;
        $this->smsCom->appkey = $this->appkey;
        $this->smsCom->secretKey = $this->secret;
    }
    
    /**
     * 发送
     * @param string|array $phones
     * @param string $scene
     * @param array $params
     */
    public function send($phones, $scene, $params=[],$sign = '回来啦社区') {
        //init
        $this->req = new \AlibabaAliqinFcSmsNumSendRequest;
        $this->req->setExtend("123456");
        $this->req->setSmsType("normal");
        $this->req->setSmsFreeSignName($sign);

        if(!$phones)return FALSE;
        if(!$scene || empty($this->scene[$scene]))return FALSE;

        $params = $this->parseParams($params);
        $this->req->setSmsParam($params);
        $this->req->setSmsTemplateCode($this->scene[$scene]['templateCode']);
        $phones = $this->parsePhone($phones);
        foreach($phones as $phone){
            $this->req->setRecNum($phone);
            $rsps[] = (array)$this->smsCom->execute($this->req);
        }
//        foreach($rsps as $rsp){
//            if(!empty($rsp['result'])){
//                $result = simplexml_load_string($rsp['result']);
//                if('true' == strtolower($rsp['result']['success']))return TRUE;//有一个发送成功即返回成功
//            }
//        }
//        return FALSE;//全部发送失败才返回失败
        return true;
    }

    /**
     * 发送语音
     * @param string|array $phones
     * @param string $scene
     * @param array $params
     */
    public function sendYuYin($phones, $scene, $params=[]) {
        //init
        $this->req = new \AlibabaAliqinFcTtsNumSinglecallRequest;
        $this->req->setExtend("123456");

        if(!$phones)return FALSE;
        if(!$scene || empty($this->scene[$scene]))return FALSE;

        $params = $this->parseParams($params);
        $this->req->setTtsParam($params);
        $this->req->setTtsCode($this->scene[$scene]['ttsCode']);
        $this->req->setCalledShowNum($this->scene[$scene]['showNum']);
        $phones = $this->parsePhone($phones);
        foreach($phones as $phone){
            $this->req->setCalledNum($phone);
            $rsps[] = (array)$this->smsCom->execute($this->req);
        }
//        foreach($rsps as &$rsp){
//            $rsp['result'] = (array)$rsp['result'];
//            if('true' == strtolower($rsp['result']['success']))return TRUE;//有一个发送成功即返回成功
//        }
//        return FALSE;//全部发送失败才返回失败
        return true;
    }

    /**
     * 解析phone
     * @param string|array $phone
     */
    public function parsePhone($phone){
        if(is_string($phone))$phone = Util::advExplode($phone);
        $phone = array_chunk($phone, 200);
        foreach($phone as &$phoneChunk){
            $phoneChunk = join(',', $phoneChunk);
        }
        return $phone;
    }

    /**
     * 解析params
     * @param array $params
     * @return string
     */
    public function parseParams($params){
        $params = array_map('strval', $params);//阿里大鱼只接受字符串，不接受整形
        return json_encode($params);
    }
}
