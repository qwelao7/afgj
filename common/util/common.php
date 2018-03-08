<?php
use yii\helpers\VarDumper;
use yii\helpers\ArrayHelper;
use common\components\pinyin\Pinyin;
use yii\web\cookie;
/**
 * 封装系统打印函数 dump
 * @param mixed $var 打印变量
 * @param string $is_exit 是否终止程序
 * @param number $depth 打印深度
 * @param string $highlight 高亮
 */
function f_d($var, $is_exit = true, $depth = 10, $highlight = true) {
    header("Content-type: text/html; charset=utf-8");
    VarDumper::dump($var, $depth, $highlight);
    $is_exit && exit();
}

/**
 * 获取配置参数信息
 * @param $key 指定键
 * @param string $default 默认值
 * @return string 指定键对应的值信息
 * @author zend.wang
 * @date  2016-06-24 13:00
 */
function f_params($key,$default="") {
    return ArrayHelper::getValue(\Yii::$app->params,$key,$default);
}
/**
 * @Title: f_date
 * @Description:
 * @param int $unixtime 时间戳
 * @param int or string $mode
 * @return: string 1：年月日时分秒   2：年月日  3： 时分秒
 * @author: zend.wang
 * @date: 2014-12-17 下午5:43:54
 */
function f_date($unixtime, $mode = 1) {
    $modes = [1 => 'Y-m-d H:i:s', 2 => 'Y-m-d', 3 => 'H:i:s',4=>'Ym'];
    return isset($modes[$mode]) ? date($modes[$mode], $unixtime) : date($mode, $unixtime);
}
/**
 * @Title: f_c cache
 * @Description: 封装获取和保存缓存的发那个发
 * @param mixed $key 键值 键值只支持字母、数字、下划线、减号组成的key
 * @param mixed $val 缓存的值
 * @param int $expire 过期时间
 * @return: mixed
 * @author: kai.gao
 * @date: 2014-12-1 下午12:35:28
 */
function f_c($key, $val=null, $expire = 0) {
    $cache = \Yii::$app->cache;
    if ($val === false) $cache->delete($key);
    elseif ($val === null) {
        $data = $cache->get($key);
        //$data === false && Yii::error($key.'缓存不存在');
        return $data;
    }
    else $cache->set($key, $val, $expire);
}

/**
 * 删除指定格式下的所有key
 * @param string 例如 ms:* 表示所有ms:为前缀的key
 * @author zend.wang
 * @return int 返回删除的key总数
 * @date 2015年11月27日 11:06:42
 */
function f_del_c($key) {
    $redis = \Yii::$app->cache;
    $keys = $redis->executeCommand('KEYS', [$key]);
    foreach ($keys as $key) {
        f_c($key, false);
    }
    return count($keys);
}

/**
 * Returns GET parameter with a given name. If name isn't specified, returns an array of all GET parameters.
 *
 * @param string $name the parameter name
 * @param mixed $defaultValue the default parameter value if the parameter does not exist.
 * @return array|mixed
 */
function f_get($name = null, $defaultValue = null) {
    if ($name === null)
        return \Yii::$app->getRequest()->getQueryParams();
    else
        return \Yii::$app->getRequest()->getQueryParam($name, $defaultValue);
}

/**
 * Returns POST parameter with a given name. If name isn't specified, returns an array of all POST parameters.
 *
 * @param string $name the parameter name
 * @param mixed $defaultValue the default parameter value if the parameter does not exist.
 * @return array|mixed
 */
function f_post($name = null, $defaultValue = null) {
    if ($name === null) {
        return \Yii::$app->getRequest()->getBodyParams();
    } else {
        return \Yii::$app->getRequest()->getBodyParam($name, $defaultValue);
    }
}

/**
 * 设置或获取cookie
 * @param $name
 * @param null $value
 * @return mixed
 * @author zend.wang
 * @date  2016-08-25 13:00
 */
function   f_cookie($name,$value = null) {
    if($value) {
        $cookies = Yii::$app->response->cookies;
        $cookies->add(new Cookie(['name'=>$name,'value'=>$value]));
    }else {
        $cookies = Yii::$app->request->cookies;
        return $cookies->getValue($name);
    }
}
/**
 *
 * 字符串截取函数，防止截取中文乱码
 * @param String $sourcestr 截取的字符串对象
 * @param Integer $cutlength 截取的长度
 * @param String $etc 截取完的后缀，默认为'...'
 */
