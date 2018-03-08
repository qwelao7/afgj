<?php

namespace mobile\components;

use Yii;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\base\InlineAction;
use yii\filters\AccessControl;

/**
 * Description of ActiveController
 *
 * @author Don.T
 */
class ActiveController extends \yii\rest\ActiveController {
    
    public function init() {}
    
    public function actions() {}
    
    protected function verbs() {}
    
    public function behaviors() {
        return \yii\helpers\ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@']
                    ],
                ],
                'denyCallback' => function($rule, $action) {
                    throw new BadRequestHttpException('您没有权限');
                }
            ],
        ]);
    }
    
    /**
     * 绑定rest接口参数
     * @param type $action
     * @param type $params
     * @return type
     */
    public function bindActionParams($action, $params) {
        
        if (Yii::$app->request->getMethod() == 'POST') {
            $data = file_get_contents('php://input');
            if ($data) {
                $params = Json::decode($data, true);
            }
        }
        
        if ($action instanceof InlineAction) {
            $method = new \ReflectionMethod($this, $action->actionMethod);
        } else {
            $method = new \ReflectionMethod($action, 'run');
        }

        $args = [];
        $missing = [];
        $actionParams = [];
        foreach ($method->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $params)) {
                if ($param->isArray()) {
                    $args[] = $actionParams[$name] = (array) $params[$name];
                } else {
                    $args[] = $actionParams[$name] = $params[$name];
                }
                unset($params[$name]);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $actionParams[$name] = $param->getDefaultValue();
            } else {
                $missing[] = $name;
            }
        }

        if (!empty($missing)) {
            throw new BadRequestHttpException(Yii::t('yii', 'Missing required parameters: {params}', [
                'params' => implode(', ', $missing),
            ]));
        }

        $this->actionParams = $actionParams;

        return $args;
    }
    
    /**
     * rest格式输出
     * @param type $data    内容
     * @param type $msg     错误信息
     * @param type $code    返回码
     * @return type
     */
    public function renderRest($data=true) {

        return [
            'data'=>$data
        ];
    }
    
    /**
     * 错误返回异常
     * @param type $msg     错误详情
     * @throws BadRequestHttpException
     */
    public function renderRestErr($msg) {
        throw new BadRequestHttpException($msg);
    }
}