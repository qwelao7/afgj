require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#donation-index", function (e, id, page) {
        var url = common.getRequest();

        var num = $('#number').html(),
            tpl = $('#tpl').html(),
            row = $('#row'),
            detail = $('#detail'),
            list = $('#list'),
            event_id = '';

        common.img();

        $('#back').live('click', function () {
            history.go(-1);
        });

        function loadData() {
            common.ajax('GET', '/benefit/detail', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    event_id = data.id;
                    data.percent_num = data.percent > 100.0 ? 100.0 : data.percent;
                    var html = juicer(num, data),
                        htm = juicer(tpl, data);
                    row.prepend(html);
                    list.append(htm);
                    detail.append(data.pb_detail);
                } else {
                    var template = "<h3 style='margin-top: 4rem;text-align: center;'>很抱歉,暂无数据</h3>";
                    list.prepend(template);
                }
            })
        }


        $('#submit').live('click', function () {
            window.location.href = 'donation-pay.html?id=' + event_id;
        });


        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});
