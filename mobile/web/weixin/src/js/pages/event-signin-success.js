require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event-signin-success", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            content = $('#content');
        
        function loadData() {
            common.ajax('GET', '/events/sign-in', {
                id: url.id
            }, true, function(rsp) {
                if (rsp.data.code == 0 || rsp.data.code == 101) {
                    var html = juicer(tpl, rsp.data);

                    content.append(html);
                } else if (rsp.data.code == 103){
                    $.alert('很抱歉,您还未报名本次活动,请前往报名!', '签到失败', function () {
                        location.href = 'event-detail.html?id=' + url.id;
                    })
                } else {
                    $.alert('很抱歉,签到失败!', '签到失败', function () {
                        location.href = 'event-detail.html?id=' + url.id;
                    })
                }
            })
        }

        $('#back').on('click', function() {
            location.href = 'event-detail.html?id=' + url.id + '&type=1';
        });
        
        loadData();
        
        var pings = env.pings;pings();
    });

    $.init();
});