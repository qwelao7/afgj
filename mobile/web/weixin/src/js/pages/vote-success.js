require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';

    $(document).on('pageInit', '#vote-success', function(e, id, page) {
        var url = common.getRequest();

        //返回按钮
        $(page).on('click', '#back', function() {
            if(window.history.length > 2) {
                window.location.href = "bbs-detail.html?id=" + url.m_id;
            }else {
                window.location.href = common.ectouchPic;
            }
        })

        var pings = env.pings;pings();
    });

    $.init();
})