<?php

namespace common\models\ar\service;

use Yii;
use common\components\Util;

/**
 * This is the model class for table "catalog".
 *
 * @property string $id
 * @property string $name
 * @property string $logo
 * @property integer $parent_id
 * @property string $class_suffix
 * @property string $x_path
 * @property string $description
 */
class Catalog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'catalog';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id'], 'integer'],
            [['description'], 'string'],
            [['name', 'class_suffix'], 'string', 'max' => 100],
            [['logo'], 'string', 'max' => 200],
            [['x_path'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fixed_catalog' => '固定分类',
            'name' => '分类名称',
            'logo' => 'LOGO',
            'parent_id' => '父分类',
            'class_suffix' => 'Class Suffix',
            'x_path' => 'XPATH路径',
            'description' => 'Description',
        ];
    }
    
    /**
     * 关联服务
     * @return type
     */
    public function getService() {
        return $this->hasMany(Service::className(), ['catalog_id' => 'id']);
    }


    const PATH_SEPARATOR = ',';//x_path所用的分隔符
    public static function tree($indexByID = TRUE){
        $raw = static::find()->select('id,fixed_catalog,name,parent_id,x_path')->indexBy('id')->asArray()->all();
        foreach($raw as $id=>&$m){
            $m['parents'] = Util::advExplode($m['x_path'], [
                'separate' => self::PATH_SEPARATOR,
                'trim_charlist' => self::PATH_SEPARATOR,
                'no_empty' => FALSE
            ]);
            array_pop($m['parents']);
            if(0 == $m['parent_id'])continue;//第一层菜单不需要移动
            $key  = '$raw';
            foreach($m['parents'] as $p){
                if('0' == $p || !is_numeric($p))continue;
                $key .= '['.$p.']["children"]';
            }
            eval($key.'[$id] = $m;unset($raw[$id]);');
        }
        if(!$indexByID)$raw = static::numIndex($raw);
        return $raw;
    }
    public static function numIndex(&$data){
        foreach($data as $k=>&$v){
            if(is_array($v) && !empty($v['children'])){
                $v['children'] = static::numIndex($v['children']);
            }
        }
        return array_values($data);
    }


    /**
     * 简单的select使用的树
     * @return array
     */
    public static function selectTree(){
        $top = [
            'id' => 0,
            'name' => '顶级分类',
            'depth' => 0
        ];
        $raw = static::find()
            ->select('id,fixed_catalog,name,parent_id,x_path')
            ->indexBy('id')->orderBy('x_path')->asArray()->all();
        foreach($raw as &$ra){
            $ra['depth'] = substr_count($ra['x_path'], self::PATH_SEPARATOR)+1;
            $ra['name'] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $ra['depth']).$ra['name'];
        }
        array_unshift($raw, $top);
        return $raw;
    }

    /*
     * 获取父级分类
     */
    public function getParent(){
        return $this->hasOne(static::className(), ['id'=>'parent_id']);
    }

    /*
     * 获取子级分类
     */
    public function getSons(){
        return $this->hasOne(static::className(), ['parent_id'=>'id']);
    }
}
