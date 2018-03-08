<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

$config = [
    'id' => 'app-mobile',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'mobile\controllers',
    'components' => [
        'request' => [
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\ecs\EcsUsers',
            'enableAutoLogin' => false,
        ],

        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['order'],
                    'logFile' => '@app/runtime/logs/Orders/requests.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 20,
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['callback'],
                    'logFile' => '@app/runtime/logs/Orders/callback.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 20,
                ],
            ],
        ],
//        'session' => [
//            'class' => 'yii\redis\Session',
//            'keyPrefix' => 'wx:',
//            'redis' => [
//                'hostname' => REDIS_IP,
//                //'password' => REDIS_PASS,
//                'port' => 6379,
//                'database' => 1,
//            ]
//        ],
        'errorHandler' => [
            'errorAction' => 'error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,  
            'showScriptName' => false,
            'rules' => [
                "<controller:\w+>/<action:\w+>/<id:\d+>" => "<controller>/<action>",
                "<controller:\w+>/<action:\w+>" => "<controller>/<action>",
            ],
        ],
    ],
    'modules' => [
        'rest' => ['class' => 'mobile\modules\rest\RestModule',],
        'v2' => ['class' => 'mobile\modules\v2\Module'],
    ],
    'params' => $params,
];
return $config;