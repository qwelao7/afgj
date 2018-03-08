require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#map", function (e, id, page) {
        var url = common.getRequest();
        
        /** 渲染地图 **/
        function renderMap() {
            var map = new BMap.Map("container");
            var point = new BMap.Point(url.long, url.lat);
            map.centerAndZoom(point,12);
            // 创建地址解析器实例
            var myGeo = new BMap.Geocoder();
            // 将地址解析结果显示在地图上,并调整地图视野
            myGeo.getPoint(url.kw, function(point){
                if (point) {
                    map.centerAndZoom(point, 16);
                    map.addOverlay(new BMap.Marker(point));
                }else{
                    $.alert("您选择地址没有解析到结果!");
                }
            });
        }
        
        renderMap();

        var pings = env.pings;
        pings();
    });

    $.init();
});
