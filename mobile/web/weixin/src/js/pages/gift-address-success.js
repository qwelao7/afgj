require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';
    $(document).on('pageInit', '#gift-address-success', function(e, id, page) {
        var http = "http://"+ location.host;
        var url = common.getRequest();
        var keep = 3, 
            t,
            tips = $('#tips');
        function times() {
            keep--;
            if(keep <= 0) {
                clearInterval(t);
                window.location.href = 'http://www.huilaila.net/order-detail.html?classify=1&id='+url.id;
            }
            tips.html(keep + '秒后自动跳转至订单详情');
        };
        var t = setInterval(times, 1000);

        var pings = env.pings;pings();
    });

    $.init();
})