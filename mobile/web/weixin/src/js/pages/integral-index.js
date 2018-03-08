require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#integral-index", function (e, id, page) {
        function loadData() {
            common.ajax('GET', '/points/get-duiba-url', {}, true, function (rsp) {
                if(rsp.data.code == 0) {
                    var result = rsp.data.info;

                    location.href = result;
                }else {
                    $.alert('很抱歉,跳转失败!', '跳转失败', function () {
                        history.back();
                    });
                }
            })
        }

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});