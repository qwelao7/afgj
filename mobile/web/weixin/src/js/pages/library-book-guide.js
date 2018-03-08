require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#library-book-borrow", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            header = $('#header').html(),
            container = $('#container');
        
        common.img();

        //type 0-归还 1-借阅
        function loadData() {
            common.ajax('GET', '/library/bookshelf-detail', {'id': url.id}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data['type'] = url.type;

                    var html = juicer(tpl, data),
                        htm = juicer(header, data);

                    container.append(html);
                    container.before(htm);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无具体信息!</h3>";
                    container.append(template);
                }
            })
        }

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
