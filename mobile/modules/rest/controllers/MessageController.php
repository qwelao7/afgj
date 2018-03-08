<?php
namespace mobile\modules\rest\controllers;

use Yii;
use mobile\components\ActiveController;
use common\components\Util;
use common\models\ar\message\Message;
use common\models\ar\message\MessageArticle;
use common\models\ar\message\MessageComment;
use common\models\ar\message\MessagePraise;
use common\models\ar\message\MessageVote;
use common\models\ar\message\MessageVoteQuestion;
use common\models\ar\message\MessageVoteQuestionItem;
use common\models\ar\message\MessageVoteResult;
use common\models\ar\user\Account;
use common\models\ar\admin\Admin;
use common\models\ar\user\AccountAddress;
use common\models\ar\user\AccountFriend;
use common\models\ar\fang\FangLoupan;
use common\models\ar\fang\FangHouse;
use common\models\ar\fang\FangCommunityActivity;
use common\models\ar\fang\FangCommunityActivityAccount;
use common\models\ar\community\CommunityVolunteer;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\db\Query;
use common\models\ar\community\CommunityAdmin;
use yii\web\BadRequestHttpException;

use common\models\ecs\EcsUsers;
class MessageController extends ActiveController
{
    /**
     * 消息列表
     */
    public function actionMessageList($id, $page, $type, $messageType)
    {
        $limit = 10;
        $now = date('Y-m-d H:i:s', time());
        $offset = ($page - 1) * $limit;
        if (!$messageType) {
            $messageType = 0;
        }
        //所有楼盘和某个楼盘数据 并 筛选信息类型
        if ($type == 'all') {
            if ($messageType != 0) {
                $data = Message::find()->where(['<', 'message_type', '4'])->andWhere(['<', 'publish_time', $now])->andWhere(['valid' => 1, 'message_type' => $messageType])
                    ->orderBy(['publish_time' => SORT_DESC])->offset($offset)->limit($limit)->asArray()->all();
            } else {
                $data = Message::find()->where(['<', 'message_type', '4'])->andWhere(['<', 'publish_time', $now])->andWhere(['valid' => 1])
                    ->orderBy(['publish_time' => SORT_DESC])->offset($offset)->limit($limit)->asArray()->all();
            }
        } else {
            if ($messageType != 0) {
                $data = Message::find()->where(['loupan_id' => $id, 'valid' => 1, 'message_type' => $messageType])->andWhere(['<>', 'message_type', '4'])
                    ->andWhere(['<', 'publish_time', $now])->orderBy(['publish_time' => SORT_DESC])->offset($offset)->limit($limit)->asArray()->all();
            } else {
                $data = Message::find()->where(['loupan_id' => $id, 'valid' => 1])->andWhere(['<>', 'message_type', '4'])
                    ->andWhere(['<', 'publish_time', $now])->orderBy(['publish_time' => SORT_DESC])->offset($offset)->limit($limit)->asArray()->all();
            }
        }
        if ($data) {
            foreach ($data as $key => $value) {
                //楼盘名称
                $data[$key]['loupan_name'] = FangLoupan::find()->where(['id' => $value['loupan_id']])->select(['name'])->one();
                //显示当前用户是否之前点过赞
                $isPraise = MessagePraise::isPraise($value['id']);
                ($isPraise > 0) ? $data[$key]['isLike'] = true : $data[$key]['isLike'] = false;
                //发布时间
                $data[$key]['publish_time'] = date("m-d H:i", strtotime($value['publish_time']));
                //管理员信息
                $data[$key]['admin_info'] = Admin::getAdminInfo($value['admin_id']);
                //附件类型判断
                switch ($value['attachment_type']) {
                    case 0:
                        break;
                    case 1:
                        $data[$key]['image_type'] = true;
                        $data[$key]['attachment_content'] = explode(",", $value['attachment_content']);
                        break;
                    case 3:
                        $data[$key]['image_type'] = false;
                        $data[$key]['fujian'] = MessageArticle::find()->where(['id' => $data[$key]['attachment_content']])->select(['thumbnail', 'title'])->one();
                        break;
                    default:
                        ;
                }
            };
        };
        //各个楼盘
        $result = FangLoupan::find()->where(['<', 'status', '4'])->select(['name', 'id'])->asArray()->all();
        $data = array('list' => $data, 'title' => $result);
        return $this->renderRest($data);
    }

