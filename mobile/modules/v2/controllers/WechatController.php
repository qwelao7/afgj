<?php
namespace mobile\modules\v2\controllers;

use Yii;
use Qiniu\Auth;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\filters\Cors;
use mobile\modules\v2\models\ApiResponse;
use mobile\modules\v2\models\ApiData;
use mobile\modules\v2\models\User;
/**
 * 涉及微信的相关操作接口
 * 无需登录
 * Class WechatController
 * @package api\modules\v1\controllers
 */
class WechatController extends \yii\rest\Controller  {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(),[
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['index', 'upload', 'config', 'upload-token'],
                        'allow' => true,
                    ],
                    //[
                    //    'actions' => ['index'],
                    //    'allow' => true,
                    //    'roles' => ['@'],
                    //],
                ],
            ],
            [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => f_params('origins'),//定义允许来源的数组
                    'Access-Control-Request-Method' => ['GET','POST','PUT','DELETE', 'HEAD', 'OPTIONS'],//允许动作的数组
                ],
            ],
        ]);
    }
    /**
     *
     * @SWG\Get(path="/wechat/index",
     *     tags={"wechat"},
     *     summary="微信端初始化登录",
     *     description="微信端初始化登录",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response="200",
     *         description="成功操作",
     *         @SWG\Schema(ref="#/definitions/ApiResponse")
     *     ),
     *   @SWG\Response(response=401, description="手机号绑定失败,请先关注回来啦社区公众号"),
     *   @SWG\Response(response=402, description="输入验证码错误，请重新输入"),
     *   @SWG\Response(response=403, description="登录失败，请重试"),
     *   @SWG\Response(response=409, description="参数mobile或code有误"),
     * )
     *
     */
    public function actionIndex() {

        $response = new ApiResponse();

        if(!Yii::$app->user->isGuest) {
            $response->data = new ApiData(0,'登录成功');
            $response->data->info = Yii::$app->user->identity->weixin_code;
        } else if (YII_DEBUG && IS_DEV_MACHINE) {
            $user = User::findOne(57);
            Yii::$app->user->login($user);
            $response->data = new ApiData(0,'测试账户登录成功');
            $response->data->info = Yii::$app->user->identity->weixin_code;
        } else {
            $code = f_get('code');
            if (!$code) {
                $this->jumpToWeiXinAuthorize();
            }
            $data = Yii::$app->wx->getOauthAccessToken($code);

            if (empty($data['openid'])) {
                $response->data = new ApiData(3,'无法获取OpenId');
            } else {
                $user = User::loginOrReg($data);
                if ($user) {
                    if (!empty($user->primary_mobile) && Yii::$app->user->login($user)) {
                        $response->data = new ApiData(0,'登录成功');
                        $response->data->info = Yii::$app->user->identity->weixin_code;
                    } else {
                        $response->data = new ApiData(1,'请绑定手机号');
                        $response->data->info = $user->weixin_code;
                    }
                } else {
                    $response->data = new ApiData(2,'无法获取用户信息');
                }
            }
        }
        return $response;
    }
    /**
     *
     * @SWG\Get(path="/wechat/upload",
     *     tags={"wechat"},
     *     summary="从微信端抓取图片上传到七牛云存储",
     *     description="从微信端抓取图片上传到七牛云存储",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *        in = "query",
     *        name = "mediaId",
     *        description = "微信端上传文件ID",
     *        required = true,
     *        type = "string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="成功操作",
     *         @SWG\Schema(ref="#/definitions/ApiResponse")
     *     ),
     *   @SWG\Response(response=401, description="mediaId参数不能为空"),
     *   @SWG\Response(response=402, description="上传抓取失败"),
     * )
     *
     */
    public function actionUpload($mediaId) {
        $response = new ApiResponse();
        if(!$mediaId) {
            $response->data = new ApiData(1,'mediaId参数不能为空');
        }
        $img = Yii::$app->upload->saveImgToUrl('https://api.weixin.qq.com/cgi-bin/media/get?access_token='.Yii::$app->wx->wxToken.'&media_id='.$mediaId, 'fang');

        if($img && !empty($img['path'])) {
            $response->data = new ApiData(0,'上传图片成功');
            $response->data->info =Yii::$app->upload->domain.$img['path'];
         } else {
            $response->data = new ApiData(2,'上传抓取失败');
        }

        return $response;
    }
    /**
     *
     * @SWG\Post(path="/wechat/config",
     *     tags={"wechat"},
     *     summary="获取微信配置信息",
     *     description="获取微信配置信息",
     *     consumes={"application/x-www-form-urlencoded","application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *        in = "formData",
     *        name = "href",
     *        description = "指定网址",
     *        required = true,
     *        type = "string",
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="成功操作",
     *         @SWG\Schema(ref="#/definitions/ApiResponse")
     *     ),
     *   @SWG\Response(response=401, description="mediaId参数不能为空"),
     *   @SWG\Response(response=402, description="上传抓取失败"),
     * )
     *
     */
    public function actionConfig() {
        $response = new ApiResponse();

        $data = Yii::$app->request->post();
        $timestamp = time();
        $nonceStr = Yii::$app->security->generateRandomString();
        $signature = sha1('jsapi_ticket='.Yii::$app->wechat->app->js->ticket().'&noncestr='.$nonceStr.'&timestamp='.$timestamp.'&url='.$data['href']);
        $config =  \yii\helpers\Json::encode([
            'appId' => Yii::$app->wx->appId,
            'timestamp' => $timestamp,
            'nonceStr' => $nonceStr,
            'signature' => $signature,
        ]);

        if($config) {
            $response->data = new ApiData(0,'微信配置获取成功');
            $response->data->info = $config;
        }else {
            $response->data = new ApiData(1, '配置获取失败');
        }

        return $response;
    }
    /**
     * 返回上传token凭证
     */
    public function actionUploadToken() {
        $auth = new Auth(Yii::$app->upload->qiniuAk, Yii::$app->upload->qiniuSk);

        $opts = ['saveKey'=>'$(fname)'];
        $token = $auth->uploadToken(Yii::$app->upload->bucket, null, 3600, $opts);
        
        $result['uptoken'] = $token;

        return $result;
        
    }
    /**
     * 微信网页授权
     */
    protected function jumpToWeiXinAuthorize(){
        header('HTTP/1.1 302 Moved Permanently');
        header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=".Yii::$app->wx->appId
            ."&redirect_uri=".urlencode('https://api.afguanjia.com/'.Yii::$app->request->pathInfo)
            ."&response_type=code&scope=snsapi_base&state=&connect_redirect=1#wechat_redirect");
        die;
    }
}
