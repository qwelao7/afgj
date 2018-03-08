<?php
/**
 * 微信回掉
 */
namespace common\components;
use Yii;
use common\models\ar\user\Account;
//
//
//$wechatObj = new wechatCallbackapiTest();
//if (!isset($_GET['echostr'])) {
//    $wechatObj->responseMsg();
//}else{
//    $wechatObj->valid();
//}
/**
 * Description of WechatCallback
 *
 * @author Don.T
 */
class WechatCallback {
    
    //验证消息（第一次时需要用到）
    public function valid() {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }
    //检查签名（第一次时需要用到）
    private function checkSignature() {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = WXTOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }

    //响应消息
    public function  responseMsg() {
        if(!empty($GLOBALS["HTTP_RAW_POST_DATA"])) {
            $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        } else {
            $postStr = file_get_contents('php://input');
        }
        if (!empty($postStr)) {
            $postArr = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            Yii::warning('postArr:'.print_r($postArr, TRUE));
            $RX_TYPE = trim($postArr['MsgType']);
            switch ($RX_TYPE) {
                case "event":
                    $result = $this->receiveEvent($postArr);
                    break;
                case "text":
                case "image":
                case "location":
                case "voice":
                case "video":
                case "shortvideo":
                case "link":
                    //除了事件消息，其他消息都转发到客服
                    $result = $this->transferCustomerService($postArr);
                    break;
                default:
                    $result = "unknown msg type: ".$RX_TYPE;
                    break;
            }
            Yii::warning('回复的内容:'.$result);
            echo $result;
        } else {
            echo "";
            exit;
        }
    }

    //接收事件消息
    private function receiveEvent($postArr) {
        $event = strtolower($postArr['Event']);
        $openid = $postArr['FromUserName'];
        switch($event){
            case 'scan':
                $qrcodeID = $postArr['EventKey'];
                /* 查询用户是否注册过，没有注册先注册 */
                $user = \mobile\models\User::loginOrReg(['openid' => $openid]);
                if($user->id)Account::addFavorByQrcodeID($qrcodeID, $user->id);
                break;
            case 'subscribe':
                $qrcodeID = str_replace('qrscene_', '', $postArr['EventKey']);
                /* 查询用户是否注册过，没有注册先注册 */
                $user = \mobile\models\User::loginOrReg(['openid' => $openid]);
                if($user && $user->id && $qrcodeID) {
                    Account::addFavorByQrcodeID($qrcodeID, $user->id);
                    Account::buildInviteRelation($qrcodeID,$user->id);
                }
                return $this->transmitNews($postArr, [
                    ['Title' => '致绿城东方的业主，最亲爱的家人们',
                     'Description' => '当你下班或外出回家，最希望得到的是什么？ 家人最简单的问候：“回来啦”。 深夜的保安、清晨的保洁，园区开好的花儿…… 门口的拖鞋，锅里的饭菜，温暖的灯光…… 三缺一的牌局，邻居泡好的茶…… 都在等你呢。',
                     'PicUrl' => 'https://mmbiz.qlogo.cn/mmbiz/8QBSNiamH9xVv681ZicjdDWAliaXr8FTP4ial2WaPdjfqHWGtcbepuzLNsV72w0B6iahJENbOIYNJia1y1GrVZ4zpDow/0?wx_fmt=png',
                     'Url' => 'http://mp.weixin.qq.com/s?__biz=MzIwMTc5MDI3Ng==&mid=100000008&idx=1&sn=95fa9ad45be192cec7f3c19f74c41455&scene=0&previewkey=FsIH%2Bzn%2Fz0cIqfSiaAJ1%2BcNS9bJajjJKzz%2F0By7ITJA%3D#wechat_redirect'],
                ]);
                break;
            default:
                break;
        }
    }

    //回复图文消息
    public function transmitNews($postArr, $newsArray) {
        Yii::warning('回复图文消息', print_r($postArr, TRUE));
        $ToUserName = $postArr['FromUserName'];
        $FromUserName = $postArr['ToUserName'];
        $CreateTime = time();
        $ArticleCount = count($newsArray);
        
        $items = '';
        foreach($newsArray as $news){
            $Title = $news['Title'];
            $Description = $news['Description'];
            $PicUrl = $news['PicUrl'];
            $Url = $news['Url'];
            $items .= <<<item
<item>
<Title><![CDATA[{$Title}]]></Title>
<Description><![CDATA[{$Description}]]></Description>
<PicUrl><![CDATA[{$PicUrl}]]></PicUrl>
<Url><![CDATA[{$Url}]]></Url>
</item>
item;
        }
        return <<<XML
<xml>
<ToUserName><![CDATA[{$ToUserName}]]></ToUserName>
<FromUserName><![CDATA[{$FromUserName}]]></FromUserName>
<CreateTime>{$CreateTime}</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>{$ArticleCount}</ArticleCount>
<Articles>
{$items}
</Articles>
</xml>
XML;
    }

    /**
     * 除了事件消息，其他消息都转发到客服
     */
    public function transferCustomerService($postArr){
        $ToUserName = $postArr['FromUserName'];
        $FromUserName = $postArr['ToUserName'];
        $CreateTime = time();
        return <<<XML
<xml>
    <ToUserName><![CDATA[{$ToUserName}]]></ToUserName>
    <FromUserName><![CDATA[{$FromUserName}]]></FromUserName>
    <CreateTime>{$CreateTime}</CreateTime>
    <MsgType><![CDATA[transfer_customer_service]]></MsgType>
</xml>
XML;
    }
}