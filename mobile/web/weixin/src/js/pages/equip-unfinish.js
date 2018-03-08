require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#equip-unfinish", function (e, id, page) {
        var url = common.getRequest();
        /**自定义模板**/
        common.img();
        var format = function(data) {
            var arr = data.split('-');
            return data = arr[1] + '月' + arr[2] + '日';
        };
        juicer.register('format', format);

        /**定义变量**/
        var tpl = $('#tpl').html(),
            container = $('#container');

        /**加载数据**/
        function loadData() {
            common.ajax('GET', '/facilities/unfinish', {'id': url.id}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    container.after(html);
                } else {
                    $.alert('很抱歉,服务器失去连接,请稍后...');
                }
            });
        }

        /**
         * 返回
         */
        $(document).on('click', '#back', function() {
            window.location.href = 'equip-list.html?id=' + url.id;
        });

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
