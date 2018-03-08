require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-family", function (e, id, page) {
        var url = common.getRequest(),
            tpl = $('#tpl').html(),
            content = $('#content'),
            header = $('#header').html(),
            title = $('#title'),
            data = new Array();
        var token = '';
        token = url.token ? url.token : common.getCookie('openid');

        // 跨域ajax请求

        function loadFamily() {

            $.ajax({
                url: env.ajax_data + "/pes/house/" + url.id + "/owners?token=" + token,
                dataType: 'json',
                success: function (rsp) {
                    var info = {};
                    info.title = window.localStorage.getItem('data_detail');
                    var htm = juicer(header, info);
                    title.append(htm);
                    if (rsp.status == 200) {
                        var data1 = {};
                        $.each(rsp.data, function (index, item) {
                            data[index] = item;
                        })
                        data1.data = data;
                        console.log(data);
                        var html = juicer(tpl, data1);
                        content.append(html);

                        $.each(data, function (index, item) {
                            if (data[index].identity == '户主') {
                                window.localStorage.setItem('data_house_owner', JSON.stringify(item));
                            }
                        })
                    } else {
                        content.append('<h5 style="text-align: center">还没有业主信息，请添加！</h5>')
                    }


                },
                error: function () {
                    window.location.href = 'data-input-auth.html';
                },
            });
        };


        //返回
        $(page).on('click', '#back', function () {
            var id = window.localStorage.getItem('data_projectId');
            window.location.href = 'data-address-list.html?id=' + id;
        });


        /**
         * 跳转编辑页面
         */
        $(document).on('click', '.account-item', function () {
            var self = $(this),
                id = self.data('id');
            $.each(data, function (index, item) {
                if (data[index].customer_code == id) {
                    window.localStorage.setItem('data_owner', JSON.stringify(item));
                }
            })
            window.localStorage.setItem('data_roomCode', url.id);
            window.location.href = 'data-family-info.html?id=' + id;

        });
        /**
         * 新建家庭成员
         */
        $(document).on('click', '.bar-tab', function () {
            window.location.href = 'data-family-add.html?id=' + url.id;
        });


        loadFamily();
        var pings = env.pings;
        pings();
    });

    $.init();
});
