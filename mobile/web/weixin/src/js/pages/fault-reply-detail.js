require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#fault-reply-detail", function (e, id, page) {
        var url = common.getRequest();

        var container = $('#container'),
            tpl = $('#tpl').html(),
            pop = $('#pop').html();

        var config = {
            loop: true,
            autoHeight: true,
            visiblilityFullfit: true,
            autoplayDisableOnInteraction: false,
            pagination: '.swiper-pagination',
            paginationClickable: true
        };

        var imgs = function (data) {
            var head = env.defaultHeadImg;
            return head;
        };
        var parse = function(data) {
            var result = data.replace(/\s/g, '<br/>');
            return result;
        };
        juicer.register('imgs', imgs);
        juicer.register('parse', parse);

        common.img();

        function loadData() {
            common.ajax('GET', '/feedback/maintain-log-list', {
                'id': url.id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    if (!data.list || data.list.length == 0) {
                        var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无服务反馈信息!</h3>";
                        container.append(template);
                        return false;
                    }

                    var html = juicer(tpl, data);

                    container.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无服务反馈信息!</h3>";
                    container.append(template);
                }
            })
        }

        $(document).on('click', '.error-img-container', function () {
            //生成图集数据
            var self = $(this),
                parent = self.parent(),
                pics = parent.data('pics');

            var html = juicer(pop, {'pics': pics.split(',')});
            $('#popup').empty().append(html);

            $.popup('.popup-about');
            $('.swiper-container').swiper(config);
        });

        $(document).on('click', '#back', function() {
            var path = '?id=' + url.community_id + '&work_id=2';
            path = (url.ref != undefined) ? path + '&ref=' + url.ref : path;

            location.href = 'fault-reply-list.html' + path;
        });       
        
        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});