<?php
namespace common\models;

use Yii;
use yii\base\Model;

/**
 * Description of IaoModel
 *
 * @author Don.T
 */
abstract class IaoModel extends Model{
    
    //定义当前类的公共属性
    private $_md = array();
    //接口URL
    protected $_host;
    //超时时间
    protected $_timeOut;
    //格式类型
    protected $format = 'encrypt';
	private static $_models = array();
    protected $_attributes=array();	
    //定义这个模块下 哪些接口是post接口
    public $postEnum = [];
    
    //获取接口URI地址
	abstract public function getUri();
    
    public function extraFields() {}
    
    public function toArray(array $fields = array(), array $expand = array(), $recursive = true) {}
    
	public static function model($className=__CLASS__) {
		if(isset(self::$_models[$className])) {
			return self::$_models[$className];
        } else {
			$model = self::$_models[$className]=new $className(null);
			$model->attachBehaviors($model->behaviors());
			return $model;
		}
    }
    
    public function __construct($scenario='') {
		$this->setScenario($scenario);
		$this->init();
		$this->attachBehaviors($this->behaviors());
	}
    
	public function init() {
        //根据验证规则获取当前类的属性
        foreach ($this->rules() as $v) {
            if (!is_array($v[0])) {
                $this->_md[] = $v[0];
            } else {
                $this->_md = array_unique(array_merge($this->_md, $v[0]));
            }
        }
	}
    
    public function __get($name) {
		if (isset($this->_attributes[$name])) {
			return $this->_attributes[$name];
        } else if (in_array($name, $this->_md)) {
            //不做处理
            return null;
        } else {
            return parent::__get($name);
        }
    }
    
    public function __set($name, $value) {
		if (property_exists($this,$name)===false) {
            if ($name !== 'attributes') {
                $this->_attributes[$name] = $value;
            } else {
                parent::__set($name,$value);
            }
        }
    }
    
    public function __call($name, $parameters) {
        $this->setScenario($name);
        if (!empty($parameters)) {
            $this->attributes = $parameters[0];
        }
        return $this->request();
    }
    
    /**
     * 获取接口地址
     * @return array
     */
    public function getApiUrl() {
        return $this->_host . $this->getUri() . $this->getScenario();
    }

    /**
     * 获取当前被定义过的参数
     * @return array
     */
    public function getParams() {
        $attributes = $this->_attributes;
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
            $rsp = array('code'=>500, 'mess'=>$errorStr, 'data'=>'');
        } else {
            $url = $this->getApiUrl();
            $params = $this->getParams();
            //数据请求完以后 清空当前数据
            $this->_attributes = array();
            $response = $this->httpsRequest($url, $params);
            if (isset($_GET['debug']) && $_GET['debug']=='apilog') {
                // 记录调试日志
                $logInfo = "\r\n调用地址：".$url.'?'.base64_encode(json_encode($params))."\r\n"
                . "请求参数：".json_encode($params)."\r\n"
                . "请求结果：".$response."\r\n";
                Yii::info($logInfo, 'iao');
            }
            $rsp = \yii\helpers\Json::decode($response);
            if (empty($rsp)) {
                //TODO 这里需要记录异常日志
                $logInfo = "\r\n调用地址：".$url.'?'.base64_encode(json_encode($params))."\r\n"
                . "请求参数：".json_encode($params)."\r\n"
                . "请求结果：".$response." \r\n";
                Yii::info($logInfo, 'iao');
                $rsp = [];
            }
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