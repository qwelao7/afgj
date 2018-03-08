<?php

namespace common\models\hll;

use Yii;
use yii\helpers\ArrayHelper;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_library_book_category".
 *
 * @property string $id
 * @property string $name
 * @property integer $is_show
 * @property integer $view_order
 * @property integer $valid
 */
class HllLibraryBookCategory extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_library_book_category';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['is_show', 'view_order', 'valid'], 'integer'],
            [['name'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'is_show' => 'Is Show',
            'view_order' => 'View Order',
            'valid' => 'Valid',
        ];
    }

    /**
     * 获取分类ID
     * @param $name 分类名称
     * @return int|string
     * @author zend.wang
     * @time 2017-03-23 15:00
     */
    public static function getCategoryByName($name) {
        $pattern = '/[\x{4e00}-\x{9fff}]/u';
        preg_match_all($pattern, $name, $match);
        $match = implode('', $match[0]);
        $bookCategory = static::findOne(['name'=>$match,'valid'=>1]);
        if($bookCategory) {
            return $bookCategory->id;
        }else {
            $bookCategory = new HllLibraryBookCategory();
            $bookCategory->name = $match;
            if($bookCategory->save()) {
                return $bookCategory->id;
            }else {
                return 0;
            }
        }
    }
    /**
     * 获取所有分类
     * @return array
     * @author zend.wang
     * @time 2017-03-23 15:00
     */
    public static function getAllCategory() {
        $categoryList = static::find()->select(['id','name'])->where(['is_show'=>1,'valid'=>1])->orderBy('view_order DESC')->all();
        if($categoryList) {
            return ArrayHelper::map($categoryList,'id','name');
        }else {
           return [];
        }
    }
}
