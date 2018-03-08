<?php
/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="zh-CN>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-param" content="_csrf">
    <meta name="csrf-token" content="d2sybmVwZkQREn0NDCocHCRYfAAVIzYUGT9HXg0kBz5BWWciIzw8Cg==">
    <title></title>
    <link href="/assets/lib/ionic/css/ionic.min.css" rel="stylesheet">
    <link href="/assets/css/home.css?v=72" rel="stylesheet">
    <link href="/assets/css/style.css?v=37" rel="stylesheet"></head>
<body ng-app="starter">
<?php $this->beginBody() ?>
    <?= $content ?>
<?php if(!IS_DEV_MACHINE && TXSTATID) {?>
<script type="text/javascript" src="http://pingjs.qq.com/h5/stats.js" name="MTAH5" sid="<?=TXSTATID ?>" ></script>
<?php }?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
