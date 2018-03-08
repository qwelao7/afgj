<?php

namespace common\components;

use Yii;
use common\models\WxIao;
use yii\helpers\Json;

/**
 * 微信组件
 *
 * @author Don.T
 */
class WeiXin extends \yii\base\Object {
    
    public $appId;
    public $appsecret;
    public $wxModel;
    //微信token
    public $wxToken;
    //缓存
    public $cache;
    //key前缀
    public $prefix;
    
    public function init() {
        $this->cache = Yii::$app->cache;
        $this->prefix = 'weixin:afgj:';
        $this->wxModel = new WxIao();
        $this->wxToken = $this->getToken();
        $this->wxModel->wxToken = $this->wxToken;
    }
    
    /**
     * 获取token
     */
    public function getToken($isForce=false) {
        if (IS_DEV_MACHINE) {
            $key = $this->prefix.'token';
            $data = $this->cache->get($key);
            if (empty($data) || $isForce) {
                $rsp = $this->wxModel->token([
                    'grant_type' => 'client_credential',
                    'appid' => $this->appId,
                    'secret' => $this->appsecret
                ]);
                if (isset($rsp['access_token'])) {
                    $data = $rsp['access_token'];
                    if($isForce) {
                        $this->wxToken = $data;
                        $this->wxModel->wxToken = $data;
                    }
                    $this->cache->set($key,$data,1800);
                }
            }
            return $data;
        } else {
            $accessToken = Yii::$app->wechat->app->access_token;
            return $accessToken->getToken();
        }
    }
    
    /**
     * 获取凭据
     */
    public function getTicket() {
        $key = 'overtrue.wechat.jsapi_ticket.'.$this->appId;
        $data = $this->cache->get($key);
        if (empty($data)) {
            $data = Yii::$app->wechat->app->js->ticket();
        }
        
        return $data;
    }
    
    /**
     * 发送客服消息
     * @param type $text        消息体
     * @param type $touser      接收者
     * @param type $msgtype     消息类型
     */
    public function messageCustomSend($text, $touser, $msgtype="text") {
        $rsp = $this->wxModel->messageCustomSend([
                'text' => $text,
                'touser' => $touser,
                'msgtype' => $msgtype
            ]);
        return $rsp;
    }
    
