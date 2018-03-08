require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';
    $(document).on('pageInit', '#market-success', function(e, id, page) {
        var url = common.getRequest();

        var http = "http://"+ location.host;
        var keep = 3, 
            t,
            tips = $('#tips');
        function times() {
            keep--;
            if(keep <= 0) {
                clearInterval(t);
                window.location.href = 'market-list.html?id=' + url.id + '&classify=' + url.classify;
            }
            tips.html(keep + '秒后跳转至小市首页');
        };
        var t = setInterval(times, 1000);

        var pings = env.pings;pings();
    });

    $.init();
})