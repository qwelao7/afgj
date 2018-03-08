<?php

namespace common\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * 微信接口模型
 *
 * @author Don.T
 */
class WxIao extends \common\models\IaoModel {
    
    protected $format = 'json';
    public $wxToken;
    //定义这个模块下 哪些接口是post接口
    public $postEnum = ['messageCustomSend', 'kfsessionCreate', 'genQrcode', 'tmpNotice'];
    
    public function init() {
        parent::init();
        $this->_host = Yii::$app->params['wx']['host'];
    }
    
    public function scenarios() {
        return ArrayHelper::merge(parent::scenarios(), [
            //获取token
            'token' => ['grant_type', 'appid', 'secret'],
            //获取凭证
            'getTicket' => ['type', 'access_token'],
            //获取用户信息token
            'oauthAccessToken' => ['appid', 'secret', 'code', 'grant_type'],
            //获取用户信息      ?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
            'userinfo' => ['access_token', 'openid', 'lang'],
            //发送客服消息
            'messageCustomSend' => ['touser', 'msgtype', 'text', 'image', 'voice', 'video', 'music', 'news', 'customservice'], 
            //生成二维码
            'genQrcode' => ['expire_seconds','action_name','action_info'],
            //发送模板通知
            'tmpNotice' => ['touser', 'template_id', 'url', 'data']
        ]);
    }
    
    public function rules() {
		return [
            //获取token
            [['grant_type', 'appid', 'secret'], 'required', 'on'=>'token'],
            //获取凭证
            [['type', 'access_token'], 'required', 'on'=>'getTicket'],
			//发送客服消息
			[['touser', 'msgtype'], 'required', 'on'=>'messageCustomSend'],
			//获取用户信息token
			[['appid', 'secret', 'code', 'grant_type'], 'required', 'on'=>'oauthAccessToken'],
            //获取用户信息
			[['access_token', 'openid'], 'required', 'on'=>'userinfo'],
            //接入客服
			[['openid', 'kf_account', 'text'], 'required', 'on'=>'kfsessionCreate'],
            //授权接口 获取用户基础信息 /sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
            [['access_token', 'openid', 'lang'], 'required', 'on'=>'snsUserInfo'],
		    //发送模板通知
		    [['touser', 'template_id', 'data'], 'required', 'on'=>'tmpNotice']
        ];
    }

    public static function model($className=__CLASS__) {
        return parent::model($className);
    }

    public function getUri() {
        $scenario = $this->getScenario();
        $uri = '';
        switch ($scenario) {
            //获取凭证
            case 'getTicket':
                $uri = 'cgi-bin/ticket/getticket';
                break;
            //发送客服消息
            case 'messageCustomSend':
                $uri = 'cgi-bin/message/custom/send?access_token=' . $this->wxToken;
                break;
            //获取用户信息token
            case 'oauthAccessToken':
                $uri = 'sns/oauth2/access_token';
                break;
            //获取用户信息
            case 'userinfo':
                $uri = 'cgi-bin/user/info';
                break;
            //接入客服
            case 'kfsessionCreate':
                $uri = 'customservice/kfsession/create?access_token=' . $this->wxToken;
                break;
            //授权接口 获取用户基础信息 /sns/userinfo?access_token=ACCESS_TOKEN&openid=OPENID&lang=zh_CN
            case 'snsUserInfo':
                $uri = 'sns/userinfo';
                break;
            case 'genQrcode':
                $uri = 'cgi-bin/qrcode/create?access_token='.$this->wxToken;
                break;
            //发送模板通知
            case 'tmpNotice':
                $uri = 'cgi-bin/message/template/send?access_token='.$this->wxToken;
                break;
            default:
                $uri = 'cgi-bin/' . $scenario;
                break;
        }
        return $uri;
    }
    
    /**
     * 获取接口地址
     * @return array
     */
    public function getApiUrl() {
        return $this->_host . $this->getUri();
    }
    
    public function getParams() {
        $attributes = $this->_attributes;
        if (in_array($this->getScenario(), $this->postEnum) && $this->format=='json') {
            $attributes = \yii\helpers\Json::encode($attributes);
        }
        return $attributes;
    }
    
    /**
     * 发起请求
     */
    public function request() {
        if (!$this->validate()) {
            //参数验证失败返回错误
            $errorStr = '';
            foreach ($this->getErrors() as $err) {
                $errorStr .= $err[0] . ' ';
            }
            $rsp = array('code'=>500, 'msg'=>$errorStr, 'data'=>'');
        } else {
            $url = $this->getApiUrl();
            $params = $this->getParams();
            //数据请求完以后 清空当前数据
            $this->_attributes = [];
            $response = $this->httpsRequest($url, $params);
            $rsp = \yii\helpers\Json::decode($response);
        }
        return $rsp;
    }
    
    protected function httpsRequest($url, $data=null) {
        //非post请求时 将参数添加到url后面
        if (!in_array($this->getScenario(), $this->postEnum)) {
            if (!empty($data)) {
                $str = '';
                foreach ($data as $k=>$v) {
                    if (!empty($str)) {
                        $str .= '&';
                    }
                    $str .= $k . '=' . $v;
                }
                if (strpos($url, '?')===false) {
                    $url .= '?' . $str;
                } else {
                    $url .= '&' . $str;
                }
                $data = null;
            }
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }
}