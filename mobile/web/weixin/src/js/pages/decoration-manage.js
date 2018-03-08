require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#decoration-manage", function (e, id, page) {

        //模板
        var tpl = $('#tpl').html(),
            container = $('#container');

        //模板函数
        common.img();

        function loadData() {
            common.ajax('GET', '/decorate/index', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    container.append(html);
                } else {
                    container.append("<h3 style='text-align: center;margin-top: 4rem;'>您暂无装修计划,请创建!</h3>");
                }
            });
        }

        loadData();

        //跳转详情页面
        $('.detail').live('click', function () {
            var self = $(this),
                id = self.data('id'),
                address_id = self.data('address_id'),
                desc = self.data('desc');

            window.localStorage.setItem('decorateDetail', desc);

            var path = '?id=' + id + '&address_id=' + address_id + '&type=1';
            window.location.href = 'decoration-detail.html' + path;
        });

        $('#back').on('click', function () {
            window.location.href = common.ectouchUrl + '&c=user&a=index&params';
        });

        var pings = env.pings;pings();
    });

    $.init();
});
