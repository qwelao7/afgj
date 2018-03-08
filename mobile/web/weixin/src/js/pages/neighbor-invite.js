require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#neighbor-invite", function (e, id, page) {
        var tpl = $('#tpl').html(),
            container = $('#container');

        //获取localstorage
        var storage = window.localStorage,
            openId = storage.getItem('openId');

        //自定义模板函数
        common.img();

        $.ajax({
            type: 'POST',
            url: common.WEBSITE_API + '/neighbour/invite?access_token=' + openId,
            success: function (rsp) {
                var data = rsp.data.info,
                    html = juicer(tpl, data);
                container.append(html);

                //设置跳转storage
                var skip = {
                    num: data.num,
                    money: data.money
                }
                common.saveStorage(skip);
            },
            error: function (xhr, type) {
                $.alert('很抱歉,服务器失去联系,请等待...');
            }
        });

        //跳转邀请记录页面
        $(page).on('click', '#log', function () {
            window.location.href = 'neighbor-invitation-record.html';
        })

        var pings = env.pings;pings();
    });

    $.init();
});
