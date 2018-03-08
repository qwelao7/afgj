<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=121.196.237.177;dbname=afgj',
            'username' => 'tangjun',
            'password' => '77t7sNhmLoOQwKB7',
            'charset' => 'utf8',
        ],
        'wx' => [
            'class' => 'common\components\WeiXin',
            'appId' => 'wx527a263cf0706ac0',
            'appsecret' => 'a8bf38c4d6472e6eeaffe90538f15949',
        ],
        'im'=> [
            'class' => 'common\components\BaiChuanIm',
            'appkey' => '23409825',
            'secret' => 'd8dad129ad6648db7da8671aa30fc87c',
            'format' => 'json',
        ],
        'wechat' => [
            'class' => 'common\components\wechat\WeChat',
            'app_id' => 'wx527a263cf0706ac0',
            'secret' => 'a8bf38c4d6472e6eeaffe90538f15949',
            'payment' => [
                'merchant_id'        => '1330063201',
                'key'                => 'qimQpVZASenCtnJrspo8XstiiuNQSlGw',
                'cert_path'          => '/mnt/www/afgj/common/cert/weixin/apiclient_cert.pem', // XXX: ¾ø¶Ô·¾¶£¡£¡£¡£¡
                'key_path'          => '/mnt/www/afgj/common/cert/weixin/apiclient_key.pem', // XXX: ¾ø¶Ô·¾¶£¡£¡£¡£¡
            ]
        ],
        'ne_captch_verifier'=> [
            'class' => 'common\components\yidun\NECaptchaVerifier',
            'captchaId' =>'df7a22a433e840ac8317cfbabd41f3a8',
            'secretId' => '14ddfbccbdc3ebbdd47e391b545931b9',
            'secretKey' => 'db4da0a64a3ca8f359c3e6041f106d41',
        ],
    ],
];
