<?php

namespace common\models\ar\message;

use common\models\ecs\EcsUsers;
use Yii;
use common\components\Util;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use common\models\ar\fang\FangCommunityActivity;
use common\models\ar\fang\FangCommunityActivityAccount;
use common\components\ActiveRecord;
use common\models\hll\HllBbsUser;

use yii\db\Query;

/**
 * This is the model class for table "message".
 *
 * @property string $id
 * @property integer $message_type
 * @property integer $account_id
 * @property integer $admin_id
 * @property integer $loupan_id
 * @property string $title
 * @property string $content
 * @property integer $attachment_type
 * @property string $attachment_content
 * @property string $publish_time
 * @property string $publish_status
 * @property integer $comment_num
 * @property integer $praise_num
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class Message extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'message';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['message_type', 'account_id', 'admin_id', 'bbs_id', 'loupan_id', 'attachment_type', 'comment_num', 'praise_num', 'creater', 'updater', 'valid'], 'integer'],
            [['message_type','publish_time', 'attachment_type'], 'required'],
            [['attachment_content'], 'string'],
            [['publish_time', 'created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 50],
            [['content'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message_type' => '消息类型',
            'account_id' => '用户',
            'admin_id' => '管理员',
            'loupan_id' => '楼盘名称',
            'title' => '标题',
            'content' => '内容',
            'attachment_type' => '附件类型',
            'attachment_content' => '附件',
            'publish_time' => '发布时间',
            'publish_status' => '发布状态',
            'comment_num' => '评论数',
            'praise_num' => '赞数',
        ];
    }


    /**
     * message_type字段所有的类型
     */
    public static $messageType = [
        1 => ['name' => '楼盘新闻'],
        2 => ['name' => '楼盘活动'],
        3 => ['name' => '楼盘成长日志'],
        4 => ['name' => '社区新鲜事'],
        5 => ['name' => '装修日志'],
    ];

    /**
     * attachment_type字段所有的类型
     */
    public static $attachmentType = [
        0 => ['name' => '无附件'],
        1 => ['name' => '图片'],
        2 => ['name' => '视频'],
        3 => ['name' => '文章'],
        4 => ['name' => '投票'],
        5 => ['name' => '活动'],
    ];

    /**
     * publish_status字段
     */
    public static $publishStatus = [
        1 => ['name' => '待审核'],
        2 => ['name' => '允许发布'],
        3 => ['name' => '拒绝发布'],
    ];

    /**
     * 获取楼盘
     */
    public function getLoupan()
    {
        return $this->hasOne(\common\models\ar\fang\FangLoupan::className(), ['id' => 'loupan_id']);
    }

    /**
     * 获取用户
     * @return ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(\common\models\ar\user\Account::className(), ['id' => 'account_id']);
    }

    /**
     * 获取管理员
     */
    public function getAdmin()
    {
        return $this->hasOne(\common\models\ar\admin\Admin::className(), ['id' => 'admin_id']);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) return FALSE;
        switch ($this->attachment_type) {
            case '1'://图片
                if (!empty($_POST['attachment'][1])) {
                    $this->attachment_content = join(',', array_filter($_POST['attachment'][1]));
                }
                break;
            case '3'://文章
                if (!empty($_POST['attachment'][3])) {
                    $this->attachment_content = (int)$_POST['attachment'][3];
                }
                break;
            default:
                break;
        }
        return TRUE;
    }

    /**
     * 获取某个小区的新鲜事及其评论数和赞数
     * @param integer $loupanID 楼盘ID
     */
    public static function newsNumByLoupan($loupanID)
    {
        return static::find()->select('COUNT(1) newsNum, SUM(comment_num) commentNum, SUM(praise_num) praiseNum')
            ->where(['loupan_id' => (int)$loupanID, 'message_type' => 4, 'valid' => 1, 'publish_status' => 2])
            ->andWhere(['<', 'publish_time', Util::now()])
            ->asArray()
            ->one();
    }

    /** 消息列表 **/
    public static function newsDataProvider($loupanid)
    {
        $query = static::find()->select(['id'])->where(['loupan_id' => $loupanid, 'message_type' => 1, 'publish_status' => 2, 'valid' => 1])
            ->andWhere(['<=', 'publish_time', date('Y-m-d H:i:s')])->orderBy(['publish_time' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        return $dataProvider;
    }


    /** 新鲜事列表 **/
    public static function createCommunityEventDataProvider($bbsId)
    {
        $query = Message::find()->select('id')->where(['bbs_id' => $bbsId,
            'message_type' => 4, 'publish_status' => [1, 2], 'valid' => 1])
            ->orderBy(['publish_time' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10
            ]
        ]);

        return $dataProvider;
    }

    /** 消息详情 **/
    public static function generateCommunityEventDatail($messageId, $userId)
    {

        $fields = ['id', 'bbs_id','account_id', 'content', 'attachment_type', 'attachment_content', 'comment_num', 'praise_num', 'publish_time'];
        $userFields = ['t2.nickname', 't2.headimgurl'];
        $voteFields = ['id', 'title', 'deadline'];

        $message = Message::find()->select($fields)->where(['id' => $messageId])->asArray()->one();

        if (!$message) return false;

        if($message) {
            //显示当前用户是否之前点过赞
            $message['isLike'] = (MessagePraise::hasPraise($messageId, $userId) > 0);
            //发布者信息
            $message['accountInfo'] = EcsUsers::getUser($message['account_id'], $userFields);
            //发布时间
            $message['publish_time'] = date("m-d H:i", strtotime($message['publish_time']));
            //发布人状态(//是否退出社团)
            $message['publisher_status'] = (bool)HllBbsUser::findOne(['bbs_id'=>$message['bbs_id'],'account_id'=>$message['account_id'],'valid'=>1]);
        }

        //附件类型判断
        switch ($message['attachment_type']) {
            case 1://图片
                $message['attachment_content'] = explode(",", $message['attachment_content']);
                break;
            case 3://文章
                $message['fujian'] = MessageArticle::getInfo($message['attachment_content'], ['id', 'thumbnail']);
                break;
            case 4: //投票
                $message['fujian'] = MessageVote::getInfo($message['attachment_content'], $voteFields);
                $message['voted_person_num'] = MessageVoteResult::getVoteNum($message['attachment_content']);
                break;
            case 5: //活动
                $message['fujian'] = FangCommunityActivity::getInfo($message['attachment_content']);
                if ($message['fujian']) {
                    $message['fujian']['person_num'] = FangCommunityActivityAccount::getCountByActivity($message['attachment_content']);
                }
                break;
        }
        return $message;
    }

    //**楼盘资讯详情**//
    public static function generateLoupanEventDatail($messageId)
    {
        $message = (new Query())->select(['t1.publish_time', 't2.id', 't2.title', 't2.thumbnail'])
            ->from('message as t1')->leftJoin('message_article as t2', 't2.id = t1.attachment_content')
            ->where(['t1.id' => $messageId, 't1.attachment_type' => 3, 't2.valid' => 1,])->one();

        $message['publish_time'] = strtotime($message['publish_time']);

        return $message;
    }

    
}
