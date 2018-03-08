<?php
/**
 * 环境有关的常量
 */
//是否是生产机器
defined('IS_PROD_MACHINE') or define('IS_PROD_MACHINE', true);
//是否是测试机器
defined('IS_TEST_MACHINE') or define('IS_TEST_MACHINE',false);
//其他的都算开发机器
defined('IS_DEV_MACHINE') or define('IS_DEV_MACHINE', !IS_PROD_MACHINE && !IS_TEST_MACHINE);
//redis的服务器地址
defined('REDIS_IP') or define('REDIS_IP', '127.0.0.1');

//redis的连接密码
//defined('REDIS_PASS') or define('REDIS_PASS', '');

//微信token
defined('WXTOKEN')  or define("WXTOKEN", "kB9aqKnVXezeM00X");
//腾讯统计
defined('TXSTATID')  or define("TXSTATID", "500149441");
//静态资源版本号
defined('STATIC_FILE_VERSION')  or define("STATIC_FILE_VERSION",90);

defined('ECTOUCH_ROOT_PATH')  or define("ECTOUCH_ROOT_PATH", "/mnt/www/huilaila/");
defined('ECTOUCH_LOGIN_URL')  or define("ECTOUCH_LOGIN_URL", "http://mall.huilaila.net/index.php?m=default&c=user&a=login");
defined('ECTOUCH_USER_HOME_URL')  or define("ECTOUCH_USER_HOME_URL", "http://mall.huilaila.net/index.php?m=default&c=user&a=index");//我家页面