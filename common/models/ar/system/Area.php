<?php

namespace common\models\ar\system;

use Yii;
use common\components\Util;

/**
 * This is the model class for table "area".
 *
 * @property integer $code
 * @property string $name
 * @property integer $type
 * @property string $displayname
 */
class Area extends \yii\db\ActiveRecord
 {
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'area';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['code', 'name', 'displayname'], 'required'],
            [['code', 'type'], 'integer'],
            [['name', 'displayname'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'code' => '行政区划代码',
            'name' => '行政区划名称',
            'type' => '行政区划类型：1省，2市，3区',
            'displayname' => '行政区划显示名称',
        ];
    }

    /*
     * 直辖市
     */
    public static $zhiXiaShi = [
        110000,//北京市
        120000,//天津市
        310000,//上海市
        500000,//重庆市
    ];
    public static function sons($code, $fields=NULL){
        if(is_null($fields))$fields = 'code,displayname';
        $code = (string)(int)$code;
        if('00' !== $code[4].$code[5])return [];//没有子级

        $where = '`type` IS NOT NULL';
        if('00' !== $code[2].$code[3]){//tpye=2级
            $wildcard = substr($code, 0, 4).'__"';
        }else{                         //type=1级
            if(in_array($code, self::$zhiXiaShi)){//直辖市需要特殊处理
                $wildcard = substr($code, 0, 2).'____"';
            }else{
                $wildcard = substr($code, 0, 2).'__00"';
            }
        }
        $where .= ' AND `code` LIKE "'.$wildcard;
        $sons = static::find()->select($fields)->where($where)
            ->asArray()->indexBy('code')->all();
        unset($sons[$code]);//删除自己
        return $sons;
    }


    /**
     * 取得某个区域的所有祖先区域
     * @return 数组
     */
    public static function parents($code, $fields=NULL, $withSelf=TRUE){
        if(is_null($fields))$fields = 'code,displayname';
        $code = (string)(int)$code;
        $parents = [];
        if('0000' === substr($code, -4)){//没有父级什么也不做

        }else{
            $parents[1] = static::find()->select($fields)
            ->where('`code`='.substr($code, 0, 2).'0000')->asArray()->one();

            if('00' !== substr($code, -2)){//type=3级
                $area = static::find()->select($fields)
                ->where('`type` IS NOT NULL AND `code`='.substr($code, 0, 4).'00')->asArray()->one();
                if($area)$parents[2] = $area;
            }
        }
        if($withSelf){//包括自己
            $parents[count($parents)+1] = static::find()->select($fields)
            ->where('`code`='.$code)->asArray()->one();
        }
        return $parents;
    }
    /**
     * 取得某个区域的所有祖先区域
     * @return 字符串
     */
    public static function parentsStr($code){
        $parents = array_column(static::parents($code, 'displayname'), 'displayname');
        return join('-', $parents).' ';
    }
}
