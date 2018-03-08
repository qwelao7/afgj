<?php

namespace common\models\hll;

use Yii;
use common\components\ActiveRecord;
/**
 * This is the model class for table "hll_lxhealthy_temp".
 *
 * @property string $id
 * @property string $templateId
 * @property string $openId
 * @property string $title
 * @property string $data
 * @property string $receive_time
 * @property string $send_time
 * @property integer $send_status
 * @property string $creater
 * @property string $created_at
 * @property string $updater
 * @property string $updated_at
 * @property integer $valid
 */
class HllLxhealthyTemp extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hll_lxhealthy_temp';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['receive_time'], 'required'],
            [['receive_time', 'send_time', 'created_at', 'updated_at'], 'safe'],
            [['send_status', 'creater', 'updater', 'valid'], 'integer'],
            [['templateId', 'openId', 'title'], 'string', 'max' => 255],
            [['data'], 'string', 'max' => 1000],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'templateId' => 'Template ID',
            'openId' => 'Open ID',
            'title' => 'Title',
            'data' => 'Data',
            'receive_time' => 'Receive Time',
            'send_time' => 'Send Time',
            'send_status' => 'Send Status',
            'creater' => 'Creater',
            'created_at' => 'Created At',
            'updater' => 'Updater',
            'updated_at' => 'Updated At',
            'valid' => 'Valid',
        ];
    }

    /**
     * 获取蓝熙健康消息
     * @param $templateId 模板ID
     * @param $title 通知标题
     * @param $openId 客户OpenId
     * @param $data 数据
     * @return bool
     */
    public static function saveTempData($templateId,$title,$openId,$data){

        $templateId = 'OPENTM203116282';
        $openId = 'OPENTM402073008';

        $data = [
            'first'=>'11111',
            'keyword'=>[
                'keyword1'=>'aaaa',
                'keyword2'=>'bbbb'
            ],
            'remark'=>'22222',
            'redirect_url'=>'http://www.baidu.com'
        ];

        $messageBody['first']=$data['first'];
        $messageBody['keyword1'] = f_params(['lxheathTmplRemardFormat',$templateId,'title']);
        $messageBody['keyword2'] = '2016-12-11';
        $messageBody['keyword3'] = '南京慈铭体检中心';

        $remark= $data['keyword'];
        array_push($remark,$data['remark']);
        $remarkFormat = f_params(['lxheathTmplRemardFormat',$templateId,'remarkFormat']);
        $messageBody['remark'] = vsprintf($remarkFormat,$remark);
        $messageBody['redirect_url']=$data['redirect_url'];

        $model = new HllLxhealthyTemp();
        $model->templateId = $templateId;
        $model->openId = $openId;
        $model->title = $title;
        $model->data = json_encode($messageBody,JSON_UNESCAPED_UNICODE);
        $model->receive_time = date('Y-m-d H:i:s',time());

        if($model->save()){
            return $model->id;
        }else{
            return false;
        }
    }
}
