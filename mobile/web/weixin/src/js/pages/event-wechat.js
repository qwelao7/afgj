require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';
    
    $(document).on("pageInit", "#event-wechat", function (e, id, page) {
        var url = common.getRequest();

        $('#back').on('click', function () {
            location.href = 'event-detail.html?id=' + url.event_id + '&type=1';
        });

        var pings = env.pings;
        pings();
    });

    $.init();
});