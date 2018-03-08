<?php

namespace common\models\hll;

use Yii;
use yii\db\Query;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_event_light_detail".
 *
 * @property string $id
 * @property integer $light_id
 * @property string $light_name
 * @property string $light_pic
 * @property integer $cct
 * @property integer $cqs
 * @property integer $lux
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllEventLightDetail extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_event_light_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['light_id', 'light_name','cct', 'cqs', 'lux'], 'required'],
            [['light_id', 'cct', 'cqs', 'lux', 'creater', 'updater', 'valid'], 'integer'],
            [['created_at', 'updated_at','light_pic'], 'safe'],
            [['light_name'], 'string', 'max' => 50],
            [['light_pic'], 'string', 'max' => 100],
        ];
    }

    public static $score_level = [0=>"不佳",1=>"良好",2=>"优秀"];
    public static $light_level = [0=>3,1=>3,2=>3, 3=>2,4=>2, 5=>1,6=>1];

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'light_id' => 'Light ID',
            'light_name' => 'Light Name',
            'light_pic' => 'Light Pic',
            'cct' => 'Cct',
            'cqs' => 'Cqs',
            'lux' => 'Lux',
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
     * @param $light_data
     * @return int
     * @throws \yii\db\Exception
     */
    public static function setLightDetail($id, $light_data,$user_id){
        $command = Yii::$app->db;
        $data = [];
        foreach($light_data as $item){
            $light_detail['light_id'] = $id;
            $light_detail['light_name'] = $item['name'];
            $light_detail['cct'] = $item['cct'];
            $light_detail['cct_score'] = static::getCctLevel($item['cct']);
            $light_detail['cqs'] = $item['cqs'];
            $light_detail['cqs_score'] = static::getCqsLevel($item['cqs']);
            $light_detail['lux'] = $item['lux'];
            $light_detail['lux_score'] = static::getLuxLevel($item['lux']);
            $light_detail['light_score'] = $light_detail['cct_score'] + $light_detail['cqs_score'] + $light_detail['lux_score'];
            $light_detail['creater'] = $user_id;
            $light_detail['created_at'] = date("Y-m-d H:i:s");
            array_push($data,$light_detail);
        }
        $fields = ['light_id','light_name','cct','cct_score',
            'cqs','cqs_score','lux', 'lux_score','light_score','creater','created_at'];
        $result = $command->createCommand()->batchInsert(static::tableName(),$fields,$data)->execute();
        return $result;
    }

    /**
     * 获取检测信息
     * @param $id
     * @return array
     */
    public static function getLightDetail($id){
        $detail = (new Query())->select(['light_name','cct','cqs','lux',
            'cct_score','cqs_score','lux_score','light_score'])
            ->from('hll_event_light_detail')->where(['light_id'=>$id,'valid'=>1])->all();
        if(!$detail){
            return [];
        }
        foreach($detail as &$item){
            $item['cct_score'] = static::$score_level[$item['cct_score']];
            $item['cqs_score'] = static::$score_level[$item['cqs_score']];
            $item['lux_score'] = static::$score_level[$item['lux_score']];
            $item['light_score'] = static::$light_level[$item['light_score']];
        }
        return $detail;
    }

    /**
     * 获取检测列表
     * @return $this
     */
    public static function getLightDetailList(){
        $data = HllEventLight::find()->select(['address_id as address','content','pics','created_at','id','account_id'])
            ->where(['valid'=>1])->orderBy(['created_at'=>SORT_DESC])->asArray();
        return $data;
    }

    /**
     * 获取色温值等级
     * @param $cct
     * @return array
     */
    public static function getCctLevel($cct){
        if($cct>5000 || $cct<2500){
            $result = 0;
        }else if(3500<$cct && $cct<4500){
            $result = 2;
        }else{
            $result = 1;
        }
        return $result;
    }

    /**
     * 获取光照度等级
     * @param $lux
     * @return array
     */
    public static function getLuxLevel($lux){
        $result = '';
        if($lux>200 || $lux<800){
            $result = 0;
        }else if(300<$lux && $lux<500){
            $result = 2;
        }else{
            $result = 1;
        }
        return $result;
    }

    /**
     * 获取光色品质等级
     * @param $cqs
     * @return array
     */
    public static function getCqsLevel($cqs){
        $result = '';
        switch(true){
            case $cqs>90:
                $result = 2;
                break;
            case $cqs<80:
                $result = 0;
                break;
            default:
                $result = 1;
                break;
        }
        return $result;
    }

    /**
     * 获取统计信息
     * @return mixed
     */
    public static function getLightStatistics(){
        $statistics['light_num'] = (new Query())->from('hll_event_light')
            ->where(['valid'=>1])->count();
        $statistics['light_detail_num'] = (new Query())->from('hll_event_light_detail')
            ->where(['valid'=>1])->count();
        $statistics['perfect'] = (new Query())->from('hll_event_light_detail')
            ->where(['>','light_score',4])->andWhere(['valid'=>1])->count();
        $statistics['bad'] = (new Query())->from('hll_event_light_detail')
            ->where(['<','light_score',3])->andWhere(['valid'=>1])->count();
        $statistics['fine'] = $statistics['light_detail_num'] - $statistics['perfect'] - $statistics['bad'];
        return $statistics;
    }

    /**
     * 获取合格的检测数
     * @param $id
     * @return mixed
     */
    public static function getCheckPoint($id){
        $point['light_detail_num'] = (new Query())->from('hll_event_light_detail')
            ->where(['light_id'=>$id,'valid'=>1])->count();
        $point['perfect'] = (new Query())->from('hll_event_light_detail')
            ->where(['light_id'=>$id,'valid'=>1])->andWhere(['>','light_score',4])->count();
        $point['bad'] = (new Query())->from('hll_event_light_detail')
            ->where(['light_id'=>$id,'valid'=>1])->andWhere(['<','light_score',3])->count();
        $point['fine'] = $point['light_detail_num'] - $point['perfect'] - $point['bad'];
        return $point;
    }
}
