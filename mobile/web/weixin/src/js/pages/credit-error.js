require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';

    /**
     * 报错信息
     * 1-sdk initialize error sdk初始化错误
     * 2-data  error 数据出错
     * 3-authorize failed 认证失败
     * 4-callback data error 回调数据出错
     * 5-authorized user 已认证用户
     * 6-get user score fail 获取用户信用分失败
     */
    $(document).on('pageInit', '#credit-error', function(e, id, page) {
        var url = common.getRequest(),
            errorContainer = $('#error_msg');

        switch(url.error) {
            case '1': errorContainer.html('很抱歉,sdk初始化错误!');
                    break;
            case '2': errorContainer.html('很抱歉,获取数据错误!');
                    break;
            case '3': errorContainer.html('很抱歉,认证失败!');
                    break;
            case '4': errorContainer.html('很抱歉,回调数据错误!');
                    break;
            case '5': errorContainer.html('很抱歉,您已完成芝麻信用授权认证!');
                    break;
            case '6': errorContainer.html('很抱歉,获取用户信用分失败!');
                    break;
            default: errorContainer.html('很抱歉,芝麻信用授权失败!');
        }

        var pings = env.pings;pings();
    });

    $.init();
})