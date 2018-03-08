<?php

namespace common\components;

use Yii;
use yii\db\Query;
use yii\db\Exception;
use common\models\ecs\EcsFeedback;
/**
 * 新版本订单支付方式
 * 微信支付类V2 与 ectouch配合使用
 */
class WxpayV2 extends \yii\base\Object
{

    var $parameters; // cft 参数
    var $payment; // 配置信息

    /**
     * 生成支付代码
     *
     * @param array $order
     *            订单信息
     * @param array $payment
     *            支付方式信息
     */
    function get_code($order)
    {
        // 配置参数
        $this->payment = (new Query())->select('*')
            ->from('ecs_payment')
            ->where(['pay_id'=>f_params(['wxpay','payment_id']), 'enabled'=>1])->one();


        if ($this->payment) {
            $config_list = unserialize($this->payment['pay_config']);

            foreach ($config_list AS $config)
            {
                $this->payment[$config['name']] = $config['value'];
            }
        }

        // 网页授权获取用户openid
        $openid = $GLOBALS['_SESSION']['openId'];
        if (!isset($openid) || empty($openid)) {
            return false;
        }
        
        // 根目录url
        $this->setParameter("openid", $openid); // 用户openid
        $this->setParameter("body", $order['order_sn']); // 商品详情
        $this->setParameter("out_trade_no", $order['order_sn'] . 'A' . ($order['order_amount'] * 100) . 'B' . $order['log_id']); // 商户订单号
        $this->setParameter("total_fee", $order['order_amount'] * 100); // 总金额
        $this->setParameter("notify_url",f_params(['wxpay','notify_url'])); // 通知地址
        $this->setParameter("trade_type", "JSAPI"); // 交易类型
//        if($order['apply'] == 1){
//            $this->setParameter("attach", "drp");
//        }
        $prepay_id = $this->getPrepayId();
        $jsApiParameters = $this->getParameters($prepay_id);

        $result['jsApi'] = $jsApiParameters;
        $result['url'] = f_params(['wxpay','return_url']);
        return $result;
    }

    /**
     * 生成支付代码
     *
     * @param array $apply
     *            活动信息
     * @param array $payment
     *            支付方式信息
     */
    function get_apply_code($apply)
    {
        // 配置参数
        $this->payment = (new Query())->select('*')
            ->from('ecs_payment')
            ->where(['pay_id'=>f_params(['wxpay','payment_id']), 'enabled'=>1])->one();


        if ($this->payment) {
            $config_list = unserialize($this->payment['pay_config']);

            foreach ($config_list AS $config)
            {
                $this->payment[$config['name']] = $config['value'];
            }
        }

        // 网页授权获取用户openid
        $openid = $GLOBALS['_SESSION']['openId'];
        if (!isset($openid) || empty($openid)) {
            return false;
        }

        // 根目录url
        $this->setParameter("openid", $openid); // 用户openid
        $this->setParameter("body", $apply['title']); // 商品详情
        $this->setParameter("out_trade_no", 'events' . 'A' . ($apply['cash_fee'] * 100) . 'B' . $apply['bill_id']); // 商户订单号
        $this->setParameter("total_fee", $apply['cash_fee'] * 100); // 总金额
        $this->setParameter("notify_url",f_params(['wxpay','notify_url'])); // 通知地址
        $this->setParameter("trade_type", "JSAPI"); // 交易类型
//        if($order['apply'] == 1){
//            $this->setParameter("attach", "drp");
//        }
        $prepay_id = $this->getPrepayId();
        $jsApiParameters = $this->getParameters($prepay_id);

        $result['jsApi'] = $jsApiParameters;
        $result['url'] = '';
        return $result;
    }

