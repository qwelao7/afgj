require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#news-loupan", function (e, id, page) {
        var tpl = $('#tpl').html(),
            container = $('#container');

        common.img();
        var format = function (data) {
            return common.formatTime(data);
        };
        juicer.register('format', format);

        //加载数据
        function loadData() {
            common.ajax('GET', '/official/list', {}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data,
                        html = juicer(tpl, data);

                    container.append(html);
                }else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无相关数据!</h3>";
                    container.append(template);
                }
            })
        }


        /**
         * 跳转楼盘信息列表页
         */
        $(document).on('click', '.news-loupan', function() {
            var self = $(this),
                id = self.data('id'),
                data = {};

            data.name = self.find('h2.item-two-line-title').text();
            common.saveStorage(data);

            window.location.href = 'news-list.html?id=' + id;
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function() {
            window.location.href = common.ectouchPic;
        });

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
