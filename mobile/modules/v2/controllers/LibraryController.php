<?php
namespace mobile\modules\v2\controllers;

use common\models\ar\system\QrCode;
use common\models\ecs\EcsUsers;
use common\models\ecs\EcsWechatUser;
use common\models\hll\HllLibrary;
use common\models\hll\HllLibraryBookAbnormal;
use common\models\hll\HllLibraryBookBorrow;
use common\models\hll\HllLibraryBookDonate;
use common\models\hll\HllLibraryBookInfo;
use common\models\hll\HllLibraryBookStatus;
use common\models\hll\HllLibraryCard;
use common\models\hll\HllLibraryBookCategory;
use common\models\SpiderModel;
use Yii;
use common\models\hll\HllLibraryBookCommerce;
use common\models\hll\HllLibraryBookRate;
use mobile\components\ApiController;
use mobile\modules\v2\models\ApiData;
use mobile\modules\v2\models\ApiResponse;
use yii\base\Exception;
use yii\db\Query;
use Swoole;

/**
 * 图书馆控制器
 * Created by PhpStorm.
 * User: qkk
 * Date: 2017/1/11
 * Time: 10:24
 */
class LibraryController extends ApiController
{

    //图书详情
    public function actionDetail($id)
    {

        //$id status的id
        //$info_id info的id
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $info_id = HllLibraryBookStatus::find()->select(['book_info_id'])->where(['id' => $id, 'valid' => 1])->scalar();
        $info['detail'] = HllLibraryBookInfo::getBookDetail($info_id);
        if (empty($info['detail'])) {
            $response->data = new ApiData('101', '参数id错误');
            return $response;
        }
        $info['detail']['store'] = HllLibraryBookCommerce::getBookStore($info['detail']['book_info_id']);
        $info['comment'] = HllLibraryBookRate::getBookComment($info_id);
        $info['detail']['is_borrow'] = HllLibraryBookBorrow::find()->where(['user_id' => $user_id, 'book_id' => $id, 'status' => 1])->count();
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //用户借书卡
    public function actionLibraryCard()
    {
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $info['user'] = HllLibraryCard::getCardDetail($user_id);
        if (empty($info['user'])) {
            $user_card = new HllLibraryCard();
            $user_card->user_id = $user_id;
            $user_card->borrow_limit = 1;
            if ($user_card->save()) {
                $info['user'] = HllLibraryCard::getCardDetail($user_id);
            }
        }
        $user = EcsUsers::getUser($user_id);
        $info['user']['nickname'] = $user['nickname'];
        $info['user']['headimgurl'] = $user['headimgurl'];
        $info['borrow'] = HllLibraryBookBorrow::getBorrowListByUser($user_id, 1);
        $info['back'] = HllLibraryBookBorrow::getBorrowListByUser($user_id, 2);
        $info['donate'] = HllLibraryBookInfo::getBookList(null, $user_id, 1, null);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //用户捐书码
    public function actionDonate()
    {
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $str = null;
        $strPol = "0123456789";
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < 5; $i++) {
            $str .= $strPol[rand(0, $max)];
        }
        $book_donate = new HllLibraryBookDonate();
        $book_donate->user_id = $user_id;
        $book_donate->donate_code = $str;
        if ($book_donate->save()) {
            $response->data = new ApiData();
            $response->data->info = $str;
        } else {
            $response->data = new ApiData(101, '捐书码保存失败!');
        }
        return $response;
    }

    //评价图书
    public function actionComment()
    {
        $response = new ApiResponse();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $id = Yii::$app->request->post('id');
            $book_info_id = HllLibraryBookStatus::find()->select(['book_info_id'])
                ->where(['id' => $id, 'valid' => 1])->scalar();
            $rate_star = Yii::$app->request->post('rate_star');
            $comment = Yii::$app->request->post('comment');
            $user_id = Yii::$app->user->id;
            if (empty($id) || empty($rate_star)) {
                $response->data = new ApiData('101', '缺少关键参数!');
                return $response;
            }
            $rate = new HllLibraryBookRate();
            $rate->book_info_id = $book_info_id;
            $rate->user_id = $user_id;
            $rate->rate_star = $rate_star;
            $rate->rate_comment = $comment;
            if ($rate->save()) {
                $book = HllLibraryBookInfo::findOne($book_info_id);
                $book->rate_num += 1;
                $book->rate_star += $rate_star;
                if ($book->save()) {
                    $transaction->commit();
                    $response->data = new ApiData();
                    $response->data->info = $rate;
                } else {
                    $transaction->rollback();
                    $response->data = new ApiData(104, $book->getErrors());
                }
            } else {
                $transaction->rollback();
                $response->data = new ApiData(103, $rate->getErrors());
            }
        } catch (\Exception $e) {
            $transaction->rollback();
            $response->data = new ApiData(102, '添加评论失败！');
        }
        return $response;
    }

