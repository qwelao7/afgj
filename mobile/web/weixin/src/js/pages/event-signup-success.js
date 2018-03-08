require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';
    $(document).on('pageInit', '#event-signup-success', function(e, id, page) {
        var url = common.getRequest();

        var http = "http://"+ location.host;
        var keep = 3, 
            t,
            tips = $('#tips');
        function times() {
            keep--;
            if(keep <= 0) {
                clearInterval(t);
                var path = (url.refer) ? '&id=' + url.refer : '';
                window.location.href = 'event-list.html' + path;
            }
            tips.html(keep + '秒后自动跳转到活动首页');
        };
        var t = setInterval(times, 1000);

        var pings = env.pings;pings();
    });

    $.init();
})