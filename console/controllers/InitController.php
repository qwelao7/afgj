<?php
namespace console\controllers;

use common\models\ecs\EcsUsers;
use common\models\hll\HllCustInvite;
use common\models\hll\HllUserPoints;
use Yii;
use yii\console\Controller;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * 数据初始化
 * Class InitController
 * @package console\controllers
 */

class InitController extends Controller{
	/**
	 * 初始化百川IM用户账户信息
	 * @author zend.wang
	 * @date  2016-06-08 13:00
	 */
	public function actionBaichuanIm(){
		//for($i=2;$i<40;$i++) {
		//	//Yii::$app->im->addUser($i);
		//	//Yii::$app->im->delUser($i);
		//}
	}
	/**
	 * 初始化汽车品牌信息
	 * @author zend.wang
	 * @date  2016-09-28 13:00
	 */
	public function actionCarBrand() {
		$brandUrl ="http://www.autohome.com.cn/ashx/AjaxIndexCarFind.ashx?type=1";
		$opts    = array(
			'http' => array(
				'method'  => "GET",
				'timeout' => 2,
			)
		);
		$context = stream_context_create($opts);
		$data    = file_get_contents($brandUrl, false, $context);
		$data=\Qiniu\json_decode(mb_convert_encoding($data, "UTF-8", "GBK"),true);

		if($data['returncode']  == 0) {
			$db = Yii::$app->db;
			try{
				$db->createCommand()->delete('{{hll_car_brand}}');
				$db->createCommand()->batchInsert('{{hll_car_brand}}', ['id','name','bfirstletter'],$data['result']['branditems'])->execute();
			}catch (\Exception $e){

			}

		}
	}
	/**
	 * 初始化汽车型号信息
	 * @author zend.wang
	 * @date  2016-09-28 13:00
	 */
	public function actionCarSeries() {

		$opts    = array(
			'http' => array(
				'method'  => "GET",
				'timeout' => 2,
			)
		);
		$brands = (new Query())->select("id")->distinct(true)->from('hll_car_brand')->all();

		$db = \Yii::$app->db;
		try{

			foreach ($brands as $brand) {
				$seriesUrl ="http://www.autohome.com.cn/ashx/AjaxIndexCarFind.ashx?type=3&value={$brand['id']}";

				$context = stream_context_create($opts);
				$data    = file_get_contents($seriesUrl, false, $context);
				$data = \Qiniu\json_decode(mb_convert_encoding($data, "UTF-8", "GBK"),true);

				if($data['returncode']  == 0) {
					$factoryItems = $data['result']['factoryitems'];
					if($factoryItems) {
						foreach ($factoryItems as $factory) {

							$effectedRow = $db->createCommand()->setSql("select count(*) from hll_car_factory where id ={$factory['id']}")->queryScalar();

							if(!$effectedRow) {
								$db->createCommand()->insert('hll_car_factory',['id'=>$factory['id'],'brand_id'=>$brand['id'],
									'name'=>$factory['name'],'firstletter'=>$factory['firstletter']])->execute();
							}

							foreach ($factory['seriesitems'] as &$seriesitem) {
								$seriesitem['brand_id'] = $brand['id'];
								$seriesitem['factory_id'] = $factory['id'];
							}

							$db->createCommand()->batchInsert('{{hll_car_series}}',
								['id','name', 'firstletter','seriesstate','seriesorder','brand_id','factory_id'],
								$factory['seriesitems'])->execute();

						}
					}
				}
			}
		}catch (\Exception $e){
			echo $e->getMessage();
		}
	}

