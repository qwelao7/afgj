require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#receive-ware", function (e, id, page) {
        var url = common.getRequest(),
            status = true;

        $(document).on('click', '.go-back', function() {
            window.location.href = 'unlock-event.html';
        });

        $(document).on('click', '#agree', function() {
            if(!status) return;
            status = false;

            common.ajax('POST', '/unlock/receive', {'id': url.id}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    window.location.href = 'event-borrow-success.html';
                }else {
                    $.alert('很抱歉,领取失败,请重试!', '领取失败', function() {
                        status = true;
                    })
                }
            });
        });

        var pings = env.pings;pings();
    });

    $.init();
});
