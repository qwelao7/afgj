require('../../css/style.css');
require('../../css/index.css');
var QRCode = require('../lib/qrcode.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event-employee", function (e, id, page) {
        var url = common.getRequest();

        var content = $('#content'),
            tpl = $('#list').html();

        var status = true;

        function loadData() {
            common.ajax('GET', '/events/event-admin-list', {
                'id': url.id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    content.append(html);
                } else {
                    var template = "<h3 class='grey' style='text-align: center;margin-top: 4rem;'>暂无工作人员!</h3>";
                    content.append(template);
                }
            });

            renderCode();
        }

        function renderCode() {
            new QRCode('qrcode', {
                text: 'http://' + location.host + '/confirm-tpl.html?type=1&id=' + url.id,
                width: 152,
                height: 152,
                colorDark : '#000000',
                colorLight : '#ffffff',
                correctLevel : QRCode.CorrectLevel.H
            });
        }

        $(document).on('click', '.employee-btn', function() {
            var self = $(this),
                parent = self.parents('.user-item'),
                u_id = parent.data('id');

            if (!status) return false;
            status = false;

            common.ajax('GET', '/events/delete-event-admin', {
                id: url.id,
                user_id: u_id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('该工作人员已被移出工作组', '删除成功', function() {
                        status = true;
                        parent.remove();
                    })
                } else {
                    $.alert('很抱歉,删除失败!失败原因:' + rsp.data.message, '删除失败', function() {
                        status = true;
                    })
                }
            });
        });

        loadData();
        
        var pings = env.pings;pings();
    });

    $.init();
});