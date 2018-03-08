<?php
namespace console\models\juhe;

/**
* 固话/宽带
**/
class Base{
	protected $AppKey = "705b23bc5deb184ca8fc4255f9eed448";
	protected $OpenId = "JH807de22c6124f290e6d45bf20c8bc27f";
	private static $config = [
			'teltype'=>['1'=>'电信','2'=>'联通'],
			'pervalue'=>['1'=>['10'=>'10','20'=>'20','30'=>'30','50'=>'50','100'=>'100','300'=>'300'],'2'=>['50'=>'50','100'=>'100']],
			'chargetype'=>['1'=>'固话','2'=>'宽带'],			
		];

	public static function config($key){
		return isset(self::$config[$key])?self::$config[$key]:[];
	}

	/**
	 * 请求接口返回内容
	 * @param  string $url [请求的URL地址]
	 * @param  string $params [请求的参数]
	 * @param  int $ipost [是否采用POST形式]
	 * @return  string
	 */
	private function juheCurl($url,$params=false){
		$httpInfo = array();
		$ch = curl_init();
	 
		curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
		curl_setopt( $ch, CURLOPT_USERAGENT , 'JuheData' );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 60 );
		curl_setopt( $ch, CURLOPT_TIMEOUT , 60);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt( $ch , CURLOPT_POST , true );
		curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
		curl_setopt( $ch , CURLOPT_URL , $url );

		$response = curl_exec( $ch );
		if ($response === FALSE) {
			die("curl error: " . curl_error($ch));
		}
		$httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
		$httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
		curl_close( $ch );
		return $response;
	}

	protected function httpRequest($url,$params=false){
		if(is_array($params) && isset($params['key']) && empty($params['key'])){
			$params['key'] = $this->AppKey;
		}
		$paramstring = http_build_query($params);
		$content = $this->juheCurl($url,$paramstring);
		$result = json_decode($content);
		return $result;
	}
}
 