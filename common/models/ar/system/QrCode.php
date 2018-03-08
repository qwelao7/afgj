<?php

namespace common\models\ar\system;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "qr_code".
 *
 * @property integer $id
 * @property string $qr_code
 * @property string $qr_url
 * @property string $qr_pic_path
 * @property string $expire_time
 * @property integer $item_type
 * @property integer $item_id
 * @property integer $creater
 * @property string $created_at
 * @property integer $updater
 * @property string $updated_at
 * @property integer $valid
 */
class QrCode extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'qr_code';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['expire_time', 'created_at', 'updated_at', 'qr_pic_path'], 'safe'],
			[['item_type', 'item_id', 'creater', 'updater', 'valid'], 'integer'],
			[['qr_code', 'qr_url'], 'string', 'max' => 100],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'qr_code' => 'Qr Code',
			'qr_url' => 'Qr Url',
			'qr_pic_path' => 'Qr Pic Path',
			'expire_time' => 'Expire Time',
			'item_type' => 'Item Type',
			'item_id' => 'Item ID',
			'creater' => 'Creater',
			'created_at' => 'Created At',
			'updater' => 'Updater',
			'updated_at' => 'Updated At',
			'valid' => 'Valid',
		];
	}

	public static function uploadQrcodeTo7niu($ticket, $group = 'fang')
	{
		if (empty($ticket)) return FALSE;
		$ticket = urlencode($ticket);
		$protocal = 'http';
		if (!YII_DEBUG) $protocal .= 's';
		$url = $protocal . '://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . $ticket;
		$rsp = Yii::$app->upload->saveImgToUrl($url, $group);
		return $rsp;
	}

	/**
	 * 生成邀请好友的图片二维码
	 * @param $userId 邀请人ID
	 * @param string $actionName 二维码类型
	 * @param int $expired 过期时间
	 * @return bool|string 图片地址
	 * @author zend.wang
	 * @date  2016-08-12 13:00
	 */
	public static function generateInvitationFriendQrcode($userId, $actionName = 'QR_SCENE', $expired = 604800)
	{
		$currTime = f_date(time());
		$qr_pic_path = static::find()->select('qr_pic_path')->where(['item_type' => 2, 'item_id' => $userId, 'valid' => 1])->andWhere(['>', 'expire_time', $currTime])->scalar();
		if ($qr_pic_path) {
			return $qr_pic_path;
		}
		$data = Yii::$app->wx->genQrcode("{$userId}", $actionName, $expired);
		if ($data && !empty($data['ticket'])) {
			$rsp = static::uploadQrcodeTo7niu($data['ticket'], 'qrcode');
			if ($rsp && !empty($rsp['path'])) {
				//保存图片返回图片
				$model = new QrCode();
				$model->qr_code = "{$userId}";
				$model->qr_url = $data['url'];
				$model->qr_pic_path = $rsp['path'];
				$model->expire_time = f_date(time() + $expired);
				$model->item_id = $userId;
				$model->item_type = 2;
				$model->save();
				return $rsp['path'];
			} else {
				return false;
			}
		}
	}

	/**
	 * 获取URL的后缀
	 * @param $id
	 * @return string
	 */
	public static function getUrlById($id){
		$url = static::find()->select(['qr_url'])->where(['id'=>$id,'valid'=>1])->scalar();
		if($url){
			return substr($url,strripos($url, '/'));
		}else{
			return '';
		}
	}

	public static function getCodeByUrl($url){
		$url = 'http://weixin.qq.com/q'.$url;
		$code_id = static::find()->select(['id'])->where(['qr_url'=>$url,'valid'=>1])->scalar();
		if($code_id){
			return $code_id;
		}else{
			return '';
		}
	}
}

