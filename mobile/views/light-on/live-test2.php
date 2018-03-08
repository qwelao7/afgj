<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
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
    background : url("/assets/img/light/test-1.jpg") repeat-y 0% 0%;
}
.scrolltop{
    width:100%;
    max-height:24%;
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
    top: -19px;
}
.scroll-detail{
    position: relative; overflow: hidden;
}
.col-33{
    text-align:center;
    color:#fff;
}
.table-hover-td>tbody>tr>td:hover{background-color:#f5f5f5}
.light{background-color: #E8B819;}
</style>
</head>
<body ng-app="myApp" ng-controller="myCtrl">
    <div class="building-area">
    	<div class="building-list">
            <img src="/assets/img/light/bg-original.jpg" alt="" class="img-full" name="building-bg">
    	</div>
    	<div class="bottom-section">
            <img src="/assets/img/light/test-welcome.png" style="width:20%;margin:0 auto;display:block;margin-bottom:2%;">
    	    <div class="scrolltop">
                <ul class="scroll-list">
                    <li class="scroll-detail row">
                        <div class="col col-33">
                            <img src="/assets/img/light/light.png" style="width: 5%;height:5%;">
                            <span>姓名(31-32-302)</span>
                        </div>
                        <div class="col col-33">
                            <img src="/assets/img/light/light.png" style="width: 5%;height:5%;">
                            <span>姓名(31-32-302)</span>
                        </div>
                        <div class="col col-33">
                            <img src="/assets/img/light/light.png" style="width: 5%;height:5%;">
                            <span>姓名(31-32-302)</span>
                        </div>
                        <div class="col col-33">
                            <img src="/assets/img/light/light.png" style="width: 5%;height:5%;">
                            <span>姓名(31-32-302)</span>
                        </div>
                        <div class="col col-33">
                            <img src="/assets/img/light/light.png" style="width: 5%;height:5%;">
                            <span>姓名(31-32-302)</span>
                        </div>
                        <div class="col col-33">
                            <img src="/assets/img/light/light.png" style="width: 5%;height:5%;">
                            <span>姓名(31-32-302)</span>
                        </div>
                        <div class="col col-33">
                            <img src="/assets/img/light/light.png" style="width: 5%;height:5%;">
                            <span>姓名(31-32-302)</span>
                        </div>
                        <div class="col col-33">
                            <img src="/assets/img/light/light.png" style="width: 5%;height:5%;">
                            <span>姓名(31-32-302)</span>
                        </div>
                        <div class="col col-33">
                            <img src="/assets/img/light/light.png" style="width: 5%;height:5%;">
                            <span>姓名(31-32-302)</span>
                        </div>

                    </li>
                </ul>
            </div>
            <img ng-src="{{button}}" ng-click="action.switch()" style="width:2%;position:absolute;bottom: 2%;right: 2%">

        </div>
    </div>
<script>
//所有top\left\width\height都按百分值来计算，所以传值的时候也记得传百分比，building的位置为绝对定位，deg为倾斜角度，column为每行窗数
function CreateBuilding(top, left, width, height) {
  this.top = top;
  this.left = left;
  this.width = width;
  this.height = height;

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
      'background' : 'url("/assets/img/light/1_1.png") no-repeat 0% 0% / 100% auto'
    });

    $('.building-list').append(building);

//    var w_width = 100/(this.column + (this.column-1)/2);
//    var w_width_px = $('.building-current').width()*w_width*0.01;
//    var column_height = $('.building-current').height()/this.rows;
//    var b_height = column_height - w_width_px;
//    for( var r=0; r < this.rows-1; r++ ){
//      var row = $('<div class="inline">');
//      row.css({
//        'width':'100%',
//        'height' : w_width_px,
//        'margin-bottom' :b_height,
//        'transform' : 'skewY('+ (parseInt(this.deg)+r/3) +'deg)',
//      });
//      for( var c=0;c < this.column;c++ ){
//        var span = $('<div data-row="'+r+'" data-column="'+c+'">');
//        var right = c == this.column -1 ? '' : w_width/2 + '%';
//        span.css({
//          'width' : w_width+'%',
//          'height' : '28%',
//          'float' : 'left',
//          'margin-right': right,
//          'background' : 'rgba(94, 174, 239, 0.6)',
//        });
//        row.append(span);
//      }
//      building.append(row);
//    }

//    $('.building-list').find('.building-current').remove();
//    building.removeClass('building-current');
//    $('.building-list').append(building);
    
  },