    //书架详情
    public function actionBookshelfDetail()
    {
        $response = new ApiResponse();
        $book_id = f_get('id', 0);
        $info['book'] = (new Query())->select(['t1.id', 't1.library_id', 't2.book_name'])
            ->from('hll_library_book_status as t1')
            ->leftJoin('hll_library_book_info as t2', 't2.id = t1.book_info_id')
            ->where(['t1.id' => $book_id, 't1.valid' => 1, 't2.valid' => 1])->one();
        $info['library'] = HllLibrary::find()->select(['library_name', 'thumbnail', 'library_phone', 'library_info'])
            ->where(['id' => $info['book']['library_id'], 'valid' => 1, 'status' => 1])->asArray()->one();
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //书架列表
    public function actionBookshelfList()
    {
        $response = new ApiResponse();
        $long = f_get('long', 0);
        $lat = f_get('lat', 0);
        $user_id = Yii::$app->user->id;
        if ($lat == 0 && $long == 0) {
            $list = HllLibrary::getList();
        } else {
            $list = HllLibrary::getLibraryList($user_id, $long, $lat);
        }
        $response->data = new ApiData();
        $response->data->info = $list;
        return $response;
    }

    //书本列表
    public function actionBookList()
    {
        $response = new ApiResponse();
        $library_id = f_get('library_id', 0);
        $sort = f_get('sort', 1);
        $keyword = f_get('keyword', '0');
        $book_type = f_get('book_type', 0);
        $page = f_get('page', 0);
        $query = HllLibraryBookInfo::getBookList($library_id, null, intval($sort), $keyword, intval($book_type));
        $info = $this->getDataPage($query, $page);
        $info['book_type'] = HllLibraryBookCategory::getAllCategory();
        foreach ($info['list'] as &$item) {
            $item['category_content'] = HllLibraryBookCategory::find()->select(['name'])
                ->where(['id' => $item['book_type'], 'valid' => 1, 'is_show' => 1])->scalar();
            if (!$item['category_content']) {
                $item['category_content'] = '未分类';
            }
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //书本搜索列表
    public function actionSearchList()
    {
        $response = new ApiResponse();
        $book_name = f_get('book_name', '0');
        $book_type = f_get('book_type', 0);
        $sort = f_get('sort', 1);
        $longitude = f_get('longitude', 0);
        $latitude = f_get('latitude', 0);
        $page = f_get('page', 0);
        $query = HllLibraryBookInfo::getSearchList($book_name, intval($book_type), intval($sort), $longitude, $latitude);
        $info = $this->getDataPage($query, $page);
        $info['book_type'] = HllLibraryBookCategory::getAllCategory();
        foreach ($info['list'] as &$item) {
            $item['category_content'] = HllLibraryBookCategory::find()->select(['name'])
                ->where(['id' => $item['book_type'], 'valid' => 1, 'is_show' => 1])->scalar();
            if (!$item['category_content']) {
                $item['category_content'] = '未分类';
            }
        }
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

//    //用户借书
//    public function actionBorrowBook($qrcode){
//        $response = new ApiResponse();
//        $user_id = Yii::$app->user->id;
//        $result = HllLibraryBookBorrow::borrowBook($qrcode, $user_id);
//        if($result['code'] == 0){
//            $response->data = new ApiData();
//            $response->data->info = $result['content'];
//        }
////        else if($result['code'] == 1){
////            $this->redirect('../../library-book-bind.html?code='.$qrcode);
////        }
//        else{
//            $response->data = new ApiData($result['code'], $result['content']);
//        }
//        return $response;
//    }

    //借书异常
    public function actionBorrowError()
    {
        $response = new ApiResponse();

        $id = Yii::$app->request->post('id');
        $type = Yii::$app->request->post('type');
        $comment = Yii::$app->request->post('comment');
        $user_id = Yii::$app->user->id;
        if (empty($id) || empty($comment)) {
            $response->data = new ApiData('101', '缺少关键参数!');
            return $response;
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $rate = new HllLibraryBookAbnormal();
            $rate->book_id = $id;
            $rate->user_id = $user_id;
            $rate->abnormal_desc = $comment;
            $rate->abnormal_type = $type;
            if ($rate->save()) {
                $book = HllLibraryBookStatus::findOne($id);
                $book->status = 3;
                if ($book->save()) {
                    $transaction->commit();
                    $response->data = new ApiData();
                    $response->data->info = $rate;
                } else {
                    $response->data = new ApiData(103, '修改书本状态失败！');
                }
            } else {
                $response->data = new ApiData(104, '保存异常失败！');
            }
        } catch (\yii\db\Exception $e) {
            $transaction->rollback();
            $response->data = new ApiData(102, '提交异常失败！');
        }
        return $response;
    }

    //用户借书列表
    public function actionBorrowBookList($library_id)
    {
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;
        $info['library'] = HllLibrary::find()->select(['library_name', 'longitude', 'latitude'])
            ->where(['id' => $library_id, 'valid' => 1, 'status' => 1])->asArray()->one();
        if (!empty($info['library'])) {
            $info['list'] = HllLibraryBookBorrow::getBorrowListByUser($user_id, 1);

            if (empty($info['list'])) {
                $response->data = new ApiData('101', '没有可还的书本！');
            } else {
                $response->data = new ApiData();
                $response->data->info = $info;
            }
        } else {
            $response->data = new ApiData(102, '书架不存在');
        }
        return $response;
    }

    //用户还书
    public function actionReturnBook($library_id, $book_id)
    {
        $response = new ApiResponse();
        $user_id = Yii::$app->user->id;

        $result = HllLibraryBookBorrow::returnBook($book_id, $library_id, $user_id);
        if ($result['code'] == 0) {
            $response->data = new ApiData();
            $response->data->info = $result['content'];
        } else {
            $response->data = new ApiData($result['code'], $result['content']);
        }
        return $response;
    }

    //图书上架
    public function actionPutAway()
    {
        $response = new ApiResponse();
        $donate_code = f_get('code', '0');
        $library_id = f_get('library_id', 0);
        $qr_code = f_get('qr_code', 0);
        $isbn = f_get('isbn', 0);
        $isbn = explode(',', $isbn);
        if (empty($isbn) || empty($qr_code) || empty($library_id)) {
            $response->data = new ApiData(101, '缺少关键参数');
            return $response;
        }
        if (sizeof($isbn) != 2) {
            $response->data = new ApiData(110, '请重新扫描ISBN码！');
            return $response;
        }
        $qr_code_model = QrCode::find()->where(['qr_code' => $qr_code, 'valid' => 1, 'item_type' => 3, 'item_id' => 0])->one();
        if (!$qr_code_model) {
            $response->data = new ApiData(102, '该二维码已被绑定');
            return $response;
        }
        if ((strlen($isbn[1]) == 13 && preg_match("/^97[89]{1}\d{10}/", $isbn[1]))
            || (strlen($isbn[1]) == 10 && preg_match("/\d{9}[x0-9]+$/", $isbn[1]))
        ) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $isNew = true;
                $bookInfo = HllLibraryBookInfo::find()->where(['isbn' => $isbn, 'valid' => 1])->one();
                if ($bookInfo) {
                    $isNew = false;
                    $bookInfo->book_num += 1;
                } else {
                    $bookInfo = new HllLibraryBookInfo();
                    $bookInfo->isbn = $isbn[1];
                    $bookInfo->is_complete = 0;
                }
                if (!$bookInfo->save()) {
                    throw new Exception('更新图书信息失败', 111);
                }

                $cardInfo = HllLibraryCard::getCardChange($donate_code);
                if ($cardInfo['flag'] == 0) {
                    throw new Exception($cardInfo['user_id'], 112);
                }

                $book_status = new HllLibraryBookStatus();
                $book_status->library_id = $library_id;
                $book_status->qrcode = $qr_code;
                $book_status->status = 1;
                $book_status->user_id = $cardInfo['user_id'];
                $book_status->book_info_id = $bookInfo->id;

                if ($book_status->save()) {
                    $qr_code_model->item_id = $book_status->id;
                    $library = HllLibrary::find()->where(['valid' => 1, 'id' => $library_id])->one();
                    $library->library_book += 1;
                    if ($qr_code_model->save()) {
                        $library->save();
                        $transaction->commit();
                        if ($isNew) {
                            $openid = EcsWechatUser::findOne(['ect_uid' => Yii::$app->getUser()->getId()])->getAttribute('openid');
                            Yii::info("begin sync task params:{$isbn[1]},{$donate_code},{$library_id},{$qr_code}");
                            $client = new Swoole\Client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
                            $client->connect('127.0.0.1', 9501, 1);
                            $client->send(json_encode(['action' => 'spider-book-by-isbn', 'param' => [$isbn[1], $donate_code, $library_id, $qr_code, $openid]]));
                        }
                        $response->data = new ApiData(0, '图上上架成功');
                    } else {
                        throw new Exception('更新二维码状态失败' . json_encode($qr_code_model->getErrors()), 114);
                    }
                } else {
                    throw new Exception('创建书本状态失败' . json_encode($book_status->getErrors()), 115);
                }

            } catch (Exception $e) {
                $transaction->rollBack();
                $response->data = new ApiData($e->getCode(), '上架失败！' . $e->getMessage() . PHP_EOL . ': line:' . $e->getFile());
            }
        } else {
            $response->data = new ApiData(100, '请重新扫描ISBN码！');
            return $response;
        }
        return $response;
    }

    /**
     *
     * 更新图书馆坐标信息
     * @return ApiResponse
     * @author zend.wang
     * @time 2017-01-19 15:00
     */
    public static function actionLibraryCoordinate()
    {
        $response = new ApiResponse();

        $library_id = Yii::$app->request->post('id');
        $latitude = Yii::$app->request->post('latitude');
        $longitude = Yii::$app->request->post('longitude');

        $library = HllLibrary::findOne($library_id);

        if (empty($latitude) || empty($library) || empty($longitude)) {
            $response->data = new ApiData(101, '缺少关键参数');
        } else {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $library->id = $library_id;
                $library->latitude = $latitude;
                $library->longitude = $longitude;
                $library->updater = Yii::$app->user->id;

                if ($library->save()) {
                    $transaction->commit();
                    $response->data = new ApiData();
                    $response->data->info = $library_id;
                } else {
                    $response->data = new ApiData(103, '更新图书馆地理位置失败！');
                    return $response;
                }
            } catch (\yii\db\Exception $e) {
                $transaction->rollBack();
                $response->data = new ApiData(102, '更新图书馆地理位置异常！');
            }
        }
        return $response;
    }

    /**
     * 获取图书分类等信息
     * @return ApiResponse
     */
    public function actionGuessYouSearch()
    {
        $response = new ApiResponse();

        $info['search_list'] = HllLibraryBookInfo::$searchBook;
        $info['book_type'] = HllLibraryBookCategory::getAllCategory();
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    //创建书架
    public function actionCreateBookshelf()
    {
        $response = new ApiResponse();
        $bookshelf = array();
        $bookshelf['library_name'] = f_post('library_name', '0');
        $bookshelf['share_type'] = f_post('share_type', 0);
        $bookshelf['library_phone'] = f_post('library_phone', '0');
        $bookshelf['longitude'] = f_post('longitude', '0');
        $bookshelf['latitude'] = f_post('latitude', '0');
        $library = new HllLibrary();
        if ($library->load($bookshelf, '') && $library->save()) {
            $response->data = new ApiData();
            $response->data->info = $library->id;
        } else {
            $response->data = new ApiData(101, '创建书架失败!');
        }
        return $response;
    }

    //test
    public function actionTest()
    {
        $response = new ApiResponse();
        $isbn = '9787121247453';
        $info = SpiderModel::SpiderBookByIsbn($isbn);
        $book_info_id = HllLibraryBookInfo::setBookInfo($info, $isbn);
        $response->data = new ApiData();
        $response->data->info = $book_info_id;
        return $response;

    }
}
