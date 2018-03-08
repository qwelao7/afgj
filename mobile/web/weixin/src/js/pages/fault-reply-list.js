require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#fault-reply-list", function (e, id, page) {
        var url = common.getRequest();

        var container = $('#container'),
            tpl = $('#tpl').html();

        var format = function (data) {
            data = data.replace(/\-/g, '/');

            data = new Date(data).getTime() / 1000;

            return common.formatTime(data);
        };
        juicer.register('format', format);

        common.img();

        function loadData() {
            common.ajax('GET', '/feedback/maintain-apply-list', {
                id: url.work_id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无服务反馈信息!</h3>";
                    container.append(template);
                }
            })
        }

        $('.route-cancel').live('click', function() {
            var self = $(this),
                id = self.data('id');

            var path = '?id=' + id + '&community_id=' + url.id;
            path = (url.ref != undefined) ? path + '&ref=' + url.ref : path;

            location.href = 'fault-reply-detail.html' + path;
        });

        $('#back').on('click', function() {
            var path = '?id=' + url.id;
            path = (url.ref != undefined) ? path + '&ref=' + url.ref : path;

            location.href = 'fault-feedback.html' + path;
        });

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});