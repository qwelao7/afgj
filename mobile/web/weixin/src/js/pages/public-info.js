require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    /**
     * id -> commuity_id
     */
    $(document).on("pageInit", "#public-info", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        var template = "<div class='tips' style='text-align: center;height: 100%;'>很抱歉,数据错误!</div>";

        function loadData() {
            common.ajax('GET', '/community/phone-community', {
                communityId: url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);
                } else {
                    container.append(template);
                }
            })
        }


        $('#back').on('click', function () {
            location.href = 'square-tab-index.html?id=' + url.id;
        });

        $('#add').on('click', function () {
            location.href = 'public-info-add.html?id=' + url.id;
        });

        $('#error').on('click', function () {
            location.href = 'public-info-error.html?id=' + url.id;
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});