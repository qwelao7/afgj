<?php
/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use mobile\assets\AppAsset;
use yii\helpers\Json;
use common\models\ar\community\CommunityVolunteer;
use common\models\ecs\EcsUsers;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
    <meta name="format-detection" content="telephone=no" />
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body ng-app="starter">
<?php $this->beginBody() ?>
    <?= $content ?>
<script>
<?php
///* 当前用户是那些楼盘的业工 */
//$volunteerLoupans = CommunityVolunteer::find()
//    ->select('loupan_id,role_id')
//    ->where(['account_id'=>Yii::$app->user->id, 'valid'=>1])
//    ->asArray()
//    ->indexBy('loupan_id')
//    ->all();
//
$user = EcsUsers::getUser(Yii::$app->user->id);

$info=['name'=>$user['nickname'],
    'avatar'=>EcsUsers::getAvatar($user['headimgurl'])];
//昵称
if(!$info['name']) {
    $info['name'] = $user['user_name'];
}
//?>
var qiniuDomain = '<?=Yii::$app->upload->domain?>';
var user = <?=Json::encode([
    'name' => $info['name'],
    'sex' => Yii::$app->user->identity->sex,
    'nick' => Yii::$app->user->identity->user_id,
    'phone' => Yii::$app->user->identity->mobile_phone,
    'address' => Yii::$app->user->identity->address_id,
    'avatar' =>  $info['avatar'],
//    'volunteerLoupans' => $volunteerLoupans,
])?>;
var keyMap = <?=Json::encode([
    'loupanTag' => \common\models\ar\fang\FangLoupan::$tagText,
    'loupanStatus' => \common\models\ar\fang\FangLoupan::$statusText,
    'loupanBuildingTypeText' => \common\models\ar\fang\FangLoupan::$buildingTypeText,
    'loupanDecorateLevel' => \common\models\ar\fang\FangLoupan::$decorateLevel,
    'loupanPropertyType' => \common\models\ar\fang\FangLoupan::$propertyType,
    'loupanHeatingType' => \common\models\ar\fang\FangLoupan::$heatingType,
    'houseSellStatus' => \common\models\ar\fang\FangHouse::$sellStatusText,
])?>;
var wxConfig = <?=Yii::$app->util->getWxConfig();?>;
////localStorage.setItem('openId','<?////=Yii::$app->user->identity->weixin_code;?>////');
</script>
<?php if(!IS_DEV_MACHINE && TXSTATID) {?>
<script type="text/javascript" src="http://pingjs.qq.com/h5/stats.js" name="MTAH5" sid="<?=TXSTATID ?>" ></script>
<?php }?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