    /**
     * 消息列表(v2)
     * 所有楼盘
     * @params $page 页数
     **/
    public function actionMessageItems($page) {
        $limit = 10;
        $now = date('Y-m-d H:i:s', time());
        $offset = ($page - 1) * $limit;

        $data = (new Query())->select(['t1.message_type', 't1.admin_id', 't1.title', 't1.content', 't1.publish_time', 't1.attachment_type', 't1.attachment_content', 't1.id', 't1.comment_num', 't1.praise_num', 't1.loupan_id'])
                            ->from('message as t1')->where(['t1.valid'=>1, 't1.publish_status'=>[1,2]])
                            ->andWhere(['t1.message_type'=>[1,2]])->andWhere(['<', 't1.publish_time', $now])
                            ->orderBy(['t1.publish_time' => SORT_DESC])
                            ->offset($offset)->limit($limit)->all();

        if($data) {
            foreach($data as $key=>$value) {
                //楼盘名称
                $data[$key]['loupan_name'] = FangLoupan::find()->where(['id' => $value['loupan_id']])->select(['name'])->one();
                //显示当前用户是否之前点过赞
                $isPraise = MessagePraise::isPraise($value['id']);
                ($isPraise > 0) ? $data[$key]['isLike'] = true : $data[$key]['isLike'] = false;
                //发布时间
                $data[$key]['publish_time'] = date("m-d H:i", strtotime($value['publish_time']));
                //管理员信息
                $data[$key]['admin_info'] = Admin::getAdminInfo($value['admin_id']);
                //附件类型判断
                switch ($value['attachment_type']) {
                    case 0:
                        break;
                    case 1:
                        $data[$key]['image_type'] = true;
                        $data[$key]['attachment_content'] = explode(",", $value['attachment_content']);
                        break;
                    case 3:
                        $data[$key]['image_type'] = false;
                        $data[$key]['fujian'] = MessageArticle::find()->where(['id' => $data[$key]['attachment_content']])->select(['thumbnail', 'title'])->one();
                        break;
                    default:;
                }
            }
        }
        return $this->renderRest($data);
    }

    /**
     * 消息详情
     */
    public function actionMessageDetail($id)
    {
        $data = Message::find()->where(['id' => $id, 'valid' => 1])->asArray()->one();
        $data['publish_time'] = date("m-d H:i", strtotime($data['publish_time']));
        //显示当前用户是否之前点过赞
        $isPraise = MessagePraise::isPraise($id);
        ($isPraise > 0) ? $data['isPraise'] = true : $data['isPraise'] = false;
        //管理员信息
        $data['admin_info'] = Admin::getAdminInfo($data['admin_id']);
        $data['comment'] = MessageComment::find()->where(['message_id' => $id, 'valid' => 1])->orderBy(['created_at' => SORT_DESC])->asArray()->all();
        //附件类型判断
        switch ($data['attachment_type']) {
            case 0:
                break;
            case 1:
                $data['attachment_content'] = explode(",", $data['attachment_content']);
                break;
            case 3:
                $data['fujian'] = MessageArticle::find()->where(['id' => $data['attachment_content']])->select(['thumbnail', 'title'])->one();
                break;
            default:
                ;
        };
        foreach ($data['comment'] as $key => $value) {
            $data['comment'][$key]['user'] = EcsUsers::getUser($value['creater'], ['t1.user_name', 't2.nickname', 't']);
            $data['comment'][$key]['created_at'] = date("m-d H:i", strtotime($value['created_at']));
        };
        return $this->renderRest($data);
    }

    /**
     * 消息附件: 文章
     */
    public function actionMessageArticle($id)
    {
        $data = MessageArticle::find()->where(['id' => $id, 'valid' => 1])->asArray()->one();
        $data['created_at'] = date("m-d H:s", strtotime($data['created_at']));
//        $data['user'] = Admin::getAdminInfo($data['creater']);
        $data['user'] = EcsUsers::getUser(($data['creater']));
        return $this->renderRest($data);
    }

