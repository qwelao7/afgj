require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';
    $(document).on('pageInit', '#data-login-success', function (e, id, page) {
        var keep = 3,
            t,
            tips = $('#tips');

        function times() {
            keep--;
            if (keep <= 0) {
                clearInterval(t);
                window.location.href = 'data-client-search.html';
            }
            tips.html(keep + '秒后自动跳转');
        };
        t = setInterval(times, 1000);

        var pings = env.pings;
        pings();
    });

    $.init();
})