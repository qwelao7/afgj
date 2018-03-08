<?php

namespace common\models\ar\admin;

use Yii;
use common\components\Util;

/**
 * This is the model class for table "admin_menu".
 *
 * @property string $id
 * @property string $name
 * @property string $route
 * @property string $father_id
 * @property string $path
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 */
class AdminMenu extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'menu';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['father_id', 'creater', 'updater'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 20],
            [['route'], 'string', 'max' => 80],
            [['path'], 'string', 'max' => 50],
            ['route', 'validRoute'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'name' => '名称',
            'route' => '路由',
            'father_id' => '父节点',
            'path' => '所有祖先类的id',
            'creater' => '创建者id',
            'created_at' => '创建时间',
            'updater' => '更新者id',
            'updated_at' => '更新时间',
        ];
    }


    const PATH_SEPARATOR = '-';//path所用的分隔符
    public static function tree($indexByID = FALSE,$field=NULL, $raw=NULL){
        if(is_null($field))$field='id,name,route,father_id,path';
        if(is_null($raw))$raw = static::find()->select($field)->indexBy('id')->asArray()->all();
        foreach($raw as $id=>&$m){
            $m['parents'] = Util::advExplode($m['path'], [
                'separate' => self::PATH_SEPARATOR,
                'trim_charlist' => self::PATH_SEPARATOR,
                'no_empty' => TRUE
            ]);
            array_pop($m['parents']);
            if(0 == $m['father_id'])continue;//第一层菜单不需要移动
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
     * 初始化权限的菜单树
     * @param unknown $role
     * @param string $indexByID
     * @param unknown $field
     */
    public static function treeWithRolePermission($role, $indexByID = FALSE,$field=NULL, $allOpen=TRUE){
        if(is_null($field))$field='id,name,route,father_id,path';
        $permissions = Yii::$app->authManager->getPermissionsByRole($role);
        $tree = static::find()->select($field)->indexBy('id')->asArray()->all();
        foreach($tree as $id=>&$tre){
            $tre['access'] = $tre['checked'] = (bool)$permissions[$id];
            $tre['open'] = $allOpen;
        }
        return static::tree(FALSE, NULL, $tree);
    }

    /**
     * 简单的select使用的树
     * @return array
     */
    public static function selectTree($field='id,name,path'){
        $top = [
            'id' => 0,
            'name' => '顶级分类',
            'depth' => 0
        ];
        $raw = static::find()
        ->select($field)
        ->indexBy('id')->orderBy('path')->asArray()->all();
        foreach($raw as &$ra){
            $ra['depth'] = substr_count(trim($ra['path'], self::PATH_SEPARATOR), self::PATH_SEPARATOR)+1;
            $ra['name'] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $ra['depth']).$ra['name'];
        }
        array_unshift($raw, $top);
        return $raw;
    }


    /*
     * 获取父级分类
     */
    public function getFather(){
        return $this->hasOne(static::className(), ['id'=>'father_id']);
    }
    
    /*
     * 获取子级分类
     */
    public function getSons(){
        return $this->hasOne(static::className(), ['father_id'=>'id']);
    }


    /**
     * 验证route
     * @param unknown $route
     */
    public function validRoute($attribute, $params){
        list($controller, $action) = array_map('trim', Util::advExplode($this->$attribute,['separate'=>'/','unique'=>FALSE]));
        if(empty($controller)){
             list($controller) = array_map('trim', Util::advExplode(Yii::$app->defaultRoute,['separate'=>'/','unique'=>FALSE]));
        }
        if(empty($action))$action = trim(Yii::$app->controller->defaultAction);
        $this->$attribute = $controller.'/'.$action;
    }
}
