<?php
/**
 *
 * @author: XuYi
 * @date: 2016-11-04
 * @version: $Id$
 */
namespace common\components;

use Yii;
class ActiveRecord extends \yii\db\ActiveRecord {
	/**
	 * {@inheritDoc}
	 * @see \yii\db\BaseActiveRecord::beforeSave($insert)
	 */
	public function beforeSave($insert){
		if(!parent::beforeSave($insert))return FALSE;
        if(empty(Yii::$app->user))return FALSE;
		//统一处理creater,created_at,updater,updated_at
		if($insert){
			if($this->hasAttribute('creater') && isset(Yii::$app->user) && !Yii::$app->user->isGuest)$this->creater=Yii::$app->user->getId();
			if($this->hasAttribute('created_at'))$this->created_at=\common\components\Util::now();
		}else{
			if($this->hasAttribute('updater') && isset(Yii::$app->user) && !Yii::$app->user->isGuest)$this->updater=Yii::$app->user->getId();
			if($this->hasAttribute('updated_at'))$this->updated_at=\common\components\Util::now();
		}
		return TRUE;
	}

	public function beforeSaveTrue($insert){
		if(!parent::beforeSave($insert))return FALSE;
		return TRUE;
	}
}