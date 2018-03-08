require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#error-list", function (e, id, page) {
        var url = common.getRequest();

        var container = $('#container'),
            tpl = $('#tpl').html();

        function loadData() {
            common.ajax('GET', '/feedback/maintain-list', {
                'work_id': url.id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var params = {};
                    params.list = rsp.data.info;
                    params.work_id = url.id;
                    
                    var html = juicer(tpl, params);

                    container.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无数据信息!</h3>";
                    container.append(template);
                }
            })
        }

        $('.error-list').live('click', function() {
            var self = $(this),
                id = self.data('id');

            location.href = 'error-detail.html?id=' + id + '&work_id=' + url.id;
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});