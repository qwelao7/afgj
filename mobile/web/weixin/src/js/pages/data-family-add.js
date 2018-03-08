require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-family-add", function (e, id, page) {
        var url = common.getRequest(),
            tpl = $('#tpl').html(),
            content = $('#content'),
            data = {},
            info = {};
        var token = '';
        token = url.token ? url.token : common.getCookie('openid');

        function loadData() {
            info = JSON.parse(window.localStorage.getItem('data_house_owner'));
            console.log(info);
            var html = juicer(tpl, info);
            content.append(html);
        }


        // 提交表单
        $(page).on('click', '#submit', function () {
            data.roomCode = url.id;
            data.projectCode = window.localStorage.getItem('data_projectId');
            data.customerCode = info.customer_code;
            data.name = $('#name').val();
            data.relationship = $('#relation').val();
            data.mobile = $('#tel').val();
            $.ajax({
                    url: env.ajax_data + "/pes/owner?token=" + token,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (rsp) {
                        if (rsp.status == 200) {
                            $.alert('业主添加成功', function () {
                                window.location.href = 'data-family.html?id=' + url.id;
                            });
                        } else {
                            $.alert('很抱歉！' + rsp.msg);
                        }

                    },
                    error: function () {
                        window.location.href = 'data-input-auth.html';
                    },
                }
            )
        });


        //返回
        $(page).on('click', '#back', function () {
            history.go(-1);
        });


        loadData();
        var pings = env.pings;
        pings();
    });

    $.init();
});
