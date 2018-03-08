<?php
return [
    'charset' => 'utf-8',
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'util' => ['class' => 'common\components\Util'],
        'pay' => ['class' => 'common\components\Pay'],
        'upload' => [
            'class' => 'common\components\Upload',
            'bucket' => 'afgj-pub',
            'domain' => 'http://pub.huilaila.net/',
            'qiniuAk' => 'L0KWeXKdwoQdYZtyunFwNRPRlGRQN8JdPxJewoZa',
            'qiniuSk' => '1-a2gQP-z3uBD9EP6iq-Bndgp_MTtv2IfZgxvt5s',
        ],
        'sms'=> [
            'class' => 'common\components\Sms',
            'appkey' => '23329701',
            'secret' => '9c0d65cb46913fb2c52c5d22f3337ad0',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => REDIS_IP,
            'port' => 6379,
            'database' => 0,
        ],
        'cache' => [
            'class' => 'yii\redis\Cache',
            'redis' => 'redis'
        ],
    ],
	'timeZone'=>'Asia/Shanghai',
	'language'=>'zh-CN',
];
