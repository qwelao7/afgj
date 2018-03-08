<?php

namespace common\components;

use common\models\ar\system\SysArea;
use Yii;

/**
 * 工具类
 *
 * @author Don.T
 */
class Util extends \yii\base\Object {

    /**
     * 转cdn地址
     */
    public function toCdnPath($source='') {
        $urlPrefix = '/statics/';
        if (Yii::$app->params['cdn']['enable']) {
            $urlPrefix = Yii::getAlias(Yii::$app->params['cdn']['host']).'statics/';
        }
        return $urlPrefix.$source;
    }


    /**
     * 获取远程网页内容
     * @param type $url
     */
    public function getUrlHtml($url) {
        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * 截取字符串
     * @param type $str
     * @param type $len
     * @param type $dot
     * @return string
     */
    public function substr($str, $len = 160, $dot = true) {
        $i = 0;
        $l = 0;
        $c = 0;
        $a = array();
        while ($l < $len) {
            $t = substr($str, $i, 1);
            if (ord($t) >= 224) {
                $c = 3;
                $t = substr($str, $i, $c);
                $l += 2;
            } elseif (ord($t) >= 192) {
                $c = 2;
                $t = substr($str, $i, $c);
                $l += 2;
            } else {
                $c = 1;
                $l++;
            }
            $i += $c;
            if ($l > $len) break;
            $a[] = $t;
        }
        $re = implode('', $a);
        if (substr($str, $i, 1) !== false) {
            array_pop($a);
            ($c == 1) && array_pop($a);
            $re = implode('', $a);
            $dot && $re .= '...';
        }
        return $re;
    }

    /**
     * 获取图片地址
     * @param $url
     * @param string $bucket
     * @return string
     */
    public function getImgUrl($url, $bucket='upload_public', $size='') {
        return Yii::$app->$bucket->domain.$url.($size?'-'.$size:'');
    }

    /**
     * 获取微信配置 过期保留 不维护
     */
    public function getWxConfig() {
        $timestamp = time();
        $nonceStr = Yii::$app->security->generateRandomString();
        $signature = sha1('jsapi_ticket='.Yii::$app->wx->getTicket().'&noncestr='.$nonceStr.'&timestamp='.$timestamp.'&url=http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        return \yii\helpers\Json::encode([
            'appId' => Yii::$app->wx->appId,
            'timestamp' => $timestamp,
            'nonceStr' => $nonceStr,
            'signature' => $signature,
        ]);
    }
    /**
     * 获取微信配置
     */
    public function getWeixinConfig() {
        $timestamp = time();
        $nonceStr = Yii::$app->security->generateRandomString();
        $signature = sha1('jsapi_ticket='.Yii::$app->wx->getTicket().'&noncestr='.$nonceStr.'&timestamp='.$timestamp.'&url=http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        return ['appId' => Yii::$app->wx->appId, 'timestamp' => $timestamp, 'nonceStr' => $nonceStr, 'signature' => $signature];
    }
    /**
     * 功能有点类似于原生的\range()函数，只是这个方法返回的是一组时间
     * @param integer   日期的个数
     * @param string    $format see http://php.net/manual/zh/function.date.php
     * @param string    $begin 开始日期，默认当前时间
     * @param string    $step  步数 see http://php.net/manual/zh/dateinterval.construct.php
     * @param string    $keys  返回的数组的键名，默认从0开始的索引键名，若传递NULL，则键名就是值
     */
    public static function dateRange($num=30, $format='Y-m-d', $begin=NULL, $step='1D', $keys=FALSE){
        if(is_null($begin))$begin = static::now();
        $dates[] = $begin;
        $num-=1;
        for($i=1;$i<=$num;$i++){
            $dates[] = static::dateAdd('P'.$step, $dates[$i-1], TRUE);
        }
        foreach($dates as &$date){
            $date = static::date($date, $format);
        }
        if(is_null($keys))$dates = array_combine($dates, $dates);//如果键名为空，把值当作键名
        return $dates;
    }

    public static $weekDayzh_CN = [ 1=>'一', 2=>'二', 3=>'三', 4=>'四', 5=>'五', 6=>'六', 7=>'天' ];
    /**
     * 与原生的date函数不一样的是，这个方法会处理中文的星期
     * @param string    $begin 开始日期，默认当前时间
     * @param string $format see http://php.net/manual/zh/function.date.php
     *  另外，D会被替换成周几，l会被替换成星期几
     */
    public static function date($date=NULL, $format='Y-m-d'){
        if(is_null($date))$date = static::now();
        $timestamp = strtotime($date);
        if(FALSE !== strpos($format, 'D') || FALSE !== strpos($format, 'l')){//处理星期
            $weekDay = static::$weekDayzh_CN[date('N', $timestamp)];
            $format = str_replace(['D', 'l'], ['周'.$weekDay, '星期'.$weekDay], $format);
        }
        return date($format, $timestamp);
    }

    /**
     * 给时间加上一个interval
     * @param string $intval    see http://php.net/manual/zh/dateinterval.construct.php
     * @param string $from      yyyy-mm-dd HH:ii:ss
     * @param string $firstDay  当天是否算作一天
     * @param string $format    see http://php.net/manual/zh/function.date.php
     * @return \DateTime
     */
    public static function dateAdd($intval, $from=NULL, $firstDay=TRUE,$format='Y-m-d H:i:s'){
        if(is_null($from))$from = static::now();//默认是现在
        $from = new \DateTime($from);
        $from->add(new \DateInterval($intval));
        if(!$firstDay)$from->sub(new \DateInterval('P1D'));
        return FALSE === $format ? $from : static::date($from->date, $format) ;
    }

    /**
     * 今天的日期
     * @param string $format
     */
    public static function today($format='Y-m-d'){
        static $today;
        if(is_null($today)){
            $today = date($format);
        }
        return $today;
    }

    /**
     * 当前时间(只包含时间部分)
     * @param string $static
     * @param string $format
     */
    public static function nowTime($static=TRUE, $format='H:i:s'){
        if(!$static){
            return date($format);
        }
        static $nowTime;
        if(is_null($nowTime)){
            $nowTime = date($format);
        }
        return $nowTime;
    }

    /**
     * 当前时间(包含日期和时间)
     * @param string $static
     * @param string $format
     */
    public static function now($static=TRUE, $format='Y-m-d H:i:s'){
        if(!$static){
            return date($format);
        }
        static $now;
        if(is_null($now)){
            $now = date($format);
        }
        return $now;
    }

    /**
     * Advanced explode，相比于原生的explode，可以去重去空
     * @param unknown $arr
     */

    public static function advExplode($str, $config = array()){
        static $defaultConfig = array(
            'separate'      => ',',         //分隔符
            'trim_charlist' => ',',         //需要去除的字符列表
            'trim_side'     => 'b',      //l 左边，r 右边， b(both) 左右两边
            'unique'        => TRUE,        //是否去重
            'sort_flags'    => SORT_REGULAR,//去重时的sort_flags
            'no_empty'      => TRUE,        //是否去空
        );
        $config = array_merge($defaultConfig, $config);
        if(is_string($str)){
            static $trimFuns = array('l' => 'ltrim', 'r' => 'rtrim', 'b' => 'trim');
            if(!$config['no_empty']){//不去空的话，从去除列表里面去掉分隔符
                $config['trim_charlist'] = str_replace($config['separate'], '', $config['trim_charlist']);
            }
            $str = explode($config['separate'], $trimFuns[$config['trim_side']]($str, $config['trim_charlist']));
        }
        if($config['unique']){
            $str = array_unique($str, $config['sort_flags']);
        }
        if($config['no_empty']){
            $str = array_filter($str);
        }
        return $str;
    }


    /**
     * 从一个数组中提取出一些字段，类似于array_column，不同的地方是支持提取多个字段，并且可以保持原来的键名
     * @param array $array
     * @param string $k 主键
     * @param string 需要提取的数组，英文逗号分隔
     * @return array 被提取的元素组成的数组
     */
    public static function kv($array, $v, $k='numeric' ){
        $v = static::advExplode($v);
        $return = array();
        if( count($v) == 1 ){
            $v = $v[0];
            foreach( $array as $key=>$val ){
                if( $k == 'numeric' ){//索引数组
                    $return[] = $val[$v];
                }elseif( $k == 'curKey' ){//继续使用当前的key
                    $return[$key] = $val[$v];
                }else{
                    $return[$val[$k]] = $val[$v];
                }
            }
        }else{
            foreach( $array as $val ){
                foreach( $v as $val2 ){
                    $return[$val[$k]][$val2] = $val[$val2];
                }
            }
        }
        return $return;
    }


    /**
     * 改变数组的键名
     * @param array 需要改变键名的数组
     * @param string $k 键名的元素的值
     * @return multitype:unknown
     */
    public static function chgKey($arr,$k){
        if(empty($arr)) return $arr;
        $return_arr = array();
        foreach( $arr as &$v ){
            $return_arr[$v[$k]] = $v;
        }
        return $return_arr;
    }


    /**
     * 数组排序，可按多个字段排序，类似于Sql语句的order by 子句
     */
    public static function order(){
        $arguments = func_get_args();
        $arrayNeedOrder = array_pop($arguments);//需要排序的数组
        //填补参数，默认是数字倒序(array_multisort函数默认是通常降序)
        $realArgs = array();
        $i = 0;//$arguments的索引，用于访问下一个参数
        $index = 0;//$realArgs数组的索引
        foreach( $arguments as &$v ){
            if( is_string($v) ){//是字符串就创建一个新元素
                $realArgs[$index] = array($v);
            }else{				//是数字就将它放入上一个参数所在的数组
                $realArgs[$index][] = $v;
            }
            if( is_string(@$arguments[$i+1]) || $i+1 == count($arguments)){//查看下一个参数的类型决定$index是否自增(这里最后一个参数的下一个是取不到的，所以加上||后面的代码判断是不是到了最后一个参数)
                if( !isset($realArgs[$index][1]) ){
                    $realArgs[$index][1] = 1;//1 for SORT_NUMERIC,3 for SORT_DESC
                }
                if( !isset($realArgs[$index][2]) ){
                    $realArgs[$index][2] = $realArgs[$index][1] >= 3 ? 1 : 3 ;//前面若已经指定了顺序标志，就补上类型标志，反之亦然
                }
                $index++;
            }
            $i++;
        }
        $code = 'array_multisort(';
        foreach( $realArgs as &$v ){
            $$v[0] = static::kv($arrayNeedOrder, $v[0]);//可变变量
            $code .= '$'.$v[0].','.$v[1].','.$v[2].',';
        }
        $code .= '$arrayNeedOrder);';
        eval($code);
        return $arrayNeedOrder;
    }

    /**
     * 将数组按某个元素的值分组
     * @param string $by
     * @param array 需要分组的数组
     * @param string $key 主键
     */
    public static function groupBy($by, $arr, $key=null){
        if(empty($arr)) return $arr;
        $return = array();
        if(is_null($key)){
            foreach($arr as &$v)	$return[$v[$by]][] = $v;
        }else{
            foreach($arr as &$v)	$return[$v[$by]][$v[$key]] = $v;
        }
        return $return;
    }

    /* 将数组拆分分组 */
    public static function noGroupBy($arr) {
        if(empty($arr)) return $arr;
        $return = array();
        foreach($arr as $key=>$value) {
            foreach($value as $k=>$v) {
                array_push($return, $v);
            }
        };
        return $return;
    }

    /**
     * 字符串截取，支持多字节字符
     * @param unknown $str
     * @param unknown $length  切的长度
     * @param string $encoding  字符串编码
     * @param string $tail      后缀
     * @return string
     */
    public static function cutStr($str, $length=10, $encoding='UTF-8', $tail='...'){
        $strLen = mb_strlen($str, $encoding);
        $cuted = mb_substr($str, 0, $length, $encoding);
        $strLen > $length && $cuted .= $tail;
        return $cuted;
    }

    public static function expireTime($month){
        $month +=1;
        $year = strtotime(date('Y-m', time()));
        $data = date('Y-m',strtotime('+ '.$month.' month',$year));
        $time = date('Y-m-d H:i:s', strtotime($data)-1);
        return $time;
    }

    /**
     * 处理图集
     * @param string $key
     * @param array $data
     */
    public static function handleGallerys($key, $data=NULL){
        if(!$key)return '';
        if(is_null($data))$data = $_POST;
        if(!$data[$key]['name'])return '';

        $return = [];
        foreach($data[$key]['name'] as $index=>$picsName){
            $return[$picsName] = (array)$data[$key]['pic'][$index];
        }
        return (string)json_encode($return);
    }

    /* *
     *  数字转为汉字(限制为2位)
     *  @param integer $num
     */
    public  static function numTransform($num) {
        $arr = array('零', '一','二','三','四','五','六','七','八','九');
        if(strlen($num) == 1) {
            $result = $arr[$num];
        }else {
            if($num == 10) {
                $result = '十';
            }else {
                if($num < 100) {
                    $result = $arr[substr($num, 0,1)]."十".$arr[substr($num,1,1)];
                }
            }
        }
        return $result;
    }

    //与当前时间相距几天
    public static function formatTime($the_time) {
        $now_time = date("Y-m-d H:i:s",time()+8*60*60);
        $now_time = strtotime($now_time);
        $show_time = strtotime($the_time);
        $dur = $now_time - $show_time;
        if($dur < 0) {
            $the_time = date('m-d H:i', strtotime($the_time));
            return $the_time;
        } else {
            if($dur < 86400) {
                return date('H:i', strtotime($the_time));
            }else {
                if($dur < 604800) {
                    return floor($dur/86400).'天前';
                }else {
                    if($dur < 2592000) {
                        return floor($dur/604800).'周前';
                    }else {
                        $the_time = date('Y-m', $show_time);
                        return $the_time;
                    }
                }
            }
        }
    }

}
