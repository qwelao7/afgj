require('../../css/style.css');
require('../../css/index.css');
var QRCode = require('../lib/qrcode.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#estate-share", function (e, id, page) {
        var url = common.getRequest();

        var list = $('#list').html(),
            content = $('#content');

        var status = true;
        
        function renderCode(data) {
            new QRCode('qrcode', {
                text: 'http://' + location.host + '/confirm-tpl.html?type=4&id=' + url.id,  // 加入房产认证
                width: 152,
                height: 152,
                colorDark : '#000000',
                colorLight : '#ffffff',
                correctLevel : QRCode.CorrectLevel.H
            });
        }

        function loadData() {
            common.ajax('GET', '/house/share-index', {
                addressId: url.id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(list, data);
                    content.append(html);

                    renderCode(data);
                } else {
                    var template = "<h3 style='margin-top: 4rem;text-align: center;'>很抱歉,数据错误!</h3>";
                    content.append(template);
                }
            })
        }

        $(document).on('click', '.employee-btn', function() {
            var self = $(this),
                parent = self.parents('.user-item'),
                address_id = parent.data('address_id');

            if (!status) return false;
            status = false;

            common.ajax('POST', '/house/delete', {
                id: address_id,
                type: 1
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('该成员已被移出房产', '删除成功', function() {
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
