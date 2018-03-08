<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_red_envelope_detail".
 *
 * @property integer $id
 * @property integer $reid
 * @property string $remoney
 * @property integer $user_id
 * @property string $taken_time
 * @property integer $send_status
 * @property integer $return_status
 * @property integer $return_point
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class RedenvelopeDetail extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_red_envelope_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['reid', 'remoney', 'taken_time'], 'required'],
            [['reid', 'user_id', 'send_status', 'return_status', 'return_point', 'creater', 'updater', 'valid'], 'integer'],
            [['taken_time', 'created_at', 'updated_at'], 'safe'],
            [['remoney'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'reid' => 'Reid',
            'remoney' => 'Remoney',
            'user_id' => 'User ID',
            'taken_time' => 'Taken Time',
            'send_status' => 'Send Status',
            'return_status' => 'Return Status',
            'return_point' => 'Return Point',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 返回所有红包的基本信息
     * @return array
     */
    public static function getAllEnvelopeId(){
        $result = (new Query())->select(['t1.reid','t2.start_time','t2.taken_num','t2.total_num'])
            ->from('hll_red_envelope_detail as t1')->distinct()
            ->leftJoin('hll_red_envelope as t2','t2.id = t1.reid')
            ->where(['t1.valid'=>1,'t2.valid'=>1,'t1.send_status'=>2])->orderBy(['t2.start_time'=>SORT_DESC])->all();
        foreach($result as &$item){
            $item['start_time'] = explode(" ",$item['start_time'])[0];
        }
        return $result;
    }
    /**
     * 根据红包Id提取所有参与人的信息
     * @param $id int 红包编号
     * @return array
     */
    public static function getLastEnvelopeDetail($id){
        $key = "redenvelope_list_{$id}";
        $cache = Yii::$app->cache;
        $data = $cache->get($key);
        if($data === false){
            $data = (new Query())->select(['t2.nickname','t2.headimgurl','t1.taken_time','t1.remoney','t1.id'])
                ->from('hll_red_envelope_detail as t1')
                ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.user_id')
                ->where(['t1.send_status'=>2,'t1.reid'=>$id])->orderBy(['t1.taken_time'=>SORT_DESC])->all();
            $most_id = (new Query())->select(['id'])->from('hll_red_envelope_detail')
                ->where(['reid'=>$id,'send_status'=>2,'valid'=>1])->orderBy(['remoney'=>SORT_DESC])->one();
            foreach($data as &$item){
                if($item['id'] == $most_id['id']){
                    $item['is_most'] = true;
                }else{
                    $item['is_most'] = false;
                }
            }
        }
        Yii::$app->cache->set($key, $data, 300);
        return $data;
    }

    public static function getEnvelopeDetail($id){
        $key = "redenvelope_list_{$id}";
        $cache = Yii::$app->cache;
        $data = $cache->get($key);
        if($data === false){
            $data = (new Query())->select(['t2.nickname','t2.headimgurl','t1.taken_time','t1.remoney','t1.id'])
                ->from('hll_red_envelope_detail as t1')
                ->leftJoin('ecs_wechat_user as t2','t2.ect_uid = t1.user_id')
                ->where(['t1.send_status'=>2,'t1.reid'=>$id])->orderBy(['t1.taken_time'=>SORT_DESC])->all();
            $most_id = (new Query())->select(['id'])->from('hll_red_envelope_detail')
                ->where(['reid'=>$id,'send_status'=>2,'valid'=>1])->orderBy(['remoney'=>SORT_DESC])->one();
            foreach($data as &$item){
                if($item['id'] == $most_id['id']){
                    $item['is_most'] = true;
                }else{
                    $item['is_most'] = false;
                }
            }
        }
        Yii::$app->cache->set($key, $data);
        return $data;
    }
}
