<?php
return [
    'adminEmail' => ['guanxingmin@afguanjia.com','lvtao@afguanjia.com'],
    'user.passwordResetTokenExpire' => 3600,
	'imgurl' => '',
	'jiaofei' => [
		'2'=> ['key'=>'waterfeeaccount','type'=>'001','api'=>'apix'],
		'3'=>['key'=>'electricfeeaccount','type'=>'002','api'=>'apix'],
		'4'=>['key'=>'gasfeeaccount','type'=>'003','api'=>'apix'],
		'20'=>['key'=>'telephonefeeaccount','type'=>'','api'=>'juhe'],
		'1'=>['key'=>'propertyfeeaccount','type'=>''],
	],
    'wx' => [
        'host' => 'https://api.weixin.qq.com/'
    ],
    'cdn' => [//生产和测试机器上的cdn域名
        'prod' => '//cdn.huilaila.net',
        'test' => '',//测试环境暂时不用cdn加速 cdn.afguanjia.com
    ],
    'cdnExcept' => [//哪些文件不需要cdn加速
        '/assets/lib/ionic/css/ionic.min.css'//cdn加速了会产生字体文件跨域问题
    ],
	'catpchaKey' => 'ff2ef298ff0fc648cf7004f1b46c6570',
    'userDefaultAvatar' => 'http://pub.huilaila.net/avatar/defaultavatar.jpg',
	'defaultActImg' => 'defaultpic/active.jpg',
	'defaultVoteImg' => 'defaultpic/vote.jpg'
];
