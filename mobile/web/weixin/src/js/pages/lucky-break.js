require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#lucky-break", function (e, id, page) {

        var endTime;
        var beginTime;
        var isJoin = false;


        /** 绑定按钮点击 **/
        $(page).on('click', '#join-game', function () {
            if (isJoin) {
                window.location.href = "lucky-result.html";
                return;
            }
            $.ajax({
                type: 'GET',
                url: common.WEBSITE_API + '/redenvelope/join',
                dataType: 'json',
                beforeSend: function (xhr, settings) {
                    $('#join-game').prop('disabled', true);
                },
                success: function (rsp) {
                    if (rsp.data.code == 0 || rsp.data.code == 1) {
                        window.location.href = "lucky-result.html";
                    } else {
                        $.alert(rsp.data.message);
                        storage.setItem('join-game', 0);
                    }
                },
                error: function (xhr, type) {
                    $.alert('很抱歉,服务器失去联系,请等待...');
                    $('#join-game').prop('disabled', false);
                }
            });


        });

        loadData();
        var pings = env.pings;
        pings();
    });

    $.init();
});
