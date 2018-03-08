<?php

namespace common\models\hll;

use Yii;
use yii\caching\DbDependency;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_red_envelope".
 *
 * @property integer $id
 * @property string $title
 * @property integer $retype
 * @property string $total_money
 * @property integer $total_num
 * @property string $start_time
 * @property string $end_time
 * @property integer $share_return
 * @property integer $return_point
 * @property integer $taken_num
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class Redenvelope extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_red_envelope';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['retype', 'total_num', 'share_return', 'return_point', 'taken_num', 'creater', 'updater', 'valid'], 'integer'],
            [['total_money', 'start_time', 'end_time'], 'required'],
            [['total_money'], 'number'],
            [['start_time', 'end_time', 'created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'retype' => 'Retype',
            'total_money' => 'Total Money',
            'total_num' => 'Total Num',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'share_return' => 'Share Return',
            'return_point' => 'Return Point',
            'taken_num' => 'Taken Num',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 获取当前可抢的红包信息
     * @param $id
     * @return array|bool|mixed|null|\yii\db\ActiveRecord
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public static  function getRedenvelope()
    {
        $key = "current_redenvelope";
        $cache = Yii::$app->cache;
        $data = $cache->get($key);

        if ($data === false) {

            $currentDate = f_date(time());

            $data = static::find()->select(['id','title','wishing','retype','total_money',
                    'total_num','start_time','end_time','share_return','return_point'])
                    ->where(["valid" => 1])
                    ->andWhere(['<','start_time',$currentDate])
                    ->andWhere(['>','end_time',$currentDate])
                    ->asArray()->one();

            if (!$data) {
                $data = static::find()->select(['id','title','wishing','retype','total_money',
                    'total_num','start_time','end_time','share_return','return_point'])
                    ->where(["valid" => 1])
                    ->andWhere(['>=','start_time',$currentDate])
                    ->orderBy('start_time ASC')
                    ->asArray()->one();
                if(!$data){ return false;}
            }
            $endTime = strtotime($data['end_time']);
            $duration = $endTime - time();
            $data['endTime'] = $endTime;
            $data['startTime'] = strtotime($data['start_time']);
            Yii::$app->cache->set($key, $data, $duration);
        }
        return $data;
    }

    public static function getJoinNumByReId($id) {
        $key = "redenvelope_join_num_{$id}";
        $redis = Yii::$app->redis;
        $count = $redis->get($key);
        if ($count === false) {
            $count = static::find()->select(['taken_num'])->where(["id" => $id])->asArray()->one();
            $redis->set($key, $count);
        }
        return $count;
    }
    public static function join($userId) {

        $redis = Yii::$app->redis;

        $redEnvelopeData = static::getRedenvelope();
        $key = "redenvelope_join_num_{$redEnvelopeData['id']}";
        $join_hash_key = "redenvelope_join_hash_{$redEnvelopeData['id']}";
        $count = $redis->get($key);
        $currTime = time();
        if($redis->hexists($join_hash_key,$userId)) {
            return [1,'请勿重复操作'];
        } else if($currTime < $redEnvelopeData['startTime']) {
            return [6,'活动尚未开始'];
        } else if($currTime > $redEnvelopeData['endTime']) {
            return [7,'活动已结束'];
        } else if($count == $redEnvelopeData['total_num']) {
            return [2,'红包已抢完'];
        }
        $db =  Yii::$app->db;
        $trans =$db->beginTransaction();
        try {
            $detailId = RedenvelopeDetail::find()->select(['id'])->where(["reid" => $redEnvelopeData['id'],'user_id'=>0,'valid'=>1])->orderBy('id DESC')->scalar();

            $effectedRows = $db->createCommand()->update('hll_red_envelope_detail', ['user_id' => $userId], ['id'=>$detailId])->execute();
            Yii::warning("{$effectedRows} token num:{$userId} detailId:{$detailId}");
            if($effectedRows) {
                $result = Yii::$app->wechat->sendRedToUser($detailId);
                if($result['res'] == 'SUCCESS') {
                    $redis->incr($key);
                    Yii::warning("join success update token num:{$userId} detailId:{$detailId}");
                    $redis->hset($join_hash_key,$userId,$detailId);
                    $db->createCommand()->setSql("UPDATE hll_red_envelope SET taken_num=taken_num+1 WHERE id={$redEnvelopeData['id']} ")->execute();
                    //$db->createCommand()->update('hll_red_envelope_detail', ['user_id' => $userId], ['id'=>$detailId])->execute();
                } else {
                    Yii::warning("send failed res:{$result['res']} msg:{$result['msg']}");
                    $trans->rollBack();
                    return [5,'手气不佳,不要泄气!'];
                }
                Yii::warning("join success :{$userId} detailId:{$detailId}");
                $trans->commit();
                return [0,'ok'];
            }else {
                Yii::warning('join update failed:'.$userId);
                return [3,'手气不佳,不要泄气!'];
            }
        }catch (\yii\db\Exception $e) {
            $trans->rollBack();
            Yii::warning('join exception:'.print_r($e));
            return [4,'手气不佳,不要泄气!'];
        }
    }
}