	/**
	 * 增量更新小区信息
	 * @author zend.wang
	 * @date  2016-06-08 13:00
	 */
	public function actionCommunityUpdate() {

		require(__DIR__ . '/../../common/util/simple_html_dom.php');

		$opts    = array(
			'http' => array(
				'method'  => "GET",
				'timeout' => 2,
			)
		);

		$cityArr = [
			/*南京市 finished*/
			'nj'=>[
				'gulou'=>['name'=>'鼓楼','province'=>'16','city'=>'220','district'=>'1835'],
				'jianye'=>['name'=>'建邺','province'=>'16','city'=>'220','district'=>'1837'],
				'qinhuai'=>['name'=>'秦淮','province'=>'16','city'=>'220','district'=>'1838'],
				'qixia'=>['name'=>'栖霞','province'=>'16','city'=>'220','district'=>'1841'],
				'xuanwu'=>['name'=>'玄武','province'=>'16','city'=>'220','district'=>'1834'],
				'yuhuatai'=>['name'=>'雨花台','province'=>'16','city'=>'220','district'=>'1839'],
				'jiangning'=>['name'=>'江宁','province'=>'16','city'=>'220','district'=>'1843'],
				'pukou'=>['name'=>'浦口','province'=>'16','city'=>'220','district'=>'1842'],
				'liuhe'=>['name'=>'六合','province'=>'16','city'=>'220','district'=>'1844'],
				'lishui'=>['name'=>'溧水','province'=>'16','city'=>'220','district'=>'1845'],
				'gaochun'=>['name'=>'高淳','province'=>'16','city'=>'220','district'=>'1846'],
			]
		];

		$db = \Yii::$app->db;

		$db->createCommand()->delete('{{hll_community_temp}}');
		$db->createCommand()->delete('{{hll_community_beta}}');

		//采集小区名称信息
		foreach($cityArr as $cityKey => $city) {

			foreach($city as $districtKey => $district) {

				$url ="http://{$cityKey}.lianjia.com/xiaoqu/{$districtKey}/pg1/";
				$dom = file_get_html($url);

				$total = intval($dom->find('h2.total span',0)->plaintext);
				$pageCount = ceil($total/30);

				$ret = $dom->find('li .title');

				$rows = [];
				foreach($ret as $v) {
					$thumbnail = $v->parent->parent->first_child()->children(0)->getAttribute('data-original');
					$cname = trim($v->plaintext);
					$firstword = f_firstLetter($cname);
					$rows[] = [$district['province'],$district['city'],$district['district'],$cname,$thumbnail,$firstword[0]];
				}


				$db->createCommand()->batchInsert('{{hll_community_temp}}',
					['province','city','district', 'name','thumbnail','firstletter'],$rows)->execute();

				for($i=2;$i<=$pageCount;$i++) {

					$url ="http://{$cityKey}.lianjia.com/xiaoqu/{$districtKey}/pg{$i}/";
					$dom = file_get_html($url);
					$rows = [];
					$ret = $dom->find('li .title');
					foreach($ret as $v) {
						$thumbnail = $v->parent->parent->first_child()->children(0)->getAttribute('data-original');
						$cname = trim($v->plaintext);
						$firstword = f_firstLetter($cname);
						$rows[] = [$district['province'],$district['city'],$district['district'],$cname,$thumbnail,$firstword[0]];
					}
					$db->createCommand()->batchInsert('{{hll_community_temp}}',
						['province','city','district', 'name','thumbnail','firstletter'],$rows)->execute();
				}
			}
		}

		//hll_community_temp 与 hll_community 表根据小区district、name比较 不存在则新增一条记录
		foreach($cityArr as $cityKey => $city) {

			foreach($city as $districtKey => $district) {

				$subQueryTemp =(new Query())->select('*')->from('hll_community_temp as t1')->where(['district'=>$district['district']]);
				$subQuery =(new Query())->select('name')->from('hll_community as t1')->where(['district'=>$district['district']]);
				$newCommunityList = (new Query())->select(['t1.province','t1.city','t1.district','t1.name','t1.thumbnail','t1.firstletter'])
						->from(['t1'=>$subQueryTemp])
						->leftJoin(['t2'=>$subQuery],'t2.name = t1.name')
						->where('t2.name is null ')
						->all();
				if($newCommunityList) {
					foreach($newCommunityList as &$val) {
						if($val['thumbnail']) {
							$result = Yii::$app->upload->saveImgToUrl($val['thumbnail'],'xq');
							if($result) {
								$val['thumbnail'] = $result['path'];
							}
						}
					}
					$db->createCommand()->batchInsert('{{hll_community_beta}}',
						['province','city','district', 'name','thumbnail','firstletter'],$newCommunityList)->execute();
				}
			}
		}
	}
	/**
	 * 初始化小区+缩略图
	 * @author zend.wang
	 * @date  2016-10-09 13:00
	 */
	public function actionCommunityInit() {

		require(__DIR__ . '/../../common/util/simple_html_dom.php');

		$opts    = array(
			'http' => array(
				'method'  => "GET",
				'timeout' => 2,
			)
		);

		$cityArr = [
			/* 南京市 初始采集结束
			'nj'=>[
				'gulou'=>['name'=>'鼓楼','province'=>'16','city'=>'220','district'=>'1835'],
				'jianye'=>['name'=>'建邺','province'=>'16','city'=>'220','district'=>'1837'],
				'qinhuai'=>['name'=>'秦淮','province'=>'16','city'=>'220','district'=>'1838'],
				'qixia'=>['name'=>'栖霞','province'=>'16','city'=>'220','district'=>'1841'],
				'xuanwu'=>['name'=>'玄武','province'=>'16','city'=>'220','district'=>'1834'],
				'yuhuatai'=>['name'=>'雨花台','province'=>'16','city'=>'220','district'=>'1839'],
				'jiangning'=>['name'=>'江宁','province'=>'16','city'=>'220','district'=>'1843'],
				'pukou'=>['name'=>'浦口','province'=>'16','city'=>'220','district'=>'1842'],
				'liuhe'=>['name'=>'六合','province'=>'16','city'=>'220','district'=>'1844'],
				'lishui'=>['name'=>'溧水','province'=>'16','city'=>'220','district'=>'1845'],
				'gaochun'=>['name'=>'高淳','province'=>'16','city'=>'220','district'=>'1846'],
			]
			*/
		];

		$db = \Yii::$app->db;

		foreach($cityArr as $cityKey => $city) {

			foreach($city as $districtKey => $district) {

				$url ="http://{$cityKey}.lianjia.com/xiaoqu/{$districtKey}/pg1/";
				$dom = file_get_html($url);

				$total = intval($dom->find('h2.total span',0)->plaintext);
				$pageCount = ceil($total/30);

				$ret = $dom->find('li .title');

				$rows = [];
				foreach($ret as $v) {
					$thumbnail = $v->parent->parent->first_child()->children(0)->getAttribute('data-original');
					$name = trim($v->plaintext);
					$result = Yii::$app->upload->saveImgToUrl($thumbnail,'xq');
					if($result) {
						$thumbnail = $result['path'];
					} else {
						$thumbnail='';
					}
					$firstword = f_firstLetter($name);
					$rows[] = [$district['province'],$district['city'],$district['district'],$name,$thumbnail,$firstword[0]];
				}

				$db->createCommand()->batchInsert('{{hll_community}}',
					['province','city','district', 'name','thumbnail','firstletter'],$rows)->execute();

				for($i=2;$i<=$pageCount;$i++) {

					$url ="http://{$cityKey}.lianjia.com/xiaoqu/{$districtKey}/pg{$i}/";
					$dom = file_get_html($url);
					$rows = [];
					$ret = $dom->find('li .title');
					foreach($ret as $v) {
						$thumbnail = $v->parent->parent->first_child()->children(0)->getAttribute('data-original');
						$name = trim($v->plaintext);
						$result = Yii::$app->upload->saveImgToUrl($thumbnail,'xq');
						if($result) {
							$thumbnail = $result['path'];
						} else {
							$thumbnail='';
						}
						$firstword = f_firstLetter($name);
						$rows[] = [$district['province'],$district['city'],$district['district'],$name,$thumbnail,$firstword[0]];
					}
					$db->createCommand()->batchInsert('{{hll_community}}',
						['province','city','district', 'name','thumbnail','firstletter'],$rows)->execute();
				}
			}
		}
	}