//  lightOn: function(row,column,background) {
//    $('#'+this.id).find('[data-row="'+(this.rows-row-1)+'"][data-column="'+(column-1)+'"]').css({
//      'background' : background ? background : 'url("/assets/img/light/light.png") no-repeat 0% 0% / 100% auto'
//    });
//
//  }
};
// var building = new CreateBuilding('24', '44.6', '8.1','57','-17',6);
// building.lightOn(2,3,'#bea323');
// building.lightOn(6,1,'#bea323');

var app = angular.module('myApp', []);
//var registerData = yii\helpers\Json::encode($data);
app.controller('myCtrl', function($scope, $http, $interval) {
    $scope.button="/assets/img/light/test-2.png";
    $scope.action={
        switch:function () {
            if($scope.button=="/assets/img/light/test-2.png"){
                $scope.button="/assets/img/light/test-3.png";
            }else{
                $scope.button="/assets/img/light/test-2.png";
            }
        }
    }
    $scope.config = {
        building: [
            //43
            {
                top: 80, left: 16, width: 1.1, height: 4
            }, {
                top: 80.7, left: 16.05,width: 1.1, height: 4
            }, {
                top: 81.4, left: 16.1,width: 1.1, height: 4
            }, {
                top: 82.1, left: 16.15, width: 1.1, height: 4
            }, {
                top: 82.8, left: 16.2, width: 1.1, height: 4
            }, {
                top: 83.5, left: 16.25, width: 1.1, height: 4
            }, {
                top: 80.8, left: 17.3,width: 1.1, height: 4
            }, {
                top: 81.5, left: 17.35, width: 1.1, height: 4
            },{
                top: 82.2, left: 17.4,  width: 1.1, height: 4
            },{
                top: 82.9, left: 17.45, width: 1.1, height: 4
            },{
                top: 83.6, left: 17.5,  width: 1.1, height: 4
            },{
                top: 84.3, left: 17.55, width: 1.1, height: 4
            },


//            45
            {
               top: 90.3, left: 16, width: 1.1, height: 4,
            },{
               top: 91, left: 16.05, width: 1.1, height: 4,
            },{
               top: 91.7, left: 16.1, width: 1.1, height: 4,
            },{
               top: 92.4, left: 16.15, width: 1.1, height: 4,
            },{
               top: 93.1, left: 16.2, width: 1.1, height: 4,
            },{
               top: 91.1, left: 17.3, width: 1.1, height: 4,
            },{
               top: 91.8, left: 17.35, width: 1.1, height: 4,
            },{
               top: 92.5, left: 17.4, width: 1.1, height: 4,
            },{
               top: 93.2, left: 17.45, width: 1.1, height: 4,
            },{
               top: 93.9, left: 17.5, width: 1.1, height: 4,
            },


//            42
            {
               top: 71.15, left: 16, width: 1.1, height: 4
            },{
               top: 71.85, left: 16.05, width: 1.1, height: 4
            },{
               top: 72.55, left: 16.1, width: 1.1, height: 4
            },{
               top: 73.25, left: 16.15, width: 1.1, height: 4
            },{
               top: 73.95, left: 16.2, width: 1.1, height: 4
            },{
               top: 74.65, left: 16.25, width: 1.1, height: 4
            },{
               top: 71.85, left: 17.25, width: 1.1, height: 4
            },{
               top: 72.55, left: 17.3, width: 1.1, height: 4
            },{
               top: 73.25, left: 17.35, width: 1.1, height: 4
            },{
               top: 73.95, left: 17.4, width: 1.1, height: 4
            },{
               top: 74.65, left: 17.45, width: 1.1, height: 4
            },{
               top: 75.35, left: 17.5, width: 1.1, height: 4
            },


//            32
            {
//               top: 57.6, left: 15.9, width: 2.5, height: 4
//            },{
//               top: 59, left: 18.4, width: 2.5, height: 4
//            },{
               top: 58.4, left: 15.9, width: 1.2, height: 4
            },{
               top: 59.1, left: 15.95, width: 1.2, height: 4
            },{
               top: 59.1, left: 17.2, width: 1.2, height: 4
            },{
               top: 59.8, left: 17.25, width: 1.2, height: 4
            },{
               top: 59.8, left: 18.5, width: 1.2, height: 4
            },{
               top: 60.5, left: 18.55, width: 1.2, height: 4
            },{
               top: 60.5, left: 19.7, width: 1.2, height: 4
            },{
               top: 61.2, left: 19.75, width: 1.2, height: 4
            },


//            33
            {
//               top: 64.8, left: 15.9, width: 2.5, height: 4
//            },{
//               top: 66.2, left: 18.4, width: 2.5, height: 4
//            },{
               top: 65.7, left: 16.2, width: 1.1, height: 4
            },{
               top: 66.4, left: 16.25, width: 1.1, height: 4
            },{
               top: 66.4, left: 17.5, width: 1.1, height: 4
            },{
               top: 67.1, left: 17.55, width: 1.1, height: 4
            },{
               top: 67.1, left: 18.8, width: 1.1, height: 4
            },{
               top: 67.8, left: 18.85, width: 1.1, height: 4
            },{
               top: 67.8, left: 20, width: 1.1, height: 4
            },{
               top: 68.5, left: 20.05, width: 1.1, height: 4
            },




//            34
//            {
//               top: 60.5, left: 24.5, width: 2.5, height: 4
//            },{
//               top: 61.9, left: 27, width: 2.5, height: 4
//            },
            {
               top: 61.3, left: 24.5, width: 1.1, height: 4
            },
            {
               top: 62, left: 24.55, width: 1.1, height: 4
            },
            {
               top: 62, left: 25.8, width: 1.1, height: 4
            },
            {
               top: 62.7, left: 25.85, width: 1.1, height: 4
            },
            {
               top: 62.7, left: 27.1, width: 1.1, height: 4
            },
            {
               top: 63.4, left: 27.15, width: 1.1, height: 4
            },
            {
               top: 63.5, left: 28.45, width: 1.1, height: 4
            },
            {
               top: 64.2, left: 28.5, width: 1.1, height: 4
            },





//            30
//            {
//               top: 53.8, left: 24.7, width: 2.5, height: 4
//            },
//            {
//               top: 55.2, left: 27.2, width: 2.5, height: 4
//            },
            {
               top: 54.8, left: 24.9, width: 1.1, height: 4
            },
            {
               top: 55.5, left: 24.95, width: 1.1, height: 4
            },
            {
               top: 55.5, left: 26.2, width: 1.1, height: 4
            },
            {
               top: 56.2, left: 26.25, width: 1.1, height: 4
            },
            {
               top: 56.2, left: 27.5, width: 1.1, height: 4
            },
            {
               top: 56.9, left: 27.55, width: 1.1, height: 4
            },
            {
               top: 56.9, left: 28.7, width: 1.1, height: 4
            },
            {
               top: 57.6, left: 28.75, width: 1.1, height: 4
            },






//            31
//            {
//               top: 50.3, left: 15.9, width: 2.5, height: 4
//            },
//            {
//               top: 51.6, left: 18.4, width: 2.5, height: 4
//            },
            {
               top: 51.2, left: 16.1, width: 1.1, height: 4
            },
            {
               top: 51.9, left: 16.15, width: 1.1, height: 4
            },
            {
               top: 51.9, left: 17.3, width: 1.1, height: 4
            },
            {
               top: 52.6, left: 17.35, width: 1.1, height: 4
            },
            {
               top: 52.6, left: 18.6, width: 1.1, height: 4
            },
            {
               top: 53.3, left: 18.65, width: 1.1, height: 4
            },
            {
               top: 53.3, left: 19.9, width: 1.1, height: 4
            },
            {
               top: 54, left: 19.95, width: 1.1, height: 4
            },



//            17
            {
               top: 45.4, left: 16.2, width: 1.1, height: 4
            },
            {
               top: 46.1, left: 16.25, width: 1.1, height: 4
            },
            {
               top: 46.1, left: 17.5, width: 1.1, height: 4
            },
            {
               top: 46.8, left: 17.55, width: 1.1, height: 4
            },
            {
               top: 46.8, left: 18.7, width: 1.1, height: 4
            },
            {
               top: 47.5, left: 18.75, width: 1.1, height: 4
            },
            {
               top: 47.5, left: 19.9, width: 1.1, height: 4
            },
            {
               top: 48.2, left: 19.95, width: 1.1, height: 4
            },







//            16
//            {
//               top:37, left: 15.9, width: 2.5, height: 4
//            },
//            {
//               top: 38.3, left: 18.4, width: 2.5, height: 4
//            },
            {
               top: 37.9, left: 16.1, width: 1.1, height: 4
            },
            {
               top: 38.6, left: 16.15, width: 1.1, height: 4
            },
            {
               top: 38.6, left: 17.4, width: 1.1, height: 4
            },
            {
               top: 39.3, left: 17.45, width: 1.1, height: 4
            },
            {
               top: 39.3, left: 18.6, width: 1.1, height: 4
            },
            {
               top: 40, left: 18.65, width: 1.1, height: 4
            },
            {
               top: 40, left: 19.8, width: 1.1, height: 4
            },
            {
               top: 40.7, left: 19.85, width: 1.1, height: 4
            },




//            44
            {
               top: 85.8, left: 21.7, width: 1.1, height: 4
            },
            {
               top: 86.5, left: 21.75, width: 1.1, height: 4
            },
            {
               top: 87.2, left: 21.8, width: 1.1, height: 4
            },
            {
               top: 86.5, left: 23, width: 1.1, height: 4
            },
            {
               top: 87.2, left: 23.05, width: 1.1, height: 4
            },
            {
               top: 87.9, left: 23.1, width: 1.1, height: 4
            },
            {
               top: 87.2, left: 24.2, width: 1.1, height: 4
            },
            {
               top: 87.9, left: 24.25, width: 1.1, height: 4
            },
            {
               top: 88.6, left: 24.3, width: 1.1, height: 4
            },
            {
               top: 88, left: 25.5, width: 1.1, height: 4
            },
            {
               top: 88.7, left: 25.55, width: 1.1, height: 4
            },
            {
               top: 89.4, left: 25.6, width: 1.1, height: 4
            },
            {
               top: 88.7, left: 26.8, width: 1.1, height: 4
            },
            {
               top: 89.4, left: 26.85, width: 1.1, height: 4
            },
            {
               top: 90.1, left: 26.9, width: 1.1, height: 4
            },
            {
               top: 89.4, left: 28.1, width: 1.1, height: 4
            },
            {
               top: 90.1, left: 28.15, width: 1.1, height: 4
            },
            {
               top: 90.8, left: 28.2, width: 1.1, height: 4
            },




//            41
            {
               top: 71.4, left: 24.8, width: 1.1, height: 4
            },
            {
               top: 72.1, left: 24.85, width: 1.1, height: 4
            },
            {
               top: 72.8, left: 24.9, width: 1.1, height: 4
            },
            {
               top: 72.1, left: 26, width: 1.1, height: 4
            },
            {
               top: 72.8, left: 26.05, width: 1.1, height: 4
            },
            {
               top: 73.5, left: 26.1, width: 1.1, height: 4
            },
            {
               top: 72.8, left: 27.25, width: 1.1, height: 4
            },
            {
               top: 73.5, left: 27.3, width: 1.1, height: 4
            },
            {
               top: 74.2, left: 27.35, width: 1.1, height: 4
            },
            {
               top: 73.5, left: 28.5, width: 1.1, height: 4
            },
            {
               top: 74.2, left: 28.55, width: 1.1, height: 4
            },
            {
               top: 74.9, left: 28.6, width: 1.1, height: 4
            },
            {
               top: 74.2, left: 29.8, width: 1.1, height: 4
            },
            {
               top: 74.9, left: 29.85, width: 1.1, height: 4
            },
            {
               top: 75.6, left: 29.9, width: 1.1, height: 4
            },
            {
               top: 74.9, left: 31.05, width: 1.1, height: 4
            },
            {
               top: 75.6, left: 31.1, width: 1.1, height: 4
            },
            {
               top: 76.3, left: 31.15, width: 1.1, height: 4
            },




//            40
            {
               top: 76.7, left: 35.4, width: 1.1, height: 4
            },
            {
               top: 77.4, left: 35.45, width: 1.1, height: 4
            },
            {
               top: 78.1, left: 35.5, width: 1.1, height: 4
            },
            {
               top: 77.4, left: 36.6, width: 1.1, height: 4
            },
            {
               top: 78.1, left: 36.65, width: 1.1, height: 4
            },
            {
               top: 78.8, left: 36.7, width: 1.1, height: 4
            },
            {
               top: 78.1, left: 37.9, width: 1.1, height: 4
            },
            {
               top: 78.8, left: 37.95, width: 1.1, height: 4
            },
            {
               top: 79.5, left: 38, width: 1.1, height: 4
            },
            {
               top: 78.8, left: 39.2, width: 1.1, height: 4
            },
            {
               top: 79.5, left: 39.25, width: 1.1, height: 4
            },
            {
               top: 80.2, left: 39.3, width: 1.1, height: 4
            },
            {
               top: 79.5, left: 40.5, width: 1.1, height: 4
            },
            {
               top: 80.2, left: 40.55, width: 1.1, height: 4
            },
            {
               top: 80.9, left: 40.6, width: 1.1, height: 4
            },
            {
               top: 80.2, left: 41.8, width: 1.1, height: 4
            },
            {
               top: 80.9, left: 41.85, width: 1.1, height: 4
            },
            {
               top: 81.6, left: 41.9, width: 1.1, height: 4
            },




//            18
            {
               top: 41.5, left: 22.6, width: 1.1, height: 4
            },
            {
               top: 42.2, left: 22.65, width: 1.1, height: 4
            },
            {
               top: 42.2, left: 23.9, width: 1.1, height: 4
            },
            {
               top: 42.9, left: 23.95, width: 1.1, height: 4
            },
            {
               top: 42.9, left: 25.2, width: 1.1, height: 4
            },
            {
               top: 43.6, left: 25.25, width: 1.1, height: 4
            },
            {
               top: 43.6, left: 26.5, width: 1.1, height: 4
            },
            {
               top: 44.3, left: 26.55, width: 1.1, height: 4
            },



//            19
            {
               top: 42.4, left: 29.6, width: 1.1, height: 4
            },
            {
               top: 43.1, left: 30.9, width: 1.1, height: 4
            },
            {
               top: 43.8, left: 32.2, width: 1.1, height: 4
            },
            {
               top: 44.5, left: 33.4, width: 1.1, height: 4
            },




//            15
            {
               top: 34, left: 24.8, width: 1.2, height: 4
            },
            {
               top: 34.7, left: 24.85, width: 1.2, height: 4
            },
            {
               top: 34.7, left: 26.05, width: 1.2, height: 4
            },
            {
               top: 35.4, left: 26.1, width: 1.2, height: 4
            },
            {
               top: 35.4, left: 27.3, width: 1.2, height: 4
            },
            {
               top: 36.1, left: 27.35, width: 1.2, height: 4
            },
            {
               top: 36.1, left: 28.6, width: 1.2, height: 4
            },
            {
               top: 36.8, left: 28.65, width: 1.2, height: 4
            },



//            14
            {
               top: 36.6, left: 31.95, width: 1.2, height: 4
            },
            {
               top: 37.3, left: 33.25, width: 1.2, height: 4
            },
            {
               top: 38, left: 34.55, width: 1.2, height: 4
            },
            {
               top: 38.7, left: 35.85, width: 1.2, height: 4
            },
            {
               top: 39.4, left: 37.15, width: 1.2, height: 4
            },
            {
               top: 40.1, left: 38.45, width: 1.2, height: 4
            },




//            20
            {
               top: 45.6, left: 36.5, width: 1.2, height: 4
            },



//            29
            {
               top: 52.65, left: 33.4, width: 1.2, height: 4
            },




        ],
        //registerData: registerData
    };
    $scope.building = [];
    $scope.buildObjs = {};
    $scope.init = function() {
        for (var i=0,n=$scope.config.building.length; i<n; i++) {
//            var param = $scope.config.building[i],
//                val = {
//                    id: $scope.config.building[i].id,
//                    name: $scope.config.building[i].name,
//                    data: []
//                }, row;
            $scope.buildObjs[$scope.config.building[i].code] = new CreateBuilding(
                                    $scope.config.building[i].top, $scope.config.building[i].left,
                                    $scope.config.building[i].width, $scope.config.building[i].height);
//            for (var yi=1,yn=param.yNum; yi<=yn; yi++) {
//                row = [];
//                for (var xi=1,xn=param.xNum; xi<=xn; xi++) {
//                    row.push(xi);
//                }
//                val.data.push(row);
//            }
//            $scope.building.push(val);
        }
//        $scope.lightOn();
//        $interval(function(){
//            $http.get("/site/live-data-update").success(function(rsp) {
//                $scope.config.registerData = rsp;
//                $scope.lightOn();
//            });
//        }, 3000);
    }
//    $scope.lightOn = function() {
//        angular.forEach($scope.config.registerData, function(data, index) {
//            for (var i=0,n=data.length; i<n; i++) {
//                var x = parseInt(data[i].substr(-2)), y = parseInt(data[i].substr(0, data[i].length-2));
//                if (typeof $scope.buildObjs['building'+index]!='undefined') {
//                    $scope.buildObjs['building'+index].lightOn(y,x);
//                }
//            }
//        });
//    };
    var img = new Image();
    img.src = "/assets/img/light/runyuan.png";
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
            _li.parent().empty().append(_li.clone()).append(_li.clone()).append(_li.clone());
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

        $(".scrolltop").imgscroll({
            speed: 80,    //图片滚动速度
            amount: 0,    //图片滚动过渡时间
            width: 1,     //图片滚动步数
            dir: "up"   // "left" 或 "up" 向左或向上滚动
        });
    });
</script>
</body>
</html>