    /**
     * 新鲜事列表
     */
    public function actionCommunityZoneList($id, $page)
    {
        $id = (int)$id;
        $page = (int)$page;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        //用户在该楼盘是否有一套验证过的房子
        $vaild = AccountAddress::ownerAuthNum($id);
        $vaild = (bool)$vaild;
        //是否为业工
        $isVolunteer = CommunityVolunteer::find()->where(['loupan_id' => $id, 'account_id' => Yii::$app->user->id])->count();
        $isVolunteer = (bool)$isVolunteer;
        //新鲜事列表
        $orWhere = '';
        if (146 == Yii::$app->user->id) $orWhere = 'id=45';//id为45的帖子是个维权贴，除了发帖子的人，其他人看不到
        $data = Message::find()->where('loupan_id=' . $id . ' AND message_type=4 AND publish_status IN(1,2) AND valid=1')
            ->orWhere($orWhere)
            ->orderBy(['publish_time' => SORT_DESC])->offset($offset)->limit($limit)->asArray()->all();
        if ($data) {
            //用户信息
            //房产是否认证
            //当前用户与新鲜事发布者是否为好友
            $data = AccountFriend::infoByVaildFriend($data, $vaild, 'account_info', 'account_id', false);
            foreach ($data as $key => $value) {
                //显示当前用户是否之前点过赞
                $isPraise = MessagePraise::isPraise($value['id']);
                ($isPraise > 0) ? $data[$key]['isLike'] = true : $data[$key]['isLike'] = false;
                //发布时间
                $data[$key]['publish_time'] = date("m-d H:i", strtotime($value['publish_time']));
                //附件类型判断
                switch ($value['attachment_type']) {
                    case 0:
                        break;
                    case 1:
                        $data[$key]['image_type'] = 1;
                        $data[$key]['attachment_content'] = explode(",", $value['attachment_content']);
                        break;
                    case 3:
                        $data[$key]['image_type'] = 3;
                        $data[$key]['fujian'] = MessageArticle::find()->where(['id' => $value['attachment_content']])->select(['thumbnail', 'title'])->one();
                        break;
                    case 4:
                        $data[$key]['image_type'] = 4;
                        $data[$key]['fujian'] = MessageVote::find()->where(['id' => $value['attachment_content'], 'valid' => 1])->one();
                        if ($data[$key]['fujian']) {
                            $data[$key]['fujian']['deadline'] = date('m-d H:i', strtotime($data[$key]['fujian']['deadline']));
                        }
                        $data[$key]['voted_person_num'] = MessageVoteResult::find()->where(['mv_id' => $value['attachment_content'], 'valid' => 1])->select('creater')->distinct()->count();
                        break;
                    case 5:
                        $data[$key]['image_type'] = 5;
                        $data[$key]['fujian'] = FangCommunityActivity::find()->where(['id' => $value['attachment_content']])->select(['thumbnail', 'name', 'signup_end'])->one();
                        if ($data[$key]['fujian']) {
                            $data[$key]['fujian']['signup_end'] = date('m-d H:i', strtotime($data[$key]['fujian']['signup_end']));
                            $data[$key]['fujian']['person_num'] = FangCommunityActivityAccount::getCountByActivity($value['attachment_content']);
                        }
                        break;
                    default:
                        ;
                }
            };
        };
        //用户住房所在楼盘
        $isAdmin = false;
        $title = FangLoupan::getLoupansByAccountID(Yii::$app->user->id, Yii::$app->user->identity->admin_id);
        if (Yii::$app->user->identity->admin_id > 0) {
            $isAdmin = CommunityAdmin::isAdminOfLoupan($id, Yii::$app->user->identity->admin_id);
        }

        $data = array('list' => $data, 'title' => $title, 'vaild' => $vaild, 'isVolunteer' => $isVolunteer, 'isAdmin' => $isAdmin);
        return $this->renderRest($data);
    }

