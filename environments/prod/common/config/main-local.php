<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=rm-bp1t54wlq77pkw24r.mysql.rds.aliyuncs.com;dbname=afgj',
            'username' => 'php',
            'password' => '0kg4Tv3IEIHwWOtz',
            'charset' => 'utf8',
        ],
        'wx' => [
            'class' => 'common\components\WeiXin',
            'appId' => 'wx44cbab498f26bf2f',
            'appsecret' => '37efc72ce7a189df0d8d57c849f88609',
        ],
        'im'=> [
            'class' => 'common\components\BaiChuanIm',
            'appkey' => '23444086',
            'secret' => '220842517ab0ad5ef9101c3b6601abcd',
            'format' => 'json',
        ],
        'wechat' => [
            'class' => 'common\components\wechat\WeChat',
            'app_id' => 'wx44cbab498f26bf2f',
            'secret' => '37efc72ce7a189df0d8d57c849f88609',
            'payment' => [
                'merchant_id'        => '1375462902',
                'key'                => 'qimQpVZASenCtnJrspo8XstiiuNQSlGw',
                'cert_path'          => '/mnt/www/afgj/common/cert/weixin/apiclient_cert.pem',
                'key_path'          => '/mnt/www/afgj/common/cert/weixin/apiclient_key.pem',
            ]
        ],
        'ne_captch_verifier'=> [
            'class' => 'common\components\yidun\NECaptchaVerifier',
            'captchaId' =>'3c39aa8a680441229334259ec403ea47',
            'secretId' => 'c036d4034efcf4c142fd6ba85f6933f7',
            'secretKey' => '6ce1033d4bb5d6288ce137cc46d8b81d',
        ],
    ],
];