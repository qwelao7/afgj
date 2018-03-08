<?php

namespace common\components;

use Yii;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use common\models\ar\file\FFile;

/**
 * 对接qiniu的cdn服务
 *
 * @author Don.T
 */
class Upload extends \yii\base\Object {
    //上传的空间名称
    public $bucket = 'afgj-pub';
    public $qiniuAk = '';
    public $qiniuSk = '';
    public $domain = '';
    //上传token
    public $token;
    //错误信息
    private $error = null;
    
    public function init() {
        parent::init();
        $auth = new Auth($this->qiniuAk, $this->qiniuSk);
        $this->token = $auth->uploadToken($this->bucket);
    }
    
    /**
     * 上传图片
     * @param type $file    图片地址
     * @param type $name    图片名称
     */
    public function saveImg($file, $name=null) {
        switch ($file['type']) {
            case 'image/gif': $type='.gif'; break;
            case 'image/png': $type='.png'; break;
            default: $type='.jpg'; break;
        }
        $fileMd5 = md5_file($file['tmp_name']);
        //文件名定义
        if ($name==null) {
            $name = $fileMd5 . $type;
        } else {
            $name .= '_' . base_convert($file['size'], 10, 32) . '_' . base_convert(time(), 10, 32) . $type;
        }
        if ($file['size']==0) {
            $this->error[] = ['文件大小为0'];
            return false;
        }

        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile($this->token, $name, $file['tmp_name']);
        if ($err !== null) {
            $this->error[] = ['上传失败'];
            return false;
        } else {
            return ['path'=>$ret['key']];
        }
    }
    
    /**
     * 保存网络图片路径的文件到七牛服务器   
     * 调用方法 Yii::$app->upload->saveFileToUrl('http://www.html5r.com/templets/images/wallpaper1_1.jpg', '图片名称');
     * @param type $url         网络图片地址
     * @param string $name      图片名称
     * @return boolean
     */
    public function saveImgToUrl($url, $name='other') {
    	Yii::warning('上传图片测试1：'.$url);

        if(!IS_PROD_MACHINE) $name='test';
        $nameTmp = Yii::getAlias('@uploads').'/'.$name . '_' . base_convert(time(), 10, 32) . '.jpg';
        //图片大小
    	$size = @file_put_contents($nameTmp, Yii::$app->util->getUrlHtml($url));
    	Yii::warning('上传图片测试2：'.var_export($size, TRUE));
        if ($size==0 ||$size < 200) {
            @unlink($nameTmp);
            $this->error[] = ['文件大小为0'];
            return false;
        }
        //图片尺寸
        $type = getimagesize($nameTmp);
        //图片md5值
        $fileMd5 = md5_file($nameTmp);
        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile($this->token, "{$name}_{$fileMd5}.jpg", $nameTmp);
        @unlink($nameTmp);
        Yii::warning('上传图片测试3：'.print_r(['ret'=>$ret, 'err'=>$err], TRUE));
        if ($err !== null) {
            $this->error[] = ['上传失败'];
            return false;
        } else {
            return ['path'=>$ret['key']];
        }
    }
    
    /**
     * 流模式上传图片
     * @param type $name
     * @return boolean
     */
    public function saveImgToBinary($name='other') {
        $data = file_get_contents('php://input')?file_get_contents('php://input'):
                            (empty($GLOBALS ['HTTP_RAW_POST_DATA'])?'':gzuncompress($GLOBALS ['HTTP_RAW_POST_DATA']));
        //文件名定义
        $nameTmp = $name . '_' . base_convert(time(), 10, 32) . '.jpg';
        //图片大小
    	$size = @file_put_contents($nameTmp, $data);
        if ($size==0) {
            @unlink($nameTmp);
            $this->error[] = ['文件大小为0'];
            return false;
        }
        //图片尺寸
        $type = getimagesize($nameTmp);
        //图片md5值
        $fileMd5 = md5_file($nameTmp);
        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile($this->token, $nameTmp, $nameTmp);
        @unlink($nameTmp);
        if ($err !== null) {
            $this->error[] = ['上传失败'];
            return false;
        } else {
            return ['path'=>$ret['key']];
        }
    }


    /**
     * 获取错误信息
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * 获取第一条错误信息
     */
    public function getFirstError() {
        return isset($this->error[0])?$this->error[0]:null;;
    }

    public function getImg($src) {
        return $this->domain.$src;
    }
}