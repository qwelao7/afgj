require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#equip-care-list", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            nav = $('#nav').html(),
            container = $('#container');

        var path;

        function loadData() {
            common.ajax('GET', '/facilities/equipment-notification', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);
                } else {
                    var template = "<h3 style='margin-top: 4rem;text-align: center;'>很抱歉,暂无数据</h3>";
                    container.append(template);
                }

                var htm = juicer(nav, {});
                container.after(htm);
            })
        }

        $('#add').live('click', function () {
            path = '?id=' + url.id + '&address=' + url.address;
            path = (url.refer) ? path + '&refer=detail' : path;

            location.href = 'equip-care-add.html' + path;
        });

        $('#back').on('click', function () {
            if (url.refer && url.refer != '') {
                path = '?id=' + url.id + '&address=' + url.address;
                location.href = 'equip-detail.html' + path;
            } else {
                path = '?id=' + url.address;
                location.href = 'equip-list.html' + path;
            }
        });
        
        loadData();

        var pings = env.pings;
        pings();

    });

    $.init();
});