	/**
	 * 初始化红包数据
	 * php yii init/redenvelope-init --id=1
	 * @author zend.wang
	 * @date  2016-10-27 13:00
	 */
	public function actionRedenvelopeInit($id) {

		$redEnvelope = (new Query())->select(['total_money','total_num'])
						->from('hll_red_envelope')
						->where(['id'=>$id,'valid'=>1])->one();

		if($redEnvelope) {

			$count = (new Query())->select(['total_money','total_num'])
				->from('hll_red_envelope_detail')
				->where(['reid'=>$id])->count();
			if($count) {
				echo "exist data\n";
				exit;
			}
			$remainSize= $redEnvelope['total_num'];
			$remainMoney = $redEnvelope['total_money']-100;
			$values=[];

			for($i=$remainSize;$i>0;$i--) {
				$result = static::getRandomMoney($i,$remainMoney);
				$remoney = 1+$result[0];
				echo "{$i}======{$remoney}\n";
				$remainMoney = $result[1];

				$values[]=['reid'=>$id,'remoney'=>$remoney];
			}
			$db = \Yii::$app->db;
			try {
				$db->createCommand()->batchInsert('{{hll_red_envelope_detail}}',
					['reid', 'remoney'],$values)->execute();
				echo" \n recalculate total money:" .array_sum(ArrayHelper::getColumn($values,'remoney'))."\n";
			}catch (\Exception $e){
				echo $e->getMessage();
			}
		}else {
			echo 'parameter id exception';
		}

	}