function f_sub($sourcestr, $cutlength = 80, $etc = '...') {
    $returnstr = '';
    $i = 0;
    $n = 0.0;
    $str_length = strlen($sourcestr);
    while ( ($n<$cutlength) and ($i<$str_length) ) {
        $temp_str = substr($sourcestr, $i, 1);
        $ascnum = ord($temp_str);
        if ( $ascnum >= 252) {
            $returnstr = $returnstr . substr($sourcestr, $i, 6);
            $i = $i + 6;
            $n++;
        } elseif ( $ascnum >= 248 ) {
            $returnstr = $returnstr . substr($sourcestr, $i, 5);
            $i = $i + 5;
            $n++;
        } elseif ( $ascnum >= 240 ) {
            $returnstr = $returnstr . substr($sourcestr, $i, 4);
            $i = $i + 4;
            $n++;
        } elseif ( $ascnum >= 224 ) {
            $returnstr = $returnstr . substr($sourcestr, $i, 3);
            $i = $i + 3 ;
            $n++;
        } elseif ( $ascnum >= 192 ) {
            $returnstr = $returnstr . substr($sourcestr, $i, 2);
            $i = $i + 2;
            $n++;
        } elseif ( $ascnum>=65 and $ascnum<=90 and $ascnum!=73) {
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i = $i + 1;
            $n++;
        } elseif ( !(array_search($ascnum, array(37, 38, 64, 109 ,119)) === FALSE) ) {
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i = $i + 1;
            $n++;
        } else {
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i = $i + 1;
            $n = $n + 0.5;
        }
    }

    if ( $i < $str_length ) {
        $returnstr = $returnstr . $etc;
    }

    return $returnstr;
}
function f_checkMobile($str) {
    $pattern = "/^(13|15|17|18)\d{9}$/";
     if (preg_match($pattern,$str)) {
         return true;
     } else {
         return false;
     }
 }

/**
 * 获取字符串首字母
 * @param $str
 * @return string
 * @author zend.wang
 * @date  2016-08-06 13:00
 */
function f_firstLetter($str) {
    $firstLetter = "#";
    $firstWord = mb_substr(ltrim($str), 0, 1, 'utf8');
    $assiiVal = ord($firstWord);
    if($assiiVal > 127) {
        $firstLetter = Pinyin::ChineseToPinyin($firstWord,true);
        if ($firstLetter) {
            $firstLetter =strtoupper(substr($firstLetter,0,1));
        }
    }else if( ($assiiVal>64 && $assiiVal<91) || ($assiiVal>96 &&$assiiVal<123)) {
        $firstLetter = strtoupper($firstWord);
    }
    return [$firstLetter,$assiiVal];
}
/**
 * 获得用户的真实IP地址
 *
 * @access  public
 * @return  string
 */
function f_real_ip()
{
    static $realip = NULL;

    if(IS_DEV_MACHINE) {
        $realip = '0.0.0.0';
    }

    if ($realip !== NULL) {
        return $realip;
    }

    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip) {
                $ip = trim($ip);

                if ($ip != 'unknown') {
                    $realip = $ip;

                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            } else {
                $realip = '0.0.0.0';
            }
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }

    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

    return $realip;
}
function f_sec2Time($time){
    if(is_numeric($time)){
        $value = array(
            "years" => 0, "days" => 0, "hours" => 0,
            "minutes" => 0, "seconds" => 0,
        );
        if($time >= 31556926){
            $value["years"] = floor($time/31556926);
            $time = ($time%31556926);
        }
        if($time >= 86400){
            $value["days"] = floor($time/86400);
            $time = ($time%86400);
        }
        if($time >= 3600){
            $value["hours"] = floor($time/3600);
            $time = ($time%3600);
        }
        if($time >= 60){
            $value["minutes"] = floor($time/60);
            $time = ($time%60);
        }
        $value["seconds"] = floor($time);

        $cnAlias =["years" => '年', "days" => '天', "hours" => '小时',
            "minutes" => '分', "seconds" => '秒'];
        $t='';
        foreach($value as $key=>$val) {
            if($val){
                $t .= $val.$cnAlias[$key];
            }
        }
        ////$t=$value["years"] ."年". $value["days"] ."天"." ". $value["hours"] ."小时". $value["minutes"] ."分".$value["seconds"]."秒";
        return $t;
        //return (array) $value;
    }else{
        return (bool) FALSE;
    }
}
function f_randomFloat($min = 0, $max = 1) {
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
}

function arrayToObject($e){
    if( gettype($e)!='array' ) return;
    foreach($e as $k=>$v){
        if( gettype($v)=='array' || gettype($v)=='object' )
            $e[$k]=(object)arrayToObject($v);
    }
    return (object)$e;
}

function objectToArray($e){
    $e=(array)$e;
    foreach($e as $k=>$v){
        if( gettype($v)=='resource' ) return;
        if( gettype($v)=='object' || gettype($v)=='array' )
            $e[$k]=(array)objectToArray($v);
    }
    return $e;
}