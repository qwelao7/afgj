<?php
namespace console\controllers;


use Yii;
use yii\base\Object;
use yii\console\Controller;

use common\models\hll\HllLibraryBookInfo;
use common\models\hll\HllLibraryBookStatus;
use common\models\hll\HllLibraryCard;
use common\models\ar\system\QrCode;
use common\models\hll\HllLibrary;
use common\models\SpiderModel;
class AsyncTaskController extends Controller{

    /**
     * 数据抓取入库及保存借阅状态信息
     * @param $isbn
     * @param $donate_code
     * @param $library_id
     * @param $qr_code
     * @return int
     * @author zend.wang
     * @time 2017-03-21 15:00
     */
	public function actionSpiderBookByIsbn($isbn,$donate_code,$library_id,$qr_code,$open_id){

        try {
            $time = date("Y-m-d H:i:s", time());
            echo "{$time} begin spider book : isbn={$isbn} donate_code={$donate_code} library_id={$library_id} qr_code={$qr_code}" . PHP_EOL;

            Yii::$app->db->open();
            $book_store = SpiderModel::SpiderBookByIsbn($isbn);
            echo PHP_EOL;
            print_r($book_store);
            echo PHP_EOL;
            if(!$book_store['res']) {
                echo "spider book exception:{$book_store['msg']}" . PHP_EOL;
				Yii::$app->wechat->sendBookError($isbn,$open_id);
				return 0;
            }
            HllLibraryBookInfo::setBookInfo($book_store, $isbn);
        }catch (\Exception $e){
            echo $e->getFile().PHP_EOL;
            echo $e->getTraceAsString().PHP_EOL;
            echo $e->getMessage().PHP_EOL;
        }
        Yii::$app->db->close();
        $time = date("Y-m-d H:i:s", time());
        echo "{$time} end spider book";
	}

    public function actionDemo($a,$b){
        echo $a.PHP_EOL;;
        echo $b.PHP_EOL;;
        echo 'zhangsan'.PHP_EOL;
    }
}
