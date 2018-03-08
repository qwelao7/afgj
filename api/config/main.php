<?php
$params = array_merge(
	require(__DIR__ . '/params.php'),
	require(__DIR__. '/params-local.php'),
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php')
);
$uriMap = require(__DIR__ . '/uri.php');
$a = require(__DIR__ . '/params-local.php');
$db = $a['db'];
return [
	'id' => 'hll-api',
	'basePath' => dirname(__DIR__),
	'bootstrap' => ['log'],
	'controllerNamespace' => 'api\controllers',
	'components' => [
		'user' => [
			'identityClass' => 'api\modules\third\models\User',
			'enableAutoLogin' => false,
			'loginUrl' => null
		],
		'log' => [
			'traceLevel' => YII_DEBUG ? 3 : 0,
			'targets' => [
				[
					'class' => 'yii\log\FileTarget',
					'levels' => ['error', 'warning'],
					'maxFileSize' => 1024 * 2,
				],
			],
		],
		'db' => [
            'class' => $db['class'],
            'dsn' => $db['dsn'],
            'username' => $db['username'],
            'password' => $db['password'],
            'charset' => $db['charset'],
        ],
		'urlManager' => [
			'enablePrettyUrl' => true,
			//'enableStrictParsing' => true,
			'showScriptName' => false,
			'rules' => $uriMap,
		],
		'errorHandler' => [
			'errorAction' => 'site/error',
		],
		'zhima' => $a['zhima'],
		'duiba' => $a['duiba'],
	],
	'modules' => [
		'third' => [
			'class' => 'api\modules\third\Module'
		],
		'zhima' => [
			'class' => 'api\modules\zhima\Module'
		],
		'duiba' => [
			'class' => 'api\modules\duiba\Module'
		],
	],
	'params' => $params,
];