	/**
	 * 随机红包
	 * @author zend.wang
	 * @date  2016-10-27 13:00
	 */
	private static function getRandomMoney($remainSize,$remainMoney) {

		if($remainSize == 1) {
			return [round($remainMoney,2),0];
		}

		$min = 0.01;

		$max = $remainMoney/$remainSize*2;

		$money = round($max * f_randomFloat(),2);
		if($money<$min) $money = $min;

		return [$money,$remainMoney-$money];
	}

    public static  function actionBookimg()
    {
        //循环library book commerce表 根据 commerce_id 字段
        //调用相应的解析函数 获取商品的 缩略图和轮播图，
        //保存到对应的library_book中的thumbnail、pics字段

        $db = \Yii::$app->db;
        try {

            $bookCommerces =(new Query())->select('book_id,commerce_id,book_url')
                        ->from('hll_library_book_commerce as t1')
                        ->where(['valid'=>1,'commerce_id'=>[1,2]])->all();

            if($bookCommerces) {
                foreach($bookCommerces as $val) {
                    if(empty($val['book_url'])) {
                        echo 'data exception==book_id:'.$val['book_id'].PHP_EOL;
                        break;
                    }
                    if($val['commerce_id'] == 1) {
                        $result = static::parseDangdang($val['book_url']);
                    }else if($val['commerce_id'] == 2) {
                        $result = static::parseJd($val['book_url']);
                    }
                    if($result) {
                        $db->createCommand()->update('{{hll_library_book}}',
                            ['thumbnail'=>$result['thumbnail'],'pics'=>json_encode($result['pics'])],'id=:id',[':id'=>$val['book_id']])->execute();
                    }
                }

            }


        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 解析获取缩略图和轮播图（m.dangdang.com）
     * @param $url
     * @return array
     * @author zend.wang
     * @time 2016-11-25 15:00
     */
    private  static function parseDangdang($url) {
        $dom = file_get_html($url);
        $ret = $dom->getElementById("slider")->first_child()->children();
        $img_urls = [];
        foreach($ret as $v) {
            $img = trim($v->first_child()->innertext());
            if($img) {
                $match = [];
                if(preg_match("/<img[^>]*?src=\"([^>]+?)\"[^>]*?>/i", $img ,$match)) {
                    array_push($img_urls,$match[1]);
                } else {
                    echo "不匹配";break;
                }
            }
        }
        $pattern = '/(\w+)-(\w+).jpg/i';
        $replacement = '${1}-1_b_1.jpg';
        $thumbnail = preg_replace($pattern, $replacement, $img_urls[0]);
        return ['thumbnail'=>$thumbnail,'pics'=>$img_urls] ;
    }
    /**
     * 解析获取缩略图和轮播图（m.jd.com）
     * @param $url
     * @return array
     * @author zend.wang
     * @time 2016-11-25 15:00
     */
    private  static function parseJd($url) {
        $dom = file_get_html($url);
        $ret = $dom->getElementById("slide")->first_child()->children();
        $img_urls = [];
        $i=0;
        foreach($ret as $v) {
            $img = trim($v->innertext());
            if($img) {
                $match = [];

                if( $i && preg_match("/<img[^>]*?imgsrc=\"([^>]+?)\"[^>]*?>/i", $img ,$match)) {
                    array_push($img_urls,$match[1]);
                } else if(!$i && preg_match("/<img[^>]*?src=\"([^>]+?)\"[^>]*?>/i", $img ,$match)) {
                    array_push($img_urls,$match[1]);
                } else {
                    echo "不匹配".PHP_EOL;break;
                }
                $i++;
            }
        }
        $thumbnail = str_replace("n12", "n6", $img_urls[0]);
        return ['thumbnail'=>$thumbnail,'pics'=>$img_urls] ;
    }

    public function actionInviteOrder(){
		$data = HllCustInvite::find()->where([])->orderBy('invite_date desc , created_at asc')->all();
		$d = [];
		foreach ( $data as $v){
			$d[$v['invite_date']][] = $v;
		}

		foreach ($d as $v){
			foreach ($v as $k=>$v2){
				$v2->invite_order = $k+1;
				$v2->save();
			}
		}
	}

	/**
	 * 将现在用户的积分初始化
	 */
	public function actionPoints(){
		$users = EcsUsers::find()->select(['user_id','pay_points'])->where('pay_points>0')->asArray()->all();
		if ($users) {
			foreach ($users as $user){
				$model = new HllUserPoints();
				$model->user_id = $user['user_id'];
				$model->point  =  $user['pay_points'];
				$model->save(false);
			}

		}
		//var_dump($users);

	}
}
