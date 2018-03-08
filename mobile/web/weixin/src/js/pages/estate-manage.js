require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#estate-manage", function (e, id, page) {
        /** 参数 **/
        var tpl = $('#tpl').html(),
            ext = $('#ext').html(),
            container = $('#container');
        
        var url = common.getRequest();

        /** 自定义模板函数 **/
        var transform = function(data) {
            return (data == 1)?'已认证':'未认证';
        };
        juicer.register('transform', transform);
        
        common.ajax('GET', '/house/index', {}, true, function(rsp) {
            if(rsp.data.code == 0) {
                var data = rsp.data,
                    html = juicer(tpl, data),
                    htmlExt = juicer(ext, data);
                container.append(html);
                container.append(htmlExt);
            } else {
                var template = "<h3 style='margin-top: 4rem;text-align: center;'>您暂无房产,请添加房产...</h3>";
                container.append(template);
            }
        });

        /** 跳转至房产认证界面 **/
        $(page).on('click', '.house', function() {
            var addressId = $(this).data('id');
            window.location.href = 'estate-info.html?id=' + addressId + '&fang=1';
        });
        $(page).on('click', '.house_temp', function() {
            var addressId = $(this).data('id');
            window.location.href = 'estate-info.html?id=' + addressId + '&fang=2';
        });

        /** 创建房产 **/
        $(page).on('click', '#create', function() {
            window.location.href = 'city-list.html';
        });

        /**返回**/
        $(page).on('click', '#back', function() {
            location.href = common.ectouchUrl + '&c=user&a=index&params';
        });

        var pings = env.pings;pings();

    });

    $.init();
});
