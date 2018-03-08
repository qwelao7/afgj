<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'controllerMap'=>[
        'AsyncTask'=>[
            'class'=>'console\controllers\AsyncTaskController'
        ]
    ],
    'components' => [
        'log' => [
            'targets' => [
				[
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info','error', 'warning'],
                    'categories' => ['job'],
                    'logFile' => '@app/runtime/logs/app.log',
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 20,
                ],
            ],
        ],
    ],
    'params' => $params,
];
