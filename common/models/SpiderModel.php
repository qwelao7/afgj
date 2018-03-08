<?php
/**
 *
 * @author: xuyi
 * @date: 2017/3/20
 */

namespace common\models;

use common\models\hll\HllLibraryBookInfo;
use Goutte\Client;
use yii\base\Model;
use yii;
use common\models\hll\HllLibraryBookCommerce;

class SpiderModel extends Model
{
	public static function SpiderBookByIsbn($isbn = '9787201094014')
	{
		$msg = '';

		try {
			$data = [];
			$img = ['slide' => []];
			$info = [];
			$client = new Client();
			$crawler = $client->request('get', 'https://book.douban.com/isbn/' . $isbn);

			$bg = $crawler->filter('.nbg')->html();
			preg_match("/<img[^>]*?src=\"([^>]+?)\"[^>]*?>/i", $bg, $match);
			$img_url = $match[1];
			$img['thumbnails'] = [
				'large' => $img_url,
				'small' => str_replace('lpic', 'spic', $img_url),
				'medium' => str_replace('lpic', 'mpic', $img_url),
			];
			try {
				$info['title'] = trim(strstr($crawler->filter('title')->getNode(0)->textContent, '(', true));
			} catch (\Exception $e) {
				$info['title'] = '';
			}
			try {
				$info['introduce'] = $crawler->filter('#link-report')->filter('.intro')->getNode(1)->textContent;
			} catch (\Exception $exception) {
				$info['introduce'] = '';
			}
			$buy_links = $crawler->getUri() . '/buylinks';
			$crawler = $client->request('get', $buy_links);
//			echo $crawler->html();exit();


			$crawler->filter('#buylink-table ')->filter('tr')->each(function ($node) use (&$data, &$img, &$info) {
				$html = $node->text();

				if (strstr($html, '京东商城')) {
					preg_match("/([a-z0-9-.]*)\.([a-z0-9-.]*)/", $html, $res);
					$price = $res[0];
					$url = urldecode($node->filter('a')->getNode(0)->getAttribute('href'));
					$u = 'https://' . substr(strstr(strstr($url, 'url'), '//'), 2);
					$c = new Client();
					$ca = $c->request('GET', $u);
					$html = (string)$ca->text() . '';
					$u = strstr(strstr($html, 'https'), '\'', true);
					$ca = $c->request('GET', $u);
					$url = (string)$ca->getUri() . '';
					$url = strstr($url, '?', true);
					$m_url = str_replace('item.jd', 'item.m.jd', $url);
					$par = explode('/', $m_url);
					$par[4] = $par[3];
					$par[0] = 'https:';
					$par[3] = 'product';
					$m_url = implode('/', $par);

					$data['jingdong'] = [
						'price' => $price,
						'url' => $m_url
					];

					if (0) {
						$ca2 = $c->request('GET', $m_url);
						$img_html = $ca2->filter('#slide')->html();
						preg_match_all("/<img[^>]*?src=\"([^>]+?)\"[^>]*?>/i", $img_html, $match);
						$img['slide'] = $match[1];
					}
					if (!isset($info['category'])) {
						$breadcrumb = explode('>', trim($ca->filter('.breadcrumb')->getNode(0)->textContent));
						$info['category'] = $breadcrumb[1];
					}
					unset($ca, $c);

				}
				if (strstr($html, '中国图书网')) {
					preg_match("/([a-z0-9-.]*)\.([a-z0-9-.]*)/", $html, $res);
					$price = $res[0];
					$url = urldecode($node->filter('a')->getNode(0)->getAttribute('href'));
					$url_param = parse_url($url);
					parse_str($url_param['query'], $param);
					$data['bookschina'] = [
						'price' => $price,
						'url' => $param['tourl']
					];

				}
				if (strstr($html, '当当网')) {
					preg_match("/([a-z0-9-.]*)\.([a-z0-9-.]*)/", $html, $res);
					$price = $res[0];
					$url = urldecode($node->filter('a')->getNode(0)->getAttribute('href'));
					$url_param = parse_url($url);
					parse_str($url_param['query'], $param);
					$url = "http://product.dangdang.com/" . substr(strstr($param['backurl'], '='), 1) . ".html";
					$m_url = "http://product.m.dangdang.com/product.php?pid=" . substr(strstr($param['backurl'], '='), 1);
					$data['dangdang'] = [
						'price' => $price,
						'url' => $m_url
					];
					$c = new Client();
					$ca = $c->request('GET', $url);
					if (!$img['slide']) {
						$ca2 = $c->request('GET', $m_url);
						$img_html = $ca2->filter('#slider')->html();
						preg_match_all("/<img[^>]*?src=\"([^>]+?)\"[^>]*?>/i", $img_html, $match);
						$img['slide'] = $match[1];

					}
					if (!isset($info['category'])) {
						$breadcrumb = explode('>', trim($ca->filter('.breadcrumb')->getNode(0)->textContent));
						$info['category'] = $breadcrumb[1];
					}
					unset($ca, $c);
				}
			});
			return ['data' => ['img' => $img, 'buy' => $data, 'info' => $info], 'msg' => $msg, 'res' => true];
		} catch (\Exception $e) {
			return ['img' => $img, 'data' => $data, 'msg' => '采集失败:' . $e->getMessage(), 'res' => false];
		}
	}

