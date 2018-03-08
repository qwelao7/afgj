<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace api\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
	public $basePath = '@webroot';
	public $baseUrl = '@web';
	public $css;
	public $js;
	public $depends = [];
	public function init()
	{
		parent::init();
		$this->css = [
			'assets/lib/ionic/css/ionic.min.css',
			'assets/css/home.css?v='.STATIC_FILE_VERSION,
			'assets/css/style.css?v='.STATIC_FILE_VERSION,
		];
		$this->js = [
			'http://res.wx.qq.com/open/js/jweixin-1.0.0.js',
			'http://pub.huilaila.net/bundle.min.js',
			'http://pub.huilaila.net/city-data.js',
			'assets/js/app.js?v='.STATIC_FILE_VERSION,
			'assets/js/controllers.js?v='.STATIC_FILE_VERSION,
			'assets/js/services.js?v='.STATIC_FILE_VERSION,
		];
	}
}
