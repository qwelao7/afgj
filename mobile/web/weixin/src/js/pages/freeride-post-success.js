require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';
    $(document).on('pageInit', '#freeride-post-success', function(e, id, page) {
        var loupanId = localStorage.getItem('loupanId');
        if(!loupanId) loupanId = 0;

        var keep = 3,
            t,
            tips = $('#tips');
        function times() {
            keep--;
            if(keep <= 1) {
                clearInterval(t);
                window.location.href = 'freeride-list.html?id=' + loupanId;
            }
            tips.html(keep + '秒后自动跳转到顺风车首页');
        };
        var t = setInterval(times, 1000);

        var pings = env.pings;pings();
    });

    $.init();
})