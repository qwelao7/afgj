require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#neighbor-invitation-record", function (e, id, page) {
        var tpl = $('#tpl').html(),
            container = $('#container');

        //获取localStorage
        var storage = window.localStorage,
            openId = storage.getItem('openId'),
            skip = storage.getItem('skip');
        skip = JSON.parse(skip);

        //自定义模板函数
        common.img();

        $.ajax({
           type: 'POST',
           url: common.WEBSITE_API + '/neighbour/invitelog?access_token=' + openId,
           success: function(rsp) {
               var data = rsp.data.info;
               var result = {
                   list: data,
                   skip: skip,
               }
               var html = juicer(tpl, result);
               container.append(html);
           },
           error: function(xhr, type) {
               $.alert('很抱歉,服务器失去联系,请等待...');
           }
       })

        var pings = env.pings;pings();
    });

    $.init();
});