    /**
     * 新鲜事列表 (v2)
     * @param $loupanId 楼盘Id
     */
    public function actionCommunityEventList($loupanId, $page) {
        $page = (int)$page;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        //用户在该楼盘是否有一套验证过的房子
        $vaild = (bool)AccountAddress::ownerAuthNum($loupanId);

        //是否为业工
        $isVolunteer = (bool)CommunityVolunteer::find()->where(['loupan_id' => $loupanId, 'account_id' => Yii::$app->user->id])->count();

        //新鲜事列表
        $orWhere = '';
        if (146 == Yii::$app->user->id) $orWhere = 'id=45';//id为45的帖子是个维权贴，除了发帖子的人，其他人看不到
        $data = Message::find()->where('loupan_id=' . $loupanId . ' AND message_type=4 AND publish_status IN(1,2) AND valid=1')
            ->orWhere($orWhere)
            ->orderBy(['publish_time' => SORT_DESC])->offset($offset)->limit($limit)->asArray()->all();

        if($data) {
            $data = AccountFriend::infoByVaildFriend($data, $vaild, 'account_info', 'account_id', false);
            foreach ($data as $key => $value) {
                //显示当前用户是否之前点过赞
                $isPraise = MessagePraise::isPraise($value['id']);
                ($isPraise > 0) ? $data[$key]['isLike'] = true : $data[$key]['isLike'] = false;
                //发布时间
                $data[$key]['publish_time'] = date("m-d H:i", strtotime($value['publish_time']));
                //附件类型判断
                switch ($value['attachment_type']) {
                    case 0:
                        break;
                    case 1:
                        $data[$key]['image_type'] = 1;
                        $data[$key]['attachment_content'] = explode(",", $value['attachment_content']);
                        break;
                    case 3:
                        $data[$key]['image_type'] = 3;
                        $data[$key]['fujian'] = MessageArticle::find()->where(['id' => $value['attachment_content']])->select(['thumbnail', 'title'])->one();
                        break;
                    case 4:
                        $data[$key]['image_type'] = 4;
                        $data[$key]['fujian'] = MessageVote::find()->where(['id' => $value['attachment_content'], 'valid' => 1])->one();
                        if ($data[$key]['fujian']) {
                            $data[$key]['fujian']['deadline'] = date('m-d H:i', strtotime($data[$key]['fujian']['deadline']));
                        }
                        $data[$key]['voted_person_num'] = MessageVoteResult::find()->where(['mv_id' => $value['attachment_content'], 'valid' => 1])->select('creater')->distinct()->count();
                        break;
                    case 5:
                        $data[$key]['image_type'] = 5;
                        $data[$key]['fujian'] = FangCommunityActivity::find()->where(['id' => $value['attachment_content']])->select(['thumbnail', 'name', 'signup_end'])->one();
                        if ($data[$key]['fujian']) {
                            $data[$key]['fujian']['signup_end'] = date('m-d H:i', strtotime($data[$key]['fujian']['signup_end']));
                            $data[$key]['fujian']['person_num'] = FangCommunityActivityAccount::getCountByActivity($value['attachment_content']);
                        }
                        break;
                    default:
                        ;
                }
            };
        }

        //用户住房所在楼盘
        $isAdmin = false;
        $title = FangLoupan::find()->where(['id'=>$loupanId, 'valid'=>1])->select('name')->one();
        //if (Yii::$app->user->identity->admin_id > 0) {
        //    $isAdmin = CommunityAdmin::isAdminOfLoupan($loupanId, Yii::$app->user->identity->admin_id);
        //}

        $data = array('list' => $data, 'title' => $title, 'vaild' => $vaild, 'isVolunteer' => $isVolunteer, 'isAdmin' => $isAdmin);
        return $this->renderRest($data);
    }

