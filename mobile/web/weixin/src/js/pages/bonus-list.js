require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#bonus-list", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        function loadData() {
            common.ajax('GET', '/order/bonus-list', {'order_money': 0}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);

                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,你暂无红包!</h3>";
                    container.append(template);
                }
            })
        }


        $('#back').on('click', function (e) {
           history.go(-1);
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});