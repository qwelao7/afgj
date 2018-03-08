<?php

if (!defined("WXTM_SDK_WORK_DIR"))
{
	define("WXTM_SDK_WORK_DIR", dirname(__FILE__));
}


/**
* 注册autoLoader,此注册autoLoader只加载top文件
* 不要删除，除非你自己加载文件。
**/
require("Autoloader.php");