    /**
     * 新鲜事详情
     */
    public function actionCommunityZoneDetail($id, $vaild)
    {
        $data = (new Query())->select(['t1.*'])->from('message as t1')->where(['t1.valid'=>1, 't1.id'=>$id])->one();
        $data['publish_time'] = date("m-d H:i", strtotime($data['publish_time']));
        //显示当前用户是否之前点过赞
        $isPraise = MessagePraise::isPraise($id);
        ($isPraise > 0) ? $data['isPraise'] = true : $data['isPraise'] = false;
        //新鲜事发布者信息
        //房产是否认证
        //当前用户与新鲜事发布者是否为好友
        $data = AccountFriend::infoByVaildFriend($data, $vaild, 'account_info', 'account_id', true);
        //新鲜事评论信息
        //房产是否认证
        //当前用户与新鲜事评论者是否为好友
        $data['comment'] = MessageComment::find()->where(['message_id' => $id])->orderBy(['created_at' => SORT_DESC])->asArray()->all();
        $data['comment'] = AccountFriend::infoByVaildFriend($data['comment'], $vaild, 'account_info', 'creater', false);
        foreach ($data['comment'] as $key => $value) {
            $data['comment'][$key]['created_at'] = date("m-d H:i", strtotime($value['created_at']));
            if ($value['is_admin'] != 0 && is_object($data['comment'][$key]['account_info']) && $data['comment'][$key]['account_info']->admin_id) {
                $adminInfo = Admin::getAdminInfo($data['comment'][$key]['account_info']->admin_id);
                if ($adminInfo) {
                    $data['comment'][$key]['account_info']->avatar = $adminInfo->avatar;
                    $data['comment'][$key]['account_info']->nickname = $adminInfo->nickname;
                }
            }
        }
        //附件类型判断
        switch ($data['attachment_type']) {
            case 0:
                break;
            case 1:
                $data['attachment_content'] = explode(",", $data['attachment_content']);
                break;
            case 3:
                $data['fujian'] = MessageArticle::find()->where(['id' => $data['attachment_content']])->select(['thumbnail', 'title'])->one();
                break;
            case 4:
                $data['fujian'] = MessageVote::find()->where(['id' => $data['attachment_content']])->one();
                $data['fujian']['deadline'] = date('m-d H:i', strtotime($data['fujian']['deadline']));
                $data['voted_person_num'] = MessageVoteResult::find()->where(['mv_id' => $data['attachment_content'], 'valid' => 1])->select('creater')->distinct()->count();
                break;
            case 5:
                $data['fujian'] = FangCommunityActivity::find()->where(['id' => $data['attachment_content']])->select(['thumbnail', 'person_num', 'name', 'signup_end'])->one();
                $data['fujian']['signup_end'] = date('m-d H:i', strtotime($data['fujian']['signup_end']));
                break;
            default:
                ;
        };
        $data['isAdmin'] = false;
        if (Yii::$app->user->identity->admin_id > 0) {
            $data['isAdmin'] = CommunityAdmin::isAdminOfLoupan($data['loupan_id'], Yii::$app->user->identity->admin_id);
        }

        return $this->renderRest($data);
    }