	//查询图书信息是否完整，不完整则补充完整
	public static function addBookByIsbn()
	{
		$add_num = 0;
		$isbn_list = HllLibraryBookInfo::find()->select(['isbn'])->where(['is_complete' => 0, 'valid' => 1])->column();
		foreach ($isbn_list as $item) {
			$info = static::SpiderBookByIsbn($item);
			if ($info['res'] == false) {
				continue;
			}
			$tran = Yii::$app->db->beginTransaction();
			try {
				$bookInfo = HllLibraryBookInfo::findOne(['isbn' => $item]);
				$bookInfo->thumbnail = $info['data']['img']['thumbnails']['large'];
				$bookInfo->pics = json_encode($info['data']['img']['slide']);
				$bookInfo->book_name = $info['data']['info']['title'];
				$bookInfo->book_type = array_key_exists('category', $info['data']['info']) ? $info['data']['info']['category'] : ' ';
				if ($bookInfo->save()) {
					$bookCommerce = HllLibraryBookCommerce::find()->where(['book_info_id' => $bookInfo->id, 'valid' => 1])->count();
					if ($bookCommerce == 0) {
						HllLibraryBookCommerce::setBookStore($info['data']['buy'], $bookInfo->id);
					}
					$bookInfo->is_complete = 1;
					if ($bookInfo->save()) {
						$tran->commit();
						$add_num++;
						continue;
					}
				}
			} catch (\yii\db\Exception $e) {
				$tran->rollBack();
				continue;
			}
		}
		return $add_num;
	}


	public static function SpiderEquipmentBrand($cate_list)
	{
		set_time_limit(0);


		$time = time();
		try {
			echo '开始采集分类' . "\n";
			$cateUrl = [];
			$brandUrl = [];
			$client = new Client();
			$crawler = $client->request('get', 'http://product.yesky.com/maintain/');
			$crawler->filter('.wxfl')->filter('.clear')->filter('a')->each(function ($node) use (&$cateUrl, $cate_list) {

				$name = str_replace([' ', "#(\s)#"], '', trim($node->text()));
				if (in_array($name, $cate_list)) {
					$cateUrl[array_search($name, $cate_list)] = ['name' => $name, 'url' => $node->attr('href')];
				}
			});

			foreach ($cateUrl as $cate_id => $v) {
				if (strstr($v['url'], 'maintain.shtml') === false) {
					continue;
				}
				$crawler = $client->request('get', $v['url']);

				$crawler->filter('.brand_map')->filter('a')->each(function ($node) use (&$brandUrl, $cate_id) {
					$title = $node->text();
					$name = strstr($title, '(', true);
					$num = strstr(substr(strstr($title, '('), 1), ')', true) + 0;
					$brandUrl[$cate_id][] = [
						'url' => $node->attr('href'),
						'name' => $name,
						'num' => $num,
					];
				});
			}
			echo '采集结束,耗时:' . (time() - $time) . 's' . "\n";
			return $brandUrl;
		} catch (\Exception $e) {
			echo '采集异常' . ' file:' . $e->getFile() . ' msg:' . $e->getMessage() . "\n";
			return false;
		}
	}

