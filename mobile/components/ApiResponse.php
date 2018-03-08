<?php

namespace mobile\components;
use yii\base\Component;

class ApiResponse extends  Component {

    //$result = ['ret'=>200,'error'=>'','data'=>['code'=>0,'msg'=>'']];
    public $ret;
    public $data;
    public $error;
    public function init() {
        $this->ret = 200;
        $this->error = '';
        $this->data = null;
    }
}
