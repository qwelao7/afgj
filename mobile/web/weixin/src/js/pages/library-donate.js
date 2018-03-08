require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#library-donate", function (e, id, page) {
        var tpl = $('#tpl').html(),
            content = $('#content');

        function loadData() {
            common.ajax('GET', '/library/donate', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = {};
                    data.code = rsp.data.info;
                    var html = juicer(tpl, data);

                    content.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,数据出现错误!</h3>";
                    content.append(template);
                }
            })
        }

        $(document).on('click', '#back', function() {
            window.location.href = 'library-index.html';
        })

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
