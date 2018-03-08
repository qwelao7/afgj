<?php

namespace common\components;
use common\models\ar\user\Account;
use common\models\ecs\EcsUsers;
use  yii\base\Component;
use yii\db\Query;

require_once(dirname(__FILE__) . '/baichuan/TopSdk.php');

/**
 * 淘宝百川即时通讯
 *
 * @author zend.wang
 */
class BaiChuanIm extends Component {
    
    public $appkey;
    public $secret;
    public $format;
    public $imCom;
    
    public function init() {
        $this->imCom = new \TopClient;
        $this->imCom->appkey = $this->appkey;
        $this->imCom->secretKey = $this->secret;
        $this->imCom->format = $this->format;
    }

    /**
     * 添加用户信息
     * @param $userId
     * @return bool
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function addUser($userId) {
        $result = false;
        $user = EcsUsers::getUser($userId);
        if ($user) {
            $userinfos = new \Userinfos;
            $userinfos->nick = $user['nickname'];
            $userinfos->icon_url = $user['headimgurl'];
            $userinfos->mobile = $user['mobile_phone'];
            $userinfos->userid = "{$user['user_id']}";
            $userinfos->password = 'abc123';
            $userinfos->taobaoid="";
            $userinfos->email= $user['email'];
            $userinfos->remark="";
            $userinfos->extra="{}";
            $userinfos->career="";
            $userinfos->vip="{}";
            $userinfos->address="";
            $userinfos->name=$user['user_name'];
            $userinfos->age="0";
            $userinfos->gender=$user['sex'];
            $userinfos->wechat="";
            $userinfos->qq="";
            $userinfos->weibo="";
            $req = new \OpenimUsersAddRequest;
            $req->setUserinfos(json_encode($userinfos));
            $response = $this->imCom->execute($req);
            if (!empty($response->uid_succ)) {
                $result = true;
            } else {
                $result = $response->fail_msg->string[0];
            }
        }
        return $result;
    }

    /**
     * 查询用户信息
     * @param $userId
     * @return array|bool
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function getUser($userId) {
        $result = false;
        $user = EcsUsers::getUser($userId);
        if ($user) {
            $req = new \OpenimUsersGetRequest;
            $req->setUserids("{$user['user_id']}");
            $response = $this->imCom->execute($req);
            if(!empty($response->userinfos->userinfos)){
                return (array)$response->userinfos->userinfos;
            }
        }
        return $result;
    }
    public function delUser($userId) {
        //$user = EcsUsers::getUser($userId);
        //if ($user) {
            $req = new \OpenimUsersDeleteRequest;
            $req->setUserids("{$userId}");
            $resp = $this->imCom->execute($req);
        //}
    }

    public function getChatLogs($fromUserId,$toUserId,$beginTime,$endTime=0,$count=1) {
        !$endTime && $endTime = time();

        $req = new \OpenimChatlogsGetRequest;

        $user1 = new \OpenImUser();
        $user1->uid = $fromUserId;
        $user1->taobao_account = false;
        $user1->app_key = $this->appkey;
        $req->setUser1(json_encode($user1));
        $user2 = new \OpenImUser();
        $user2->uid = $toUserId;
        $user2->taobao_account = false;
        $user2->app_key = $this->appkey;
        $req->setUser2(json_encode($user2));

        $req->setBegin((string)$beginTime);
        $req->setEnd((string)$endTime);
        $req->setCount((string)$count);
        $response = $this->imCom->execute($req);
        if( !empty($response->result->messages->roaming_message)){
            return $response->result->messages->roaming_message;
        }
        return [];
    }
}
