require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';
    $(document).on('pageInit', '#under-maintenance', function(e, id, page) {
        var url = common.getRequest();
        if(!url.id) url.id = 0;

        var keep = 3,
            t,
            tips = $('#tips');
        function times() {
            keep--;
            if(keep < 1) {
                clearInterval(t);
                window.location.href = 'square-tab-index.html?id=' + url.id;
            }
            tips.html(keep + '秒后自动跳转到社区首页');
        };
        var t = setInterval(times, 1000);

        $('#back').on('click', function() {
            window.location.href = 'square-tab-index.html?id=' + url.id;
        });

        var pings = env.pings;pings();
    });

    $.init();
})/**
 * Created by nancy on 2017/9/29.
 */