	public static function SpiderEquipmentCenter($brand_list)
	{

		$time = time();
		try {
			echo '开始采集维修点数据' . "\n";

			$brandData = [];
			$client = new Client();
			foreach ($brand_list as $brand_id => $url) {
				echo '开始采集brand_id:' . $brand_id . ' 维修点数据' . "\n";
				$crawler = $client->request('get', $url);
				//完善品牌信息
				try {
					$brandData[$brand_id]['brand']['data_url'] = $url;
					$brandData[$brand_id]['brand']['service_info'] = trim($crawler->filter('.brandwarr dl')->text());
					$brandData[$brand_id]['brand']['service_policy_url'] = $crawler->filter('.brandwith')->filter('a')->attr('href');
					$brandData[$brand_id]['brand']['cust_service_phone'] = trim(substr(strstr(strstr(str_replace(['-', "#(\s)#"], '', $crawler->filter('.brandserv')->getNode(1)->textContent), '电话'), '服务', true), 6));
					$brandData[$brand_id]['brand']['service_time'] = trim(substr(strstr(strstr($crawler->filter('.brandserv')->getNode(1)->textContent, '服务时间'), '：'), 3));
				} catch (\Exception $exception) {
					$brandData[$brand_id]['brand'] = [
						'service_info' => '',
						'service_policy_url' => '',
						'cust_service_phone' => '',
						'service_time' => '',
						'data_url' => $url
					];

				}
				//采集维修点信息
				try {
					//获取有维修点的省市
					$regionList = $crawler->filter('#brand-serviveareas1')->filter('a')->each(function ($node) {
						return ['region_id' => substr(strstr(strstr($node->attr('href'), '_'), '.', true), 1) + 0, 'url' => $node->attr('href')];
					});

					preg_match_all('/\d+/', $url, $m);
					$origin_brand_id = $m[0][1];//天极网品牌id
					$d = [];
					foreach ($regionList as $v) {
						$region_id = $v['region_id'];
						$crawler = $client->request('get', $v['url']);
						preg_match_all('/\d+/', $crawler->filter('.brand-pagetabs em')->text(), $m);
						$page_limit = $m[0][0];
						$total = $m[0][1];
						$page = ceil($total / $page_limit);
						$center_url = 'http://product.yesky.com/front/maintaincenter/maintainbrand.do?brandId=' . $origin_brand_id . '&productId=0&regionId=' . $region_id . '&cityId=0&pageSize=' . $page_limit . '&fromPage=1&pageNo=';

						for ($i = 1; $i <= $page; $i++) {
							$crawler = $client->request('get', $center_url . $i);
							$d = array_merge($d, $crawler->filter('.dfjs li')->each(function ($node) use ($region_id) {
								$address = trim($node->filter('.xxdz')->text());
								switch ($region_id) {
									case 1:
										$city = '北京';
										break;
									case 48:
										$city = '天津';
										break;
									case 2:
										$city = '重庆';
										break;
									case 4:
										$city = '上海';
										break;
									default:
										$city = trim($node->filter('.quyu')->text());
								}


								$name = substr(strstr($node->filter('.address')->text(), '公司名称：'), 15);
								$phone_text = $node->filter('.dianhua')->text();
								$phone_text = strlen(strstr($phone_text, '/')) ? strstr($phone_text, '/', true) : $phone_text;//多号码处理
								preg_match_all('/\d+/', $phone_text, $m);
								$phone = implode('', $m[0]) . '';
								return [
									'city' => $city,
									'address' => $address,
									'name' => $name,
									'phone' => $phone,
								];
							}));

						}
					}

					$brandData[$brand_id]['center'] = $d;
					echo 'brand_id:' . $brand_id . ' 维修点数据采集完成' . "\n";
				} catch (\Exception $exception) {
					$brandData[$brand_id]['center'] = [];
				}
			}
			echo '采集结束,耗时:' . (time() - $time) . 's' . "\n";
			return $brandData;
		} catch (\Exception $e) {
			echo '采集异常' . ' file:' . $e->getFile() . ' line:' . $e->getLine() . ' msg:' . $e->getMessage() . "\n";
			return false;
		}
	}
}