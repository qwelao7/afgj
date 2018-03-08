require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#square-tab-list', function (e, id, page) {
        var container = $('#container'),
            tpl = $('#tpl').html();

        var defaultThumb = 'http://pub.huilaila.net/square-tab-default.jpg';

        $('.square-tl').live('click', function () {
            var self = $(this),
                community_id = self.data('id');

            location.href = 'square-tab-index.html?id=' + community_id;
        });
        
        $('#back').on('click', function() {
            location.href = common.ectouchPic;
        });

        common.img();

        function loadData() {
            common.ajax('GET', '/official/list', {}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data;
                    data['defaultThumb'] = defaultThumb;
                    
                    var  html = juicer(tpl, data);
                    container.append(html);
                }else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无相关数据!</h3>";
                    container.append(template);
                }
            })
        }

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});