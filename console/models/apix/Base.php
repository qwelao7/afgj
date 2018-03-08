<?php
namespace console\models\apix;

/**
* 缴费
* 001:水 002:电 003:燃气
**/
class Base{
	protected $apixKey = "379c45375231430056cc63c08df6474e";
	private $config = [
			'001'=>['ProvinceId'=>'v2056','ProvinceName'=>'江苏','CityId'=>'v2058','CityName'=>'南京','PayProjectId'=>'c2670','PayUnitId'=>'v2351','PayUnitName'=>'南京市自来水总公司','ProductId'=>'6432800'],

			'002'=>['ProvinceId'=>'v2056','ProvinceName'=>'江苏','CityId'=>'v2058','CityName'=>'南京','PayProjectId'=>'c2680','PayUnitId'=>'v2642','PayUnitName'=>'南京市电力公司','ProductId'=>'641401'],

			'003'=>['ProvinceId'=>'v2056','ProvinceName'=>'江苏','CityId'=>'v2058','CityName'=>'南京','PayProjectId'=>'c2681','PayUnitId'=>'v2665','PayUnitName'=>'南京市港华燃气公司','ProductId'=>'6439500'],			
		];

	public  $error = '';

	protected function config($key){
		return isset($this->config[$key])?$this->config[$key]:[];
	}

	protected function toUrl($api,$params=[]){
		$url = $api.'?';
		foreach($params as $key=>$value){
			$url .= $key.'='.$value.'&';
		}
		return substr($url,0,-1);
	}

	protected function httpRequest($url){
		$curl = curl_init();

		curl_setopt_array($curl, array(
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					"accept: application/json",
					"apix-key: ".$this->apixKey,
					"content-type: application/json"
				),
			)
		);

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err){
			die("curl error:" . $err);
		}else{
			$response = json_decode($response);
			return $response;
		}
	}
}