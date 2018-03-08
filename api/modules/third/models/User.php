<?php
namespace api\modules\third\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;


class User extends UAccount implements IdentityInterface
{


	/**
	 * 根据给到的ID查询身份。
	 *
	 * @param string|integer $id 被查询的ID
	 * @return IdentityInterface|null 通过ID匹配到的身份对象
	 */
	public static function findIdentity($id)
	{
		return static::findOne(['id' => $id, 'valid' => '1']);
	}

	/**
	 * 根据 token 查询身份。
	 *
	 * @param string $token 被查询的 token
	 * @return IdentityInterface|null 通过 token 得到的身份对象
	 */
	public static function findIdentityByAccessToken($token, $type = null)
	{
		return static::findOne(['apptoken' => $token, 'valid' => '1']);
	}



	/**
	 * @return int|string 当前用户ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string 当前用户的（cookie）认证密钥
	 */
	public function getAuthKey()
	{
		return $this->appsecret;
	}

	public function getAccessToken()
	{
		return $this->apptoken;
	}


	/**
	 * @param string $authKey
	 * @return boolean if auth key is valid for current user
	 */
	public function validateAuthKey($authKey)
	{
		return $this->getAuthKey() === $authKey;
	}
}
