require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#vehicle-manage", function (e, id, page) {
        var tpl = $('#tpl').html(),
            container = $('#container');

        var loading = false,
            num = 2,
            nums,
            template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据信息!</h3>";

        function loadData() {
            common.ajax('GET', '/vehicle/index', {'page': 1}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    nums = data.pagination.pageCount;
                    container.append(html);

                    if (nums == 1) {
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        $('.infinite-scroll-preloader').remove();
                    }
                } else {
                    container.append(template);

                    $.detachInfiniteScroll($('.infinite-scroll'));
                    $('.infinite-scroll-preloader').remove();
                }
            })
        }

        /**
         * 无限滚动
         */
        $('.infinite-scroll').on('infinite', function () {
            // 如果正在加载，则退出
            if (loading) return;
            if (num > nums) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                $('.infinite-scroll-preloader').remove();
                return;
            }
            loading = true;

            common.ajax('GET', '/vehicle/index', {'page': num}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        htm = juicer(tpl, data);

                    container.append(htm);

                    loading = false;
                    num++;
                }
            });

            $.refreshScroller();
        });

        /**
         * 返回
         */
        $('#back').live('click', function () {
            location.href = common.ectouchUrl + '&c=user&a=index'
        });

        /**
         * 创建
         */
        $('#add').live('click', function () {
            location.href = 'freeride-setcar.html?type=1';
        });

        /**
         * 详情
         */
        $('.vehicle-list').live('click', function () {
            var self = $(this),
                id = self.data('id');

            location.href = 'vehicle-alert.html?id=' + id;
        });
        
        loadData();
        var pings = env.pings;
        pings();
    });

    $.init();
});
