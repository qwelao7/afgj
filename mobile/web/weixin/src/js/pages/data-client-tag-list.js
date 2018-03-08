require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-client-tag-list", function (e, id, page) {
        var url = common.getRequest(),
            tpl = $('#tpl').html(),
            content = $('#content'),
            header = $('#header').html(),
            title = $('#title'),
            data = [];
        var token = '';
        token = url.token ? url.token : common.getCookie('openid');

        // 跨域ajax请求

        function loadFamily() {
            var info = JSON.parse(window.localStorage.getItem('data_owner_client'));
            var html = juicer(header, info);
            title.append(html);
            $.ajax({
                url: env.ajax_data + "/pes/owner/" + url.id + "/tags?token=" + token,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        $.each(rsp.data, function (index, item) {
                            data[index] = item;
                        })
                        var data1 = {};
                        data1.data = data;
                        console.log(data1);
                        var html = juicer(tpl, data1);
                        content.append(html);
                    }
                },
                error: function () {
                    window.location.href = 'data-input-auth.html';
                },
            });
        };


        //返回
        $(page).on('click', '#back', function () {
            window.location.href = 'data-client-info.html?id=' + url.id;
        });


        loadFamily();
        var pings = env.pings;
        pings();
    });

    $.init();
});
