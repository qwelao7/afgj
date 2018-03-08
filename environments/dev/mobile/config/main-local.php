<?php

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'VLY24H3-JCj8Ve179zqREIWnIVYD-2PO',
            'enableCookieValidation' => false,
        ],
        'session' => [
            'cookieParams' => ['domain' => '.afguanjia.com', 'lifetime' => 180000],
            'timeout' => 24*3600*30,
        ],
    ],
];

if (!YII_ENV_TEST) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'yii\gii\Module';
}

return $config;