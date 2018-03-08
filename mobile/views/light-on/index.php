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
    }
    .bottom-section{
        height:100%;
        background-image: url("/assets/img/light/test-1.jpg");
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
            <img src="/assets/img/light/bg-original.jpg" alt="" class="img-full" name="building-bg">
            <img src="/assets/img/light/test-welcome.png" style="width:20%;position: absolute;right: 5%;top:10%;">
            <div id="building-list"></div>
        </div>
        <div class="bottom-section">
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
      'background' : 'url("/assets/img/light/'+this.lighting_img +'") no-repeat 0% 0% / 100% auto'
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
    $scope.button="/assets/img/light/test-2.png";
    $scope.action={
        switch:function () {
            if($scope.button=="/assets/img/light/test-2.png"){
                $scope.button="/assets/img/light/test-3.png";
                $http.get("/light-on/virtual-lighting");
            }else{
                $scope.button="/assets/img/light/test-2.png";
                $http.get("/light-on/cancel-virtual-lighting").success(function() {
                    window.location.href='/light-on/index';
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
        }, 3000);
    }
    var img = new Image();
    img.src = "/assets/img/light/bg-original.jpg";
    var timer = $interval(function() {
        if (img.complete) {
            $scope.init();
            $interval.cancel(timer);
        }
    }, 100);
});
//图片滚动 调用方法 imgscroll({speed: 30,amount: 1,dir: "up"});
$.fn.imgscroll = function(o){
    var defaults = {
        speed: 40,
        amount: 0,
        width: 1,
        dir: "left"
    };
    o = $.extend(defaults, o);

    return this.each(function(){
        var _li = $("li", this);
        _li.parent().parent().css({overflow: "hidden", position: "relative"}); //div
        _li.parent().css({margin: "0", padding: "0", overflow: "hidden", position: "relative", "list-style": "none"}); //ul
        _li.css({position: "relative", overflow: "hidden"}); //li
        if(o.dir == "left") _li.css({float: "left"});

        //初始大小
        var _li_size = 0;
        for(var i=0; i<_li.size(); i++)
            _li_size += o.dir == "left" ? _li.eq(i).outerWidth(true) : _li.eq(i).outerHeight(true);

        //循环所需要的元素
        if(o.dir == "left") _li.parent().css({width: (_li_size*3)+"px"});
        _li.parent().empty().append(_li.clone());
        _li = $("li", this);

        //滚动
        var _li_scroll = 0;
        function goto(){
            _li_scroll += o.width;
            if(_li_scroll > _li_size)
            {
                _li_scroll = 0;
                _li.parent().css(o.dir == "left" ? { left : -_li_scroll } : { top : -_li_scroll });
                _li_scroll += o.width;
            }
            _li.parent().animate(o.dir == "left" ? { left : -_li_scroll } : { top : -_li_scroll }, o.amount);
        }

        //开始
        var move = setInterval(function(){ goto(); }, o.speed);

    });
};
$(document).ready(function(){

    //$(".scrolltop").imgscroll({
    //    speed: 30,    //图片滚动速度
    //    amount: 0,    //图片滚动过渡时间
    //    width: 1,     //图片滚动步数
    //    dir: "up"   // "left" 或 "up" 向左或向上滚动
    //});
});
</script>
</body>
</html>