<?php

namespace common\models\ar\fang;

use common\models\ar\community\CommunityAdmin;
use Yii;
use yii\db\Query;
use common\models\ar\system\Area;
use common\components\Util;
use common\models\ar\user\Account;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseArrayHelper;
use common\models\ar\user\AccountAddress;
use common\models\ar\fang\FangHouse;

/**
 * This is the model class for table "fang_loupan".
 *
 * @property string $id
 * @property string $tag
 * @property string $thumbnail
 * @property string $bannerpic
 * @property string $pics
 * @property text $rich_text
 * @property string $name
 * @property string $developer
 * @property integer $status
 * @property string $avg_price
 * @property string $sell_date
 * @property string $delivery_date
 * @property string $area_id
 * @property string $address
 * @property string $sell_address
 * @property integer $building_type
 * @property integer $property_rights_years
 * @property integer $decorate_level
 * @property integer $decorate_price
 * @property string $decorate_brief
 * @property string $plot_ratio
 * @property integer $green_ratio
 * @property integer $house_total
 * @property integer $parking_total
 * @property string $property_type
 * @property string $proprety_company
 * @property string $property_fee
 * @property integer $heating_type
 * @property string $water_electric_gas
 * @property string $cover_area
 * @property string $hot_line 
 * @property string $wx_qr_code
 * @property string $sort
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 */
class FangLoupan extends \yii\db\ActiveRecord
 {
    
    public static $tagText = [//德系精装、双地铁、优质学区、智慧园区、绿城物业
        1 =>  ['name' => '地铁','class' => 'tag-color-1'],
        2 =>  ['name' => '优质学区','class' => 'tag-color-2'],
        3 =>  ['name' => '精装','class' => 'tag-color-3'],
        4 =>  ['name' => '别墅','class' => 'tag-color-4'],
        5 =>  ['name' => '德系精装','class' => 'tag-color-1'],
        6 =>  ['name' => '双地铁','class' => 'tag-color-2'],
        7 =>  ['name' => '智慧园区','class' => 'tag-color-3'],
        8 =>  ['name' => '绿城物业','class' => 'tag-color-4'],
        9 =>  ['name' => '绿城作品','class' => 'tag-color-1'],
        10 => ['name' => '优质景观','class' => 'tag-color-2'],
        11 => ['name' => '区域中心','class' => 'tag-color-3'],
        12 => ['name' => '三龙聚首','class' => 'tag-color-4'],
        13 => ['name' => '城市中心','class' => 'tag-color-1'],
        14 => ['name' => '独栋','class' => 'tag-color-2'],
        15 => ['name' => '豪宅','class' => 'tag-color-3'],
        16 => ['name' => '养老','class' => 'tag-color-4'],
        17 => ['name' => '中式别墅','class' => 'tag-color-1'],
        18 => ['name' => '合院别墅','class' => 'tag-color-2'],
        19 => ['name' => '低密度','class' => 'tag-color-4'],
    ];
    
    public static $statusText = [
        1 => ['name' => '待售'],
        2 => ['name' => '在售'],
        3 => ['name' => '交付'],
        4 => ['name' => '下线'],
    ];
    
    public static $buildingTypeText = [
        1 => ['name' => '板楼'],
        2 => ['name' => '塔楼'],
        3 => ['name' => '平板'],
    ];
    
    public static $decorateLevel = [
        1 => ['name' => '毛坯'],
        2 => ['name' => '精装'],
    ];
    
    public static $propertyType = [
        1 => ['name' => '普通住宅'],
        2 => ['name' => '公寓'],
        3 => ['name' => '花园式洋房'],
        4 => ['name' => '别墅'],
        5 => ['name' => '写字楼'],
        6 => ['name' => '商铺'],
        7 => ['name' => '商住两用'],
        8 => ['name' => '平墅'],
        9 => ['name' => '叠墅'],
    ];
    
    public static $heatingType = [
        1 => ['name' => '热电厂供暖'],
        2 => ['name' => '区域锅炉房供暖'],
        3 => ['name' => '集中供暖'],
        4 => ['name' => '无'],
    ];


    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'fang_loupan';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['status', 'avg_price', 'area_id', 'building_type', 'property_rights_years', 'decorate_level', 'decorate_price', 'green_ratio', 'house_total', 'parking_total', 'property_type', 'heating_type', 'sort', 'plot_ratio', 'property_fee'],'required'],
            [['status', 'area_id', 'building_type', 'property_rights_years', 'decorate_level', 'decorate_price', 'green_ratio', 'house_total', 'parking_total', 'heating_type', 'sort'], 'integer'],
            [['bannerpic', 'pics'], 'string', 'max' => 5000],
            [['plot_ratio', 'property_fee'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['tag', 'hot_line'], 'string', 'max' => 30],
            [['thumbnail'], 'string', 'max' => 100],
            [['name'], 'string', 'max' => 15],
            [['developer', 'sell_date', 'delivery_date', 'address', 'sell_address', 'proprety_company', 'water_electric_gas'], 'string', 'max' => 25],
            [['decorate_brief'], 'string', 'max' => 1000],
            [['cover_area'], 'string', 'max' => 20],
            ['property_type', 'validatePropertyType']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => 'ID',
            'tag' => '楼盘标签',
            'thumbnail' => '缩略图',
            'bannerpic' => '楼盘横幅图片',
            'pics' => '楼盘图片集',
            'rich_text' => '楼盘富文本',
            'name' => '名称',
            'developer' => '开发商',
            'status' => '状态',
            'avg_price' => '参考均价',
            'sell_date' => '最新开盘时间',
            'delivery_date' => '最新交房时间',
            'area_id' => '区域',
            'address' => '楼盘地址',
            'sell_address' => '售楼处地址',
            'building_type' => '建筑类型',
            'property_rights_years' => '产权年限（单位：年）',
            'decorate_level' => '装修标准',
            'decorate_price' => '装修价格（单位：元每平米）',
            'decorate_brief' => '装修说明',
            'plot_ratio' => '容积率（单位：%）',
            'green_ratio' => '绿化率（单位：%）',
            'house_total' => '规划户数',
            'parking_total' => '规划车位',
            'property_type' => '物业类型',
            'proprety_company' => '物业公司',
            'property_fee' => '物业费（单位：元每平米每月）',
            'heating_type' => '供暖方式',
            'water_electric_gas' => '水电燃气',
            'cover_area' => '楼盘占地面积',
            'hot_line' => '热线',
            'wx_qr_code' => '在线咨询的二维码',
            'sort' => '展示顺序（填写整数，数值越小，展示越靠前）',
            'creater' => '创建者',
            'created_at' => '创建时间',
            'updater' => '更新者',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 验证物业类型
     * @param string $attribute
     */
    public function validatePropertyType($attribute){
        if(empty($this->property_type))return;
        $this->property_type = join(',', $this->property_type);
    }

    /**
     * 处理表单提交的tag
     */
    public function handleTag(){
        if(is_array($this->tag))$this->tag = join(',',$this->tag);
    }

    /**
     * 解析property_type字段
     * @param string $types
     */
    public static function parsePropertyType($types){
        $types = Util::advExplode($types);

        foreach($types as &$type){
            $type = static::$propertyType[$type]['name'];
        }
        return $types;
    }
    /**
     * 解析property_type字段
     * @param string $types
     */
    public static function getPropertyTypeName($types){
        $result="";
        if($types) {
            $types = Util::advExplode($types);
            $types = array_map(function($val){
                return static::$propertyType[$val]['name'];
            },$types);
            $result = join(' ',$types);
        }
        return $result;
    }
    /**
     * 关联area表
     */
    public function getArea() {
        return $this->hasOne(Area::className(), ['code'=>'area_id']);
    }

    /**
     * 根据用户id得到用户房产所在的楼盘
     * 管家用户可以获取管理楼盘
     * @param $accountId 用户ID
     * @param $adminId 管家ID
     * @param string $indexBy 索引字段
     * @param string $fields 查询字段
     * @param string $order 排序字段
     * @return array 楼盘列表 例如: [1=>['id'=>1,'name'=>'xxxx'],...]
     * @author zend.wang
     * @date  2016-06-12 13:00
     */
    public static function getLoupansByAccountID($accountId, $adminId=0,$indexBy='id',$fields='fang_loupan.id,fang_loupan.name', $order='account_address.is_default'){

        $loupans = FangLoupan::find()->join('INNER JOIN', 'account_address', 'account_address.loupan_id=fang_loupan.id')
            ->join('INNER JOIN', 'fang_house', 'fang_house.loupan_id=fang_loupan.id and account_address.house_id=fang_house.id' )
            ->where('account_address.valid = 1 and account_address.house_id > 0')
            ->andWhere(['account_address.account_id'=>$accountId])
//            ->andWhere('fang_loupan.status != 4')
            ->select($fields)
            ->indexBy($indexBy)
            ->orderBy($order)
            ->distinct()->asArray()->all();

        if ($adminId) {
            $adminLoupans = CommunityAdmin::getLoupansByAdminID($adminId);
            if ($adminLoupans) {
              array_map(function($val)use(&$loupans){
                  if (empty($loupans[$val['id']])) {
                      $loupans[$val['id']] = $val;
                  }
              },$adminLoupans);
            }
        }
        return $loupans;
    }
    /**
     * 获取楼盘列表
     * @param $params 参数条件
     * @return array
     * @author zend.wang
     * @date  2016-06-29 13:00
     */
    public static function getLoupanList() {
        $query = static::find()->where(['valid' => 1])->select(['id', 'name'])->orderBy('sort DESC');
        $result = $query->asArray()->all();
        return !empty($result) ? ArrayHelper::map($result, 'id', 'name') : [];
    }
}