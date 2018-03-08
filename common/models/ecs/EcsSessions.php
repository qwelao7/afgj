<?php

namespace common\models\ecs;

use Yii;
use yii\base\ErrorException;
use yii\db\Query;


/**
 * This is the model class for table "{{%ecs_sessions}}".
 *
 * @property string $sesskey
 * @property integer $expiry
 * @property integer $userid
 * @property integer $adminid
 * @property string $ip
 * @property string $user_name
 * @property integer $user_rank
 * @property string $discount
 * @property string $email
 * @property string $data
 */
class EcsSessions extends \yii\db\ActiveRecord
{
    var $max_life_time = 1800;
    var $session_name = 'touch_id';
    var $session_id = '';
    var $session_expiry = '';
    var $session_md5 = '';
    var $session_cookie_path = '/';
    var $session_cookie_domain = '.afgj.com';
    var $session_cookie_secure = false;
    var $_ip = '';
    var $_time = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ecs_sessions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sesskey', 'user_name', 'user_rank', 'discount', 'email'], 'required'],
            [['expiry', 'userid', 'adminid', 'user_rank'], 'integer'],
            [['discount'], 'number'],
            [['sesskey'], 'string', 'max' => 32],
            [['ip'], 'string', 'max' => 15],
            [['user_name', 'email'], 'string', 'max' => 60],
            [['data'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sesskey' => 'Sesskey',
            'expiry' => 'Expiry',
            'userid' => 'Userid',
            'adminid' => 'Adminid',
            'ip' => 'Ip',
            'user_name' => 'User Name',
            'user_rank' => 'User Rank',
            'discount' => 'Discount',
            'email' => 'Email',
            'data' => 'Data',
        ];
    }

    public function init()
    {
        parent::init();
        $this->_ip = f_real_ip();
        $this->_time = time();
        $this->session_id = f_cookie($this->session_name);

    }

    public function loadSession()
    {
        if (!$this->session_id) return false;

        $tmp_session_id = substr($this->session_id, 0, 32);
        if ($this->gen_session_key($tmp_session_id) != substr($this->session_id, 32)) {
            return false;
        }
        $GLOBALS['_SESSION'] = [];
        $session = EcsSessions::findOne(['sesskey' => $tmp_session_id]);
        if(!$session) return false;
        if (!empty($session->data) && $this->_time - $session->expiry <= $this->max_life_time) {
            $GLOBALS['_SESSION'] = unserialize($session->data);
            $GLOBALS['_SESSION']['user_id'] = $session->userid;
            $GLOBALS['_SESSION']['admin_id'] = $session->adminid;
            $GLOBALS['_SESSION']['user_name'] = $session->user_name;
            $GLOBALS['_SESSION']['user_rank'] = $session->user_rank;
            $GLOBALS['_SESSION']['discount'] = $session->discount;
            $GLOBALS['_SESSION']['email'] = $session->email;
        } else {
            $session_data = (new Query())->select(['data', 'expiry'])->from('ecs_sessions_data')->where(['sesskey' => $tmp_session_id])->one();

            if (!empty($session_data['data']) && $this->_time - $session_data['expiry'] <= $this->max_life_time) {
                try{
                    $GLOBALS['_SESSION'] = unserialize($session_data['data']);
                    $GLOBALS['_SESSION']['user_id'] = $session->userid;
                    $GLOBALS['_SESSION']['admin_id'] = $session->adminid;
                    $GLOBALS['_SESSION']['user_name'] = $session->user_name;
                    $GLOBALS['_SESSION']['user_rank'] = $session->user_rank;
                    $GLOBALS['_SESSION']['discount'] = $session->discount;
                    $GLOBALS['_SESSION']['email'] = $session->email;
                } catch (ErrorException $ex) {
                    Yii::error("session data: ". $session_data['data'], 'error');
                    Yii::error("session data unserialize exception: ".$ex->getMessage(), 'error');
                    return false;
                }
            } else {
                return false;
            }

        }
        return true;
    }

    function gen_session_key($session_id)
    {
//        static $ip = '';
//
//        if ($ip == '') {
//            $ip = substr($this->_ip, 0, strrpos($this->_ip, '.'));
//        }

        return sprintf('%08x', crc32(ECTOUCH_ROOT_PATH . $session_id));
    }
}
