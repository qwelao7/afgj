<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_event_hcho_detail".
 *
 * @property string $id
 * @property integer $hcho_id
 * @property string $check_point
 * @property double $check_value
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventHchoDetail extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_event_hcho_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['hcho_id', 'check_point', 'check_value'], 'required'],
            [['hcho_id', 'creater', 'updater', 'valid'], 'integer'],
            [['check_value'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['check_point'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'hcho_id' => 'Hcho ID',
            'check_point' => 'Check Point',
            'check_value' => 'Check Value',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 添加检测结果
     * @param $id
     * @param $hcho_data
     * @return int
     * @throws \yii\db\Exception
     */
    public static function setHchoDetail($id,$hcho_data,$user_id){
        $command = Yii::$app->db;
        $data = [];
        foreach($hcho_data as $item){
            $hcho_detail['hcho_id'] = $id;
            $hcho_detail['check_point'] = $item['name'];
            $hcho_detail['check_value'] = $item['number'];
            $hcho_detail['is_ok'] = $item['number'] < 0.062 ? 1 : 0;
            $hcho_detail['creater'] = $user_id;
            $hcho_detail['created_at'] = date("Y-m-d H:i:s");
            array_push($data,$hcho_detail);
        }
        $fields = ['hcho_id','check_point','check_value','is_ok','creater','created_at'];
        $result = $command->createCommand()->batchInsert(static::tableName(),$fields,$data)->execute();
        return $result;
    }

    /**
     * 获取检测信息
     * @param $id
     * @return array
     */
    public static function getHchoDetail($id){
        $detail = (new Query())->select(['check_point','check_value'])
            ->from('hll_event_hcho_detail')->where(['hcho_id'=>$id,'valid'=>1])->all();
        if(!$detail){
            return [];
        }
        foreach($detail as &$item){
            $item['level'] = $item['check_value'] < 0.062 ?  1 : 0;
        }
        return $detail;
    }

    /**
     * 获取检测列表
     * @return $this
     */
    public static function getHchoDetailList(){
        $data = HllEventHcho::find()->select(['address_id as address','content','pics','created_at','id','account_id'])
            ->where(['valid'=>1])->orderBy(['created_at'=>SORT_DESC])->asArray();
        return $data;
    }

    /**
     * 获取统计信息
     * @return mixed
     */
    public static function getHchoStatistics(){
        $statistics['hcho_num'] = (new Query())->from('hll_event_hcho')
            ->where(['valid'=>1])->count();
        $statistics['hcho_detail_num'] = (new Query())->from('hll_event_hcho_detail')
            ->where(['valid'=>1])->count();
        $statistics['perfect'] = (new Query())->from('hll_event_hcho_detail')
            ->where(['is_ok'=>1, 'valid'=>1])->count();
        $statistics['not_perfect'] = $statistics['hcho_detail_num'] - $statistics['perfect'];
        return $statistics;
    }

    /**
     * 获取合格的检测数
     * @param $id
     * @return mixed
     */
    public static function getCheckPoint($id){
        $point['whole_num'] = (new Query())->from('hll_event_hcho_detail')
            ->where(['hcho_id'=>$id,'valid'=>1])->count();
        $point['ok_num'] = (new Query())->from('hll_event_hcho_detail')
            ->where(['hcho_id'=>$id,'is_ok'=>1,'valid'=>1])->count();
        return $point;
    }
}
