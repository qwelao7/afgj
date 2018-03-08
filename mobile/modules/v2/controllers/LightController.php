<?php
namespace mobile\modules\v2\controllers;

use common\models\ecs\EcsUserAddress;
use common\models\ecs\EcsUsers;
use common\models\hll\HllEventLight;
use common\models\hll\HllEventLightDetail;
use mobile\components\ApiController;
use Yii;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use yii\base\Exception;

class LightController extends ApiController
{
    /**
     * Created by PhpStorm.
     * User: nancy
     * Date: 2017/4/19
     * Time: 14:46
     */

    /**
     * 甲醛检测列表
     * @return ApiResponse
     */
    public function actionIndex()
    {
        $response = new ApiResponse();

        $page = f_get('page', 1);

        $query = HllEventLightDetail::getLightDetailList();
        $info = $this->getDataPage($query, $page);
        if ($info['list']) {
            foreach ($info['list'] as &$item) {
                $item['pics'] = (!empty($item['pics'])) ? explode(',', $item['pics']) : [];
                $item['detail'] = HllEventLightDetail::getLightDetail($item['id']);
                $item['user'] = EcsUsers::getUser($item['account_id'], ['t2.nickname', 't2.headimgurl']);
                $item['address'] = EcsUserAddress::getAddressDesc($item['address']);
            }
        }
        $info['statistics'] = HllEventLightDetail::getLightStatistics();
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    /**
     * 添加反馈结果
     * @return ApiResponse
     * @throws \yii\db\Exception
     */
    public function actionFeedback()
    {
        $response = new ApiResponse();

        $user_id = Yii::$app->user->id;
        $data = f_post('point', '0');
        $address_id = f_post('address_id', 0);
        $content = f_post('content', 0);
        $pic = f_post('pic', 0);
        if ($data == '0' || $address_id == 0) {
            $response->data = new ApiData(101, '缺少数据!');
            return $response;
        }
        $trans = Yii::$app->db->beginTransaction();
        try {
            $light = new HllEventLight();
            $light->account_id = $user_id;
            $light->address_id = $address_id;
            $light->content = $content;
            $light->pics = $pic;
            if ($light->save()) {
                $result = HllEventLightDetail::setLightDetail($light->id, $data, $user_id);
                if ($result > 0) {
                    $point = HllEventLightDetail::getCheckPoint($light->id);
                    $light->check_light_num = $point['light_detail_num'];
                    $light->check_good_num = $point['perfect'];
                    $light->check_fine_num = $point['fine'];
                    $light->check_bad_num = $point['bad'];
                    if ($light->save()) {
                        $trans->commit();
                        $response->data = new ApiData(0, '添加成功');
                        $response->data->info = $light->id;
                    } else {
                        throw new Exception($light->getErrors(), 104);
                    }
                } else {
                    throw new Exception('检测数据添加失败', 102);
                }
            } else {
                throw new Exception($light->getErrors(), 103);
            }
        } catch (Exception $e) {
            $trans->rollBack();
            $response->data = new ApiData($e->getCode(), $e->getMessage());
        }

        return $response;
    }

    /**
     * 获取检测详情
     * @param $id
     * @return ApiResponse
     */
    public function actionLightDetail($id)
    {
        $response = new ApiResponse();

        $userId = Yii::$app->user->id;
        $info = HllEventLight::find()->select(['address_id as address', 'content', 'pics', 'created_at', 'id', 'account_id'])
            ->where(['id' => $id, 'valid' => 1])->asArray()->one();
        if (!$info) {
            $response->data = new ApiData(101, '数据错误');
            return $response;
        }
        $info['pics'] = (!empty($info['pics'])) ? explode(',', $info['pics']) : [];
        $info['detail'] = HllEventLightDetail::getLightDetail($info['id']);
        $info['user'] = EcsUsers::getUser($info['account_id'], ['t2.nickname', 't2.headimgurl']);
        $info['address'] = EcsUserAddress::getAddressDesc($info['address']);
        $info['mine'] = EcsUsers::getUser($userId, ['t2.headimgurl']);
        $response->data = new ApiData();
        $response->data->info = $info;
        return $response;
    }

    public function actionTest(){

        $response = new ApiResponse();
        $code = 'photo0001';
        $result = Yii::$app->wechat->getQrcode()->forever($code);
        $response->data = new ApiData();
        $response->data->info = $result;
        return $response;
    }
}