    /**
     * 增加活动附件
     */
    public function actionAddActivity($data, $begin, $signup_end)
    {
        date_default_timezone_set("Asia/Shanghai");
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = new FangCommunityActivity;
            $model->loupan_id = $data['loupan_id'];
            $model->name = $data['name'];
            $model->signup_end = date('Y-m-d H:i:s', strtotime($signup_end));
            $model->begin = date('Y-m-d H:i:s', strtotime($begin));
            $model->address = $data['address'];
            $model->person_num = ($data['person_num']) ? $data['person_num'] : '无限制';
            $model->fee = ($data['fee']) ? $data['fee'] : '免费';
            $model->content = $data['content'];
            $model->created_at = date('Y-m-d H:i:s', time());
            $model->creater = Yii::$app->user->id;
            $model->thumbnail = $data['thumbnail'];
            if ($data['pics']) {
                foreach ($data['pics'] as $key => $value) {
                    $data['pics'][$key] = substr(parse_url($value, PHP_URL_PATH), 1);
                };
                $model->pics = implode(',', $data['pics']);
            } else {
                $model->pics = '';
            };
            $model->save();
            $transaction->commit();
        } catch (\yii\db\Exception $e) {
            $transaction->rollback();
            return $this->renderRestErr('提交失败');
        }
        return $this->renderRest($model);
    }

    /**
     * 增加投票附件
     */
    public function actionAddVote($data, $deadline)
    {
        $transaction = Yii::$app->db->beginTransaction();
        $voteId = '';
        $questionId = '';
        try {
            $vote = new MessageVote();
            $vote->title = $data['name'];
            $vote->deadline = date('Y-m-d H:i:s', strtotime($deadline));
            if ($data['content'] != '') {
                $vote->content = $data['content'];
            }
            if ($vote->save()) {
                $voteId = $vote->id;
                foreach ($data['vote'] as $list) {
                    $question = new MessageVoteQuestion();
                    $question->mv_id = $voteId;
                    $question->title = $list['title'];
                    $question->votetype = $list['method'];
                    if ($question->save()) {
                        $questionId = $question->id;
                        foreach ($list['options'] as $item) {
                            $option = new MessageVoteQuestionItem();
                            $option->content = $item['option_desc'];
                            $option->mvq_id = $questionId;
                            if ($item['pic']) {
                                foreach ($item['pic'] as $key => $value) {
                                    $item['pic'][$key] = substr(parse_url($value, PHP_URL_PATH), 1);
                                };
                                $option->picpath = implode(',', $item['pic']);
                            }
                            $option->save();
                        }
                    } else {
                        f_d($data['vote']);
                        throw new BadRequestHttpException("message_vote_question");
                    }
                }
            } else {
                throw new BadRequestHttpException("message_vote存储失败");
            }
            $transaction->commit();
        } catch (\yii\db\Exception $e) {
            $transaction->rollback();
            return $this->renderRestErr('提交失败');
        }
        return $this->renderRest($vote);
    }

    /**
     * 删除附件
     */
    public function actionDeleteAttach($id, $type)
    {
        $type = false;

        if ($type == 'activity') {
            $data = FangCommunityActivity::find()->where(['id' => $id, 'creater' => Yii::$app->user->id])->one();
            if ($data){
                $data->delete();
                $type = true;
            }
        } else if ($type == 'vote') {
            $data = MessageVote::find()->where(['id' => $id, 'creater' => Yii::$app->user->id])->one();
            if ($data) {
                $data->delete();
                $type = true;
            }
            $result = MessageVoteQuestion::find()->where(['mv_id' => $id])->asArray()->all();
            foreach ($result as $list) {
                MessageVoteQuestionItem::deleteAll(['mvq_id' => $list['id']]);
            }
            MessageVoteQuestion::deleteAll(['mv_id' => $id, 'creater' => Yii::$app->user->id]);
        }
        return $this->renderRest($type);
    }

    /**
     * 增加新鲜事
     */
    public function actionMessageCommunity($data)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = new Message;
            $model->message_type = 4;
            $model->account_id = Yii::$app->user->id;
            $model->loupan_id = $data['id'];
            $model->title = $data['title'];
            $model->content = $data['desc'];
            $model->publish_time = date('Y-m-d H:i:s', time());
            $model->publish_status = 1;
            switch ($data['attachment_type']) {
                case 1:
                    if ($data['imgs']) {
                        foreach ($data['imgs'] as $key => $value) {
                            $data['imgs'][$key] = substr(parse_url($value, PHP_URL_PATH), 1);
                        }
                        $model->attachment_content = implode(',', $data['imgs']);
                    }
                    break;
                case 4:
                    $model->attachment_content = (string)$data['attachment_content_id'];
                    break;
                case 5:
                    $model->attachment_content = (string)$data['attachment_content_id'];
                    break;
                default:
                    ;
            };
            $model->attachment_type = $data['attachment_type'];
            $model->save();
            $transaction->commit();
        } catch (\yii\db\Exception $e) {
            $transaction->rollback();
            return $this->renderRestErr('提交失败');
        }
        return $this->renderRest($model);
    }

    /**
     *  增加赞
     */
    public function actionMessagePraise($id, $type)
    {
        $model = Message::find()->where(['id' => $id])->one();
        if ($type == 1) {
            $model->praise_num = $model->praise_num + 1;
        } else {
            $model->praise_num = $model->praise_num - 1;
        };
        $praise = MessagePraise::find()->where(['message_id' => $id])->andWhere(['creater' => Yii::$app->user->id])->one();
        if ($praise) {
            $praise->delete();
        } else {
            $result = new MessagePraise;
            $result->message_id = $id;
            $result->creater = Yii::$app->user->id;
            $result->save();
        };
        if ($model->save()) {
            return $this->renderRest($model);
        } else {
            return $this->renderRestErr('设置失败');
        };
    }

    /**
     * 增加评论
     * @param $id 消息ID
     * @param $info 评论内容
     * @param $isAdmin 是否管家评论 默认0
     * @return \mobile\components\type
     * @author zend.wang
     * @date  2016-06-08 13:00
     */
    public function actionMessageComment($id, $info, $isAdmin = 0)
    {
        $now = date('Y-m-d H:i:s', time());
        $comment = Message::find()->where(['id' => $id])->one();
        $comment->comment_num = $comment->comment_num + 1;
        $data = new MessageComment;
        $data->message_id = $id;
        $data->is_admin = $isAdmin;
        $data->content = $info;
        $data->creater = Yii::$app->user->id;
        $data->created_at = $now;
        $data->save();
        if ($comment->save()) {
            return $this->renderRest($data);
        } else {
            return $this->renderRest('设置失败');
        };
    }

    /**
     * 当前用户参加/(不参加)活动
     */
    public function actionJoinActivity($activityID, $type)
    {
        if ($type) {
            //若数据库中已存在相关记录，则覆盖
            $data = FangCommunityActivityAccount::find()->where(['activity_id' => $activityID, 'account_id' => Yii::$app->user->id])->one();
            if ($data) $data->delete();
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $model = new FangCommunityActivityAccount;
                $model->activity_id = $activityID;
                $model->account_id = Yii::$app->user->id;
                $model->creater = Yii::$app->user->id;
                $model->created_at = date('Y-m-d H:i:s', time());
                $model->valid = 1;
                $model->save();
                $transaction->commit();
            } catch (\yii\db\Exception $e) {
                $transaction->rollback();
                return $this->renderRestErr('提交失败');
            }
        } else {
            $model = FangCommunityActivityAccount::find()->where(['activity_id' => $activityID, 'account_id' => Yii::$app->user->id, 'valid' => 1])->one();
            if ($model) {
                $model->delete();
            }
        }
        return $this->renderRest($model);
    }

    /**
     *  获取活动详情
     */
    public function actionActivityDetail($id)
    {
        $detail = FangCommunityActivity::find()->where(['id' => $id, 'valid' => 1])->one();
        $detail['pics'] = explode(",", $detail['pics']);
        //判断当前用户是否参加活动
        $isJoin = FangCommunityActivityAccount::find()->where(['activity_id' => $id, 'account_id' => Yii::$app->user->id, 'valid' => 1])->count();
        $isJoin = (bool)$isJoin;
        $data = array('detail' => $detail, 'isJoin' => $isJoin);
        return $this->renderRest($data);
    }

    /**
     * 获取活动参加人员详情
     */
    public function actionActivityJoiner($id, $loupanID)
    {
        $joiner = FangCommunityActivityAccount::find()->where(['activity_id' => $id, 'valid' => 1])->select(['account_id'])->asArray()->all();
        if ($joiner) {
            foreach ($joiner as $key => $value) {
                $joiner[$key]['account_info'] = Account::getAccountInfo($value['account_id']);
                $joiner[$key]['account_info']['avatar'] = Account::getAvatar($joiner[$key]['account_info']['avatar']);
                $joiner[$key]['address'] = AccountAddress::addressInfo($value['account_id'], $loupanID, true);
                if ($joiner[$key]['address']) {
                    $joiner[$key]['address'] = implode('-', $joiner[$key]['address']);
                }
            };
        }
        return $this->renderRest($joiner);
    }

    /**
     * 参加投票,新增防重复提交
     * @param $data 投票选项
     * @param $id 调查ID
     * @return \mobile\components\type|void
     * @throws BadRequestHttpException
     * @throws \yii\db\Exception
     * @author huanglong
     * @date  2016-08-15 13:00
     */
    public function actionJoinVote($data, $id)
    {
        if(!$data || !$id || !is_array($data)) {
            return $this->renderRestErr('参数错误');
        }
        $currentUserId = Yii::$app->user->id;
        $num = MessageVoteResult::find()->where(['mv_id'=>$id, 'account_id'=>$currentUserId])->count();
        if($num) {
            return $this->renderRestErr('不能重复提交');
        }
        $db = Yii::$app->db;
        $rows = [];
        foreach($data as $item) {
            $rows[] = [$id, $item, $currentUserId, $currentUserId, $currentUserId];
        }
        $db->createCommand()->batchInsert(MessageVoteResult::tableName(),
            ['mv_id', 'mvqi_id', 'account_id', 'creater', 'updater'], $rows)->execute();
        return $this->renderRest($data);
    }

    /**
     *   获取投票详情
     * @param $id ->投票id
     */
    public function actionVoteDetail($id)
    {
        $isVoted = MessageVoteResult::find()->where(['mv_id' => $id, 'creater' => Yii::$app->user->id, 'valid' => 1])->one();
        $isVoted = (bool)$isVoted;
        $isDelay = MessageVote::find()->where(['id' => $id])->andWhere(['<', 'deadline', date('Y-m-d H:i:s', time())])->one();
        $isDelay = (bool)$isDelay;
        $isShow = MessageVote::find()->where('id=' . $id)->one();
        $isShow = (bool)$isShow['is_show'];
        if (!$isVoted) {
            $data = MessageVote::find()->where(['id' => $id, 'valid' => 1])
                ->asArray()->one();
            $data['question'] = MessageVoteQuestion::find()->where(['mv_id' => $id, 'valid' => 1])
                ->asArray()->all();
            foreach ($data['question'] as &$list) {
                $list['options'] = MessageVoteQuestionItem::find()->where(['mvq_id' => $list['id'], 'valid' => 1])->asArray()->all();
                foreach ($list['options'] as &$item) {
                    if ($item['picpath']) {
                        $item['picpath'] = explode(",", $item['picpath']);
                    }
                }
            }
        } else {
            $data = '';
        }
        return $this->renderRest(['detail' => $data, 'isVoted' => $isVoted, 'isDelay' => $isDelay, 'isShow' => $isShow]);
    }

    /**
     *   获取投票结果
     * @param $id ->t投票id
     */
    public function actionVoteResult($id)
    {
        $data = MessageVote::find()->where(['id' => $id, 'valid' => 1])
            ->asArray()->one();
        $data['question'] = MessageVoteQuestion::find()->where(['mv_id' => $id, 'valid' => 1])
            ->asArray()->all();
        foreach ($data['question'] as &$list) {
            $list['options'] = MessageVoteQuestionItem::find()->where(['mvq_id' => $list['id'], 'valid' => 1])->asArray()->all();
            $list['total_num'] = 0;
            foreach ($list['options'] as &$item) {
                $result = MessageVoteResult::find()->where(['mvqi_id' => $item['id'], 'valid' => 1])->count();
                $item['voted_num'] = $result;
                $list['total_num'] += $item['voted_num'];
                $my_result = MessageVoteResult::find()->where(['mvqi_id' => $item['id'], 'valid' => 1, 'creater' => Yii::$app->user->id])->one();
                ($my_result) ? $item['voted'] = true : $item['voted'] = false;
            }
        }
        return $this->renderRest($data);
    }

    /**
     * 获取用户信息
     */
    public function actionUserAvatar($userId)
    {
        $data = Account::find()->where(['id' => $userId])->select(['nickname', 'avatar'])->one();
        $data['avatar'] = Account::getAvatar($data['avatar']);
        return $this->renderRest($data);
    }

    /**
     * 通过id获取名称
     */
    public function actionGetName($id)
    {
        $data = FangLoupan::find()->where(['id' => $id])->select(['name'])->one();
        return $this->renderRest($data);
    }
}

?>
