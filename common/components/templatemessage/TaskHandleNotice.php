<?php
namespace common\components\templatemessage;

use yii\db\Query;
use yii\base\Exception;
/**
 * 报障处理提醒
 * Class TemplateMessage
 * @package common\components\templatemessage
 */
class TaskHandleNotice extends TemplateMessage
{

    public function init()
    {
        parent::init();
        $this->template_id = (new Query())->select('template_id')
                    ->from('ecs_wechat_template')->where(['open_id'=>'OPENTM403153535','switch'=>1])->scalar();
        if(!$this->template_id) {
            throw new Exception("微信模板不存在", 101);
        }
    }

    protected function pack()
    {
        $first          = '您有一条任务处理通知';
        $keyword1       = '报障处理提醒';
        $keyword2       = '提醒';
        $remark         = '您有报障任务已经完成处理，请评价。';

        $data = [
            'first'     => ['value' => $first],
            'keyword1'  => ['value' => $keyword1],
            'keyword2'  => ['value' => $keyword2],
            'remark'    => ['value' => $remark]
        ];

        return $data;
    }
}