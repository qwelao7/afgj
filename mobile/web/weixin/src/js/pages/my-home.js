require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#my-account", function (e, id, page) {
        /** http **/
        var http = "http://"+ location.host + '/site';

        var tpl = $('#tpl').html(),
            container = $('#container');

        common.ajax('GET', '/user/info', {}, false, function(rsp) {
            if(rsp.data.code == 0) {
                var data = rsp.data.info.list,
                    html = juicer(tpl, data);
                container.prepend(html);
            } else {
                $.alert('获取信息失败,请重试!');
            }
        })

        /** 跳转用户详情页 **/
        $(page).on('click', '#account', function() {
            event.preventDefault();
            window.location.href = 'personal-info.html';
        });

        /** 跳转房产认证页面 **/
        $(page).on('click', '#estate', function() {
            window.location.href = 'estate-manage.html';
        });

        /** 跳转设备管理界面 **/
        $(page).on('click', '#equipment', function() {
            window.location.href = '';
        });

        /** 跳转订单管理界面 **/
        $(page).on('click', '#order', function() {
            window.location.href = http + '/#/order';
        });

        /** 跳转财务管理界面 **/
        $(page).on('click', '#finance', function() {
            window.location.href = '';
        });

        /** 跳转投诉管理界面 **/
        $(page).on('click', '#complaint', function() {
            window.location.href = '';
        });

        /** 弹出二维码 **/
        $(page).on('click','#code', function() {
            event.stopPropagation();
            console.log(1);
        })

        var pings = env.pings;pings();
    });
    $.init();
});
