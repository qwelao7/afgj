<?php
/**
 *
 * @author: xuyi
 * @date: 2017/3/24
 */
namespace common\components\zhima;

use common\models\hll\HllZhimaLog;
use yii\base\Object;

/**
 * Class ZhiMa
 *
 ** @property \ZmopClient $app The response component. This property is read-only.
 */
class ZhiMa extends Object
{
	public $appId = 1002265;
	public $privateKeyFilePath = '/../../cert/zhima/hll_private_key.pem';
	public $zhiMaPublicKeyFilePath = '/../../cert/zhima/zm_public_key.pem';
	public $gatewayUrl = 'https://zmopenapi.zmxy.com.cn/openapi.do';
	public $redirectUrl = 'http://www.huilaila.net/credit-index.html';
	public $errorUrl = 'http://www.huilaila.net/credit-error.html';

	private $charset = 'UTF-8';

	public $app = null;

	function init()
	{
		spl_autoload_register(__NAMESPACE__ . '\ZhiMa::__zhima_autoload');
		$private = __DIR__ . $this->privateKeyFilePath;
		$public = __DIR__ . $this->zhiMaPublicKeyFilePath;

		$client = new \ZmopClient($this->gatewayUrl, $this->appId, $this->charset, $private, $public);
		$this->app = $client;

	}


	/**
	 * 获取用户授权url 用户授权成功后会通过回调访问服务器
	 * @params $name
	 * @params $certNo
	 * @params $user_id
	 * @params $to_url
	 * @return string
	 */
	public function getUserAuthorizeUrl($name, $certNo, $user_id, $to_url)
	{
		$request = new \ZhimaAuthInfoAuthorizeRequest();
		$request->setChannel("app");
		$request->setPlatform("zmop");
		$request->setIdentityType("2");// 必要参数
		$request->setIdentityParam('{"certNo":"' . $certNo . '","name":"' . $name . '","certType":"IDENTITY_CARD"}');// 必要参数
		$request->setBizParams('{"auth_code":"M_H5","channelType":"app","state":"' . $user_id . ':'. $to_url . '"}');

		$url = $this->app->generatePageRedirectInvokeUrl($request);
		return $url;
	}

	/**
	 * 解析数据
	 * @param $sign
	 * @param $param
	 * @return bool|string
	 */
	public function decryptAndVerifySign($sign, $param, $url_encode=false)
	{
		try {
			if ($url_encode){
				$sign = urldecode($sign);
				$param =  urldecode($param);
			}

			$r = \RSAUtil::rsaDecrypt($param, $this->app->privateKeyFilePath);


			$res = \RSAUtil::verify($r, $sign, $this->app->zhiMaPublicKeyFilePath);


			if ($res === true) {
				return urldecode($r);
			}
		} catch (\Exception $e) {

			echo 'error';
			$this->app->logError('error:'.$e->getMessage() .' where:'.$e->getFile().' @'.$e->getLine());

		}
		return false;
	}


	public function getUserScore($open_id = '268815685213546311285681574')
	{
		$transactionId = '' . date('yyyyMMddhhmmssSSS') . '00000000' . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9);
		try {
			$request = new \ZhimaCreditScoreGetRequest();

			$request->setPlatform("zmop");
			$request->setTransactionId($transactionId);
			$request->setProductCode('w1010100100000000001');//目前为固定值
			$request->setOpenId($open_id);
			$response = $this->app->execute($request);

			if ($response->success == true) {
				$model = new HllZhimaLog();
				$model->transaction_id = $transactionId;
				$model->biz_no = $response->biz_no;
				$model->save();
				return $response->zm_score;
			}

		} catch (\Exception $e) {
			$this->app->logError('error:'.$e->getMessage() .' where:'.$e->getFile().' @'.$e->getLine());
		}
		return false;
	}

	/**
	 * 自动加载类
	 * @param $className
	 */
	private function __zhima_autoload($className)
	{
		if (strchr($className, 'Request')) {
			$filePath = 'request/' . $className . '.php';
		} else {
			$filePath = $className . '.php';
		}

		$includePaths = explode(PATH_SEPARATOR, get_include_path());
		$sdk_root = __DIR__ . '/sdk/';
		foreach ($includePaths as $includePath) {
			if (file_exists($sdk_root . $includePath . DIRECTORY_SEPARATOR . $filePath)) {

				require_once($sdk_root . $includePath . DIRECTORY_SEPARATOR . $filePath);
				return;
			}
		}
	}

}