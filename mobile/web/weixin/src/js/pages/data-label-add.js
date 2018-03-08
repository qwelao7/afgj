require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-label-add", function (e, id, page) {
        var url = common.getRequest(),
            data = {};
        var token = '';
        token = url.token ? url.token : common.getCookie('openid');


        // 提交表单
        $(page).on('click', '#submit', function () {
            data.customerCode = url.id;
            data.tagTitle = $('#tagName').val();
            data.tagValue = $('#tagValue').val();
            $.ajax({
                url: env.ajax_data + "/pes/tag?token=" + token,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status = 200) {
                        $.alert('标签添加成功', function () {
                            window.location.href = 'data-family-info.html?id=' + url.id;
                        });
                    } else {
                        $.alert('标签添加失败！'+rsp.msg);
                    }

                },
                error: function () {
                    window.location.href = 'data-input-auth.html';
                },
            })
        });


        //返回
        $(page).on('click', '#back', function () {
            history.go(-1);
        });

        var pings = env.pings;
        pings();
    });

    $.init();
});