    /**
     * 获取用户信息token
     */
    public function getOauthAccessToken($code) {
        $rsp = $this->wxModel->oauthAccessToken([
                'appid' => $this->appId,
                'secret' => $this->appsecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);
        return $rsp;
    }
    
    /**
     * 获取用户信息
     */
    public function getUserInfo($openId) {
        $rsp = $this->wxModel->userinfo([
                'access_token' => $this->wxToken,
                'openid' => $openId,
                'lang' => 'zh_CN'
            ]);
        if (isset($rsp['nickname'])) {
            preg_match_all('/[\x{4e00}-\x{9fff}\w\s-]+/u', $rsp['nickname'], $matches);
            $rsp['nickname'] = join('', $matches[0]);
        }
        return $rsp;
    }
    
    /**
     * 设置行业模版
     * @param type $industryId1 公众号模板消息所属行业编号
     * @param type $industryId2 公众号模板消息所属行业编号
     */
    public function setIndustryTemplate($industryId1, $industryId2) {
        $rsp = $this->wxModel->setIndustryTemplate([
            'industry_id1' => $industryId1,
            'industry_id2' => $industryId2,
        ]);
        return $rsp;
    }
    
    /**
     * 发送模版消息
        $rsp = Yii::$app->wx->messageTemplateSend([
            'first'=>['value'=>"您好，您已成功登录无界后台！"],
            'keyword1'=>['value'=>"tangjun", "color"=>"#173177"],
            'keyword2'=>['value'=>date('Y-m-d H:i:s'), "color"=>"#173177"],
            'remark'=>['value'=>"感谢使用，请注意账号安全。"],
        ], 'o1JqWuD1OYtrzM9dLKxeK51LLJ3Q', 'ne1Zd33hKVCz5zPXxqrZVVFjXZiWOR06bkcxb8yPBsc');
     * @param type $data        消息体
     * @param type $openId      接收者openId
     * @param type $templateId  模版Id
     * @param type $url         跳转的URL
     * @param type $topcolor    颜色
     * @return array
     */
    public function messageTemplateSend($data, $openId, $templateId, $url='http://m.wujie.hk', $topcolor='#FF0000') {
        $rsp = $this->wxModel->tmpNotice([
                'touser' => $openId,
                'template_id' => $templateId,
                'url' => $url,
                'topcolor' => $topcolor,
                'data' => $data
            ]);
        return $rsp;
    }
    
    /**
     * 接入客服
     * @param type $openId
     * @param type $text
     * @param type $kf
     * @return type
     */
    public function kfsessionCreate($openId, $text='新用户接入', $kf='001@otbmall') {
        $rsp = $this->wxModel->kfsessionCreate([
                'openid' => $openId,
                'kf_account' => $kf,
                'text' => $text
            ]);
        return $rsp;
    }
    
    /**
     * 获取用户所有的卡券
     * @param type $openId  用户的openid
     * @param type $cardId  卡券id
     */
    public function getUserCardList($openId, $cardId='') {
        $req = ['openid' => $openId];
        if ($cardId) {
            $req['card_id'] = $cardId;
        }
        $rsp = $this->wxModel->getUserCardList($req);
        return $rsp;
    }
    
    /**
     * 获取卡券详情
     * @param type $cardId  卡券id
     * @return type
     */
    public function getCardInfo($cardId) {
        $rsp = $this->wxModel->getCardInfo([
            'card_id' => $cardId
        ]);
        return $rsp;
    }
    
    /**
     * 获取卡券消费详情
     * @param type $code            用户卡券唯一标识
     * @param type $cardId          卡券id
     * @param type $checkConsume    是否校验code核销状态，填入true和false时的code异常状态返回数据不同。
     * @return type
     */
    public function getCardCodeInfo($code, $cardId='', $checkConsume=true) {
        $req = ['code' => $code, 'check_consume' => $checkConsume];
        if ($cardId) {
            $req['card_id'] = $cardId;
        }
        $rsp = $this->wxModel->getCardCodeInfo($req);
        return $rsp;
    }
    
    /**
     * 销毁卡券
     * @param type $code        用户卡券唯一标识
     * @param type $cardId      卡券id
     * @return type
     */
    public function consumeCard($code, $cardId='') {
        $req = ['code' => $code];
        if ($cardId) {
            $req['card_id'] = $cardId;
        }
        $rsp = $this->wxModel->consumeCard($req);
        return $rsp;
    }
    
    /**
     * 获取微信卡券Sign
     * @param type $card
     * @return boolean
     */
    public function getWxCardSign($card){
        sort($card,SORT_STRING);
        $sign = sha1(implode($card));
        if (!$sign) {
            return false;
        } else {
            return $sign;
        }
    }

    /**
     * 网页授权接口 获取用户基础信息
     * @param $token
     * @param $openid
     * @return mixed
     */
    public function getSnsUserInfo($token, $openid) {
        $rsp = $this->wxModel->snsUserInfo([
            'access_token' => $token,
            'openid' => $openid,
            'lang' => 'zh_CN',
        ]);
        if (isset($rsp['nickname'])) {
            preg_match_all('/[\x{4e00}-\x{9fff}\w\s-]+/u', $rsp['nickname'], $matches);
            $rsp['nickname'] = join('', $matches[0]);
        }
        return $rsp;
    }

    
    /**
     * 生成二维码
     * @param integer|string $sceneIdOrStr
     * @param string $actionName
     * @param integer $expireSeconds
     */
    public function genQrcode($sceneIdOrStr, $actionName = 'QR_LIMIT_SCENE', $expireSeconds=NULL){
        $data['action_name'] = $actionName;
        if(ctype_digit((string)$sceneIdOrStr)){
            $data['action_info']['scene']['scene_id'] = (int)$sceneIdOrStr;
        }else{
            $data['action_info']['scene']['scene_str'] = (string)$sceneIdOrStr;
        }
        if(!is_null($expireSeconds))$data['expire_seconds'] = $expireSeconds;
        return $this->wxModel->genQrcode($data);
    }

    /**
     * 发送模板消息
     * @param $touser 接收者openId
     * @param $scenes 场景
     * @param $url 跳转的URL
     * @param $data 消息体
     * @param $topcolor 颜色如#FF0000
     * @return boolean ture:发送成功 false:发送失败
     * @author zend.wang
     * @date  2016-06-24 13:00
     */
    public function sendTemplateMessage($touser, $scenes, $data, $url, $topcolor="#6AA84F"){
        $result = $this->wxModel->tmpNotice([
            'touser' => $touser, 'template_id' =>f_params(['wxTmplMsg',$scenes,'template_id']),
            'url' => $url, 'data' => $data, 'topcolor' => $topcolor]);
        return $result;
    }
}