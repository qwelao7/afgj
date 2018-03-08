require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#square-info-detail', function (e, id, page) {
        var url = common.getRequest();
        
        var tpl = $('#tpl').html(),
            content = $('#content');
        
        function load() {
            common.ajax('GET', '/official/article-detail', {
                'id': url.id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data,
                        html = juicer(tpl, data);
                    
                    content.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无相关数据!</h3>";
                    content.append(template);
                }
            });
        }

        load();

        var pings = env.pings;pings();
    });

    $.init();
});