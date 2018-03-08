require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#article", function (e, id, page) {
        //url
        var url = common.getRequest();

        var container = $('#container'),
            header = $('#header'),
            tpl = $('#tpl').html(),
            height = container.height();
        height = height + 'px';
        var template = "<div class='tips' style='text-align: center;height: 100%;'>很抱歉,文章不存在,请返回!</div>";

        common.ajax('GET','/forum/article', {id: url.id}, true, function(rsp) {
            if(rsp.data.code == 0) {
                var val = rsp.data.info;

                if(val.content != '') {
                    container.append(val.content);
                }else {
                    container.append(template);
                    $('.tips').css('line-height', height);
                }

                var html = juicer(tpl, val);
                header.prepend(html);
            }else {
                container.append(template);
                $('.tips').css('line-height', height);

            }
        });

        var pings = env.pings;pings();
    });

    $.init();
});