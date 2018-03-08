<?php
use yii\helpers\Html;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?= Html::encode($this->title) ?></title>
<script src="https://cdn.bootcss.com/jquery/1.11.1/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/angular.js/1.5.5/angular.min.js"></script>
<link rel="stylesheet" href="http://g.alicdn.com/msui/sm/0.6.2/css/sm.min.css">
<style>
    html,body{
        padding: 0px;
        margin: 0px;
        font-size: .7rem;
        font-family: KaiTi,"Helvetica Neue",Helvetica,sans-serif;
    }

    .building-area{
        width: 100%;
        margin: 0px auto;
        height:100VH;
    }
    .building-list{
        width: 100%;
    //	height: 100%;
        position: relative;
        float: left;
    }
    .img-full{
        width: 100%;
        position: absolute;
        top: -160px;
    }
    .bottom-section{
        height:100%;
        background-image: url("/assets/img/light2/test-1.jpg");
        background-position: center center;
        background-size:cover;
    }
    .scrolltop{
        width:100%;
        height:50%;
        overflow: hidden;
        position: relative;
    }
    .scroll-list{
        max-height:100%;
        margin: 0px;
        padding: 0px;
        overflow: hidden;
        position: relative;
        list-style: none;
    }
    .scroll-detail{
        position: relative; overflow: hidden;
    }
    .col-33{
        text-align:left;
        color:#fff;
    }
    .table-hover-td>tbody>tr>td:hover{background-color:#f5f5f5}
    .light{background-color: #E8B819;opacity: 0.8;}
</style>
</head>
<body ng-app="myApp" ng-controller="myCtrl">
    <div class="building-area">
    	<div class="building-list">
            <img src="/assets/img/light2/bg-original.jpg" alt="" class="img-full" name="building-bg">
            <img src="/assets/img/light2/pic.png" style="position:absolute;left:5%;top:10%;width:5%;">
            <img src="/assets/img/light2/test-welcome.png" style=" position: absolute;right: 8%;top:10%;width:15%;">
            <div id="building-list" style="height:100%;"></div>
        </div>
        <div class="bottom-section">
            <img src="/assets/img/light2/test-welcome.png" style="width:20%;margin:0 auto;display:block;margin-bottom:2%;">
            <div class="scrolltop">
                <ul class="scroll-list" style="padding-left: 5%;">
                    <li class="scroll-detail row" id="user_scroll_area">
                    <?php
                        foreach($result['users'] as $user) {
                    ?>
                        <div class="col col-33">
                            <img src="http://pub.huilaila.net/<?=$user['avatar']?>?imageView2/1/w/100/h/100" style="width: 8%;height:8%;">
                            <span><?=$user['nickname']?>(<?=$user['building_num']?>-<?=$user['unit_num']?>-<?=$user['house_num']?>)</span>
                        </div>
                    <?php
                    }
                    ?>
                    </li>
                </ul>
            </div>
            <img ng-src="{{button}}" ng-click="action.switch()" style="width:2%;position:absolute;bottom: 2%;right: 2%">

        </div>
    </div>
<script>
function CreateBuilding(top, left, width, height,lighting_img) {
    this.top = top;
    this.left = left;
    this.width = width;
    this.height = height;
    this.lighting_img = lighting_img;
    this.init();
}

CreateBuilding.prototype = {
  init: function() {
    var building = $('<div class="building">');
    building.css({
      'position' : 'absolute',
      'top' : this.top +'%',
      'left' : this.left +'%',
      'width' : this.width +'%',
      'height' : this.height +'%',
      'background' : 'url("/assets/img/light2/'+this.lighting_img +'") no-repeat 0% 0% / 100% auto'
    });

    $('#building-list').append(building);
    
  }
};

var app = angular.module('myApp', []);

app.controller('myCtrl', function($scope, $http, $interval) {
    $scope.config = {
        building: <?=yii\helpers\Json::encode($result['data'])?>,
    };
    $scope.lastTime = <?=$result['time']?>;
    $scope.button="/assets/img/light2/test-2.png";
    $scope.action={
        switch:function () {
            if($scope.button=="/assets/img/light2/test-2.png"){
                $scope.button="/assets/img/light2/test-3.png";
                $http.get("/light-on/virtual-lighting");
            }else{
                $scope.button="/assets/img/light2/test-2.png";
                $http.get("/light-on/cancel-virtual-lighting").success(function() {
                    window.location.href='/light-on/index2';
                });
            }
        }
    }
    $scope.init = function() {
        for (var i=0,n=$scope.config.building.length; i<n; i++) {

          new CreateBuilding($scope.config.building[i].top, $scope.config.building[i].left,
                                $scope.config.building[i].width, $scope.config.building[i].height,
                                $scope.config.building[i].lighting_img);
        }
        var time = $scope.lastTime;
    $interval(function(){
            $http.get("/light-on/lighting", {params:{ loupanId:5, time:0}})
                        .success(function(rsp) {
                            $('#building-list').empty();
                            for (var i=0,n=rsp.data.length; i<n; i++) {
                                new CreateBuilding(rsp.data[i].top,
                                                    rsp.data[i].left,
                                                    rsp.data[i].width,
                                                    rsp.data[i].height,
                                                    rsp.data[i].lighting_img);
                            }
                            $('#user_scroll_area').empty();
                            for (var i=0,n=rsp.users.length; i<n; i++) {
                                $('#user_scroll_area').append("<div class='col col-33'><img src='http://pub.huilaila.net/"
                                    +rsp.users[i].avatar+"?imageView2/1/w/100/h/100' style='width: 8%;height:8%;'><span>"
                                    +rsp.users[i].nickname+"("+rsp.users[i].building_num+"-"+rsp.users[i].unit_num+"-"+rsp.users[i].house_num+")</span></div>");
                            }
                            time = rsp.time
            });
        }, 3000000000000);
    }
    var img = new Image();
    img.src = "/assets/img/light2/bg-original.jpg";
    var timer = $interval(function() {
        if (img.complete) {
            $scope.init();
            $interval.cancel(timer);
        }
    }, 100);
});

</script>
</body>
</html>