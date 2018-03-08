require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#error-detail", function (e, id, page) {
        var url = common.getRequest();

        var container = $('#container'),
            tpl = $('#tpl').html(),
            nav = $('#nav').html();

        var case_id = 0;

        common.img();

        function loadData() {
            common.ajax('GET', '/feedback/maintain-detail', {
                id: url.id,
                work_id: url.work_id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data['work_id'] =  url.work_id;

                    case_id = data.detail.case_id;

                    var html = juicer(tpl, data),
                        htm = juicer(nav, data);

                    container.append(html);
                    $('header').after(htm);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无数据信息!</h3>";
                    container.append(template);
                }
            })
        }

        $('#submit').live('click', function () {
            location.href = 'error-manage-report.html?id=' + case_id + '&work_id=' + url.work_id;
        });

        $('#back').on('click', function () {
            location.href = 'error-list.html?id=' + url.work_id;
        });
        
        $('.error-img-container').live('click', function() {
            var self = $(this),
                link = self.data('link');

            if (link.indexOf('.html') != -1) {
                $.alert('很抱歉,暂不支持该格式预览!', '预览失败');
            } else {
                location.href = link;
            }
        });
        
        $('.to-edit').live('click', function () {
            var self = $(this),
                parent = self.parent(),
                logId = parent.data('logid');

            var path = '?log_id=' + logId;

            location.href = 'error-manage-report.html' + path;
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});