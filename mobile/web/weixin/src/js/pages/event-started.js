require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event-started", function (e, id, page) {
        var url = common.getRequest();

        var list = $('#list').html(),
            container = $('#container');

        var isLoading = false,
            status = true,
            num = 2,
            pageSize = 20,
            nums;

        var font = function (data) {
            return data == 0 ? '' : 'font-grey';
        };
        juicer.register('font', font);
        common.img();

        function loadData() {
            common.ajax('GET', '/events/launch', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    if (data.list.length < 1) {
                        showTips();return false;
                    }

                    nums = data.pagination.pageCount;

                    var html = juicer(list, data);
                    container.append(html);

                    if (nums == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                    }
                } else {
                    showTips();
                }
            })
        }

        function showTips() {
            var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,您暂未发起活动!</h3>";
            container.append(template);
            // 加载完毕，则注销无限加载事件，以防不必要的加载
            $.detachInfiniteScroll($('.infinite-scroll'));
            // 删除加载提示符
            $('.infinite-scroll-preloader').remove();
        }

        /** 无限滚动 **/
        $(document).on('infinite', '.infinite-scroll', function () {
            if (isLoading) return;
            isLoading = true;

            if (num > nums) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                // 删除加载提示符
                $('.infinite-scroll-preloader').remove();
                return;
            }

            common.ajax('GET', '/events/launch', {'per-page': pageSize, 'page': num}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    isLoading = false;
                    var data = rsp.data.info,
                        html = juicer(list, data);
                    container.append(html);
                    num++;
                }
            });

            $.refreshScroller();
        });

        /** 详情 **/
        $(document).on('click', '.event-cover', function (e) {
            var _this = $(this),
                e_id = _this.data('id');

            location.href = 'event-detail.html?id=' + e_id + '&dir=started' + '&type=1';
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function () {
            var path = (url.refer) ? '?id=' + url.refer : '';
            window.location.href = 'event-list.html' + path;
        });

        $(document).on('click', '#add', function() {
            location.href = 'event-create.html';
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});