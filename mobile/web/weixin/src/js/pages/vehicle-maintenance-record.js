require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#vehicle-maintenance-record', function (e, id, page) {
        var url = common.getRequest();
        
        var tpl = $('#tpl').html(),
            nav = $('#nav').html(),
            container = $('#container'),
            content = $('#content');

        var loading = false,
            status = true,
            num = 2,
            nums;

        function loadData() {
            common.ajax('get', '/vehicle/notification-list', {
                id: url.car_id,
                page: 1
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data),
                        htm = juicer(nav , {});

                    nums = data.pagination.pageCount;

                    container.append(html);
                    content.after(htm);

                    if (nums == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                    }
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无记录!</h3>";
                    container.append(template);
                    $('.infinite-scroll-preloader').remove();
                }
            });
        }

        $(document).on('infinite', '.infinite-scroll', function () {
            if (loading) return;
            loading = true;

            if (num > nums) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                // 删除加载提示符
                $('.infinite-scroll-preloader').remove();
                return;
            }

            common.ajax('GET', '/vehicle/notification-list', {
                id: url.car_id,
                page: num
            }, true, function (rsp) {
                loading = false;
                var data = rsp.data.info,
                    html = juicer(tpl, data);
                container.append(html);

                num++;
            });

            $.refreshScroller();
        });

        $('#submit').live('click', function() {
            var path = '?car_id=' + url.car_id;
            location.href = 'vehicle-maintenance-add.html' + path;
        });
        
        $('#back').on('click', function() {
            location.href = 'vehicle-alert.html?id=' + url.car_id;
        });

        loadData();
        
        var pings = env.pings;
        pings();
    });

    $.init();
});