    /**
     * 公益捐款
     * @param $benefit
     * @return bool
     */
    function get_benefit_code($benefit)
    {
        // 配置参数
        $this->payment = (new Query())->select('*')
            ->from('ecs_payment')
            ->where(['pay_id'=>f_params(['wxpay','payment_id']), 'enabled'=>1])->one();


        if ($this->payment) {
            $config_list = unserialize($this->payment['pay_config']);

            foreach ($config_list AS $config)
            {
                $this->payment[$config['name']] = $config['value'];
            }
        }

        // 网页授权获取用户openid
        $openid = $GLOBALS['_SESSION']['openId'];
        if (!isset($openid) || empty($openid)) {
            return false;
        }

        // 根目录url
        $this->setParameter("openid", $openid); // 用户openid
        $this->setParameter("body", $benefit['title']); // 商品详情
        $this->setParameter("out_trade_no", 'benefit' . 'A' . ($benefit['cash_fee'] * 100) . 'B' . $benefit['bill_id']); // 商户订单号
        $this->setParameter("total_fee", $benefit['cash_fee'] * 100); // 总金额
        $this->setParameter("notify_url",f_params(['wxpay','notify_url'])); // 通知地址
        $this->setParameter("trade_type", "JSAPI"); // 交易类型
//        if($order['apply'] == 1){
//            $this->setParameter("attach", "drp");
//        }
        $prepay_id = $this->getPrepayId();
        $jsApiParameters = $this->getParameters($prepay_id);

        $result['jsApi'] = $jsApiParameters;
        $result['url'] = '';
        return $result;
    }

    /**
     * 响应操作
     */
    function callback($data)
    {
        if ($_GET['status'] == 1) {
            return true;
        } else {
            return false;
        }
    }
    function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * 作用：产生随机字符串，不长于32位
     */
    public function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i ++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 作用：设置请求参数
     */
    function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 作用：生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);

        $buff = "";
        foreach ($Parameters as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        $String='';
        if (strlen($buff) > 0) {
            $String = substr($buff, 0, strlen($buff) - 1);
        }
        // echo '【string1】'.$String.'</br>';
        // 签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->payment['wxpay_key'];
        // echo "【string2】".$String."</br>";
        // 签名步骤三：MD5加密
        $String = md5($String);
        // echo "【string3】 ".$String."</br>";
        // 签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        // echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     * 作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml, $url, $second = 30)
    {
        // 初始化curl
        $ch = curl_init();
        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        // 运行curl
        $data = curl_exec($ch);
        // 返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     * 获取prepay_id
     */
    function getPrepayId()
    {
        // 设置接口链接
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        try {
            // 检测必填参数
            if ($this->parameters["out_trade_no"] == null) {
                throw new Exception("缺少统一支付接口必填参数out_trade_no！" . "<br>");
            } elseif ($this->parameters["body"] == null) {
                throw new Exception("缺少统一支付接口必填参数body！" . "<br>");
            } elseif ($this->parameters["total_fee"] == null) {
                throw new Exception("缺少统一支付接口必填参数total_fee！" . "<br>");
            } elseif ($this->parameters["notify_url"] == null) {
                throw new Exception("缺少统一支付接口必填参数notify_url！" . "<br>");
            } elseif ($this->parameters["trade_type"] == null) {
                throw new Exception("缺少统一支付接口必填参数trade_type！" . "<br>");
            } elseif ($this->parameters["trade_type"] == "JSAPI" && $this->parameters["openid"] == NULL) {
                throw new Exception("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！" . "<br>");
            }
            $this->parameters["appid"] = $this->payment['wxpay_appid']; // 公众账号ID
            $this->parameters["mch_id"] = $this->payment['wxpay_mchid']; // 商户号
            $this->parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR']; // 终端ip
            $this->parameters["nonce_str"] = $this->createNoncestr(); // 随机字符串
            $this->parameters["sign"] = $this->getSign($this->parameters); // 签名
            $xml = "<xml>";
            foreach ($this->parameters as $key => $val) {
                if (is_numeric($val)) {
                    $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
                } else {
                    $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
                }
            }
            $xml .= "</xml>";
        } catch (Exception $e) {
            die($e->getMessage());
        }

        // $response = $this->postXmlCurl($xml, $url, 30);
        $response = Http::curlPost($url, $xml, 30);
        $result = json_decode(json_encode(simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $prepay_id = $result["prepay_id"];
        return $prepay_id;
    }

    /**
     * 作用：设置jsapi的参数
     */
    public function getParameters($prepay_id)
    {
        $jsApiObj["appId"] = $this->payment['wxpay_appid'];
        $timeStamp = time();
        $jsApiObj["timeStamp"] = (string)$timeStamp;
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $jsApiObj["package"] = "prepay_id=".$prepay_id;
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->getSign($jsApiObj);
        $this->parameters = json_encode($jsApiObj);

        return $this->parameters;
    }
}