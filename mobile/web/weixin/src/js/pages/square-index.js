require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#square-index', function (e, id, page) {
        var models = $('#models').html(),
            container = $('#container');

        var config = {
            loop: true,
            autoplayDisableOnInteraction: false,
            pagination: '.swiper-pagination',
            paginationClickable: true
        };

        var img = function(str) {
            return common.ectouchPic + str;
        };
        var imgTrans = function (data) {
            var index = data.indexOf('http');

            if (index == -1) {
               return common.ectouchPic + '/data/attached/afficheimg/' + data;
            } else {
                return data;
            }
        };
        juicer.register('img', img);
        juicer.register('imgTrans', imgTrans);

        function toArray(s) {
            var arr = [];
            for(var i in s){
                arr.push(s[i]);
            }
            return arr;
        }

        function initSwiper() {
            $(".swiper-container").swiper(config);

            $('.square-pagination').css('bottom', '.5rem');
            $('.swiper-container').css('paddingBottom', 0);
        }

        function loadData() {
            common.ajax('GET', '/user/touch-nav', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var res = rsp.data.info,
                        info = {};

                    info.data = toArray(res.data);
                    info.num = toArray(res.num);
                    info.pics = res.pic;

                    var html = juicer(models, info);
                    container.append(html);

                    initSwiper();

                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据!</h3>";
                    container.append(template);
                }
            })
        }

        $('.square-item').live('click', function() {
            var self = $(this),
                toUrl = self.data('href');

            toUrl = common.escapeHtml(toUrl);

            location.href = toUrl;
        });

        $('.square-swiper-slide').live('click', function() {
            var self = $(this),
                toUrl = self.data('href');

            toUrl = common.escapeHtml(toUrl);

            location.href = toUrl;
        });

        common.renderNavs(1);
        loadData();
        var pings = env.pings;
        pings();
    });

    $.init();
});