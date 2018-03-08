require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#mix-xmasparty", function (e, id, page) {
        var url = common.getRequest();
        var token = '';
        token = common.getCookie('openid');

        function loadData() {

        }

        $(document).on('click', '#submit', function () {
            var code = $('#mix-ipt').val();
            common.ajax('POST', '/events/z-h-g-events', {'code': code}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    window.location.href = 'mix-confirm.html?id=' + rsp.data.info.type;
                } else {
                    $.alert("很抱歉," + rsp.data.msg + ",请重试!");
                }
            });
        });


        if (token) {
            loadData();
        } else {
            common.ajax('GET', '/order/index', {}, true, function () {
                loadData();
            })
        }
        var pings = env.pings;
        pings();
    });

    $.init();
});
