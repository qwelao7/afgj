require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#points-index', function (e, id, page) {
        var tpl = $('#tpl').html(),
            container = $('#container');

        function loadData() {
            common.ajax('GET', '/points/index', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);
                }
            })
        }

        $('#all').live('click', function() {
            window.location.href = 'points-record.html?type=1';
        });

        $('#collect').live('click', function() {
            window.location.href = 'points-record.html?type=2';
        });

        $('#used').live('click', function() {
            window.location.href = 'points-record.html?type=3';
        });

        $('#expire').live('click', function() {
            window.location.href = 'points-expire.html';
        });

        $('#back').live('click', function() {
            window.location.href = common.ectouchUrl + '&c=user&a=index';
        });

        $('#share').live('click', function () {
            window.location.href = 'points-share.html';
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});