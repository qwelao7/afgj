require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#points-expire', function (e, id, page) {
        var tpl = $('#tpl').html(),
            container = $('#container');
        
        var timeFormat = function (data) {
            var arr = data.split(' ');
            return arr[0];
        };
        juicer.register('timeFormat', timeFormat);
        
        function loadData() {
            common.ajax('GET', '/points/expire', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data,
                        html = juicer(tpl, data);

                    container.append(html);

                    init();
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据信息!</h3>";
                    container.append(template);
                }
            })
        }

        function init () {
            var first = $('.expire_item').first();
            first.find('.iconfont').removeClass('icon-down').addClass('icon-up');
            first.find('.normal-list').next().find('.expire_ext_item').removeClass('hide');
        }

        $('.normal-list').live('click', function () {
           var self = $(this),
               icon = self.find('.iconfont'),
               brother = self.next();

            if (icon.hasClass('icon-down')) {
                icon.removeClass('icon-down').addClass('icon-up');
            } else {
                icon.removeClass('icon-up').addClass('icon-down');
            }

            brother.find('.expire_ext_item').toggleClass('hide');
        });
        
        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
})