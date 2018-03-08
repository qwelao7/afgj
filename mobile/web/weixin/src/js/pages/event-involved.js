require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event-involved", function (e, id, page) {
        var url = common.getRequest();

        var list = $('#list').html(),
            container = $('#container');

        var nums,
            num = 2,
            isLoading = false,
            pageSize = 20,
            status = true;

        var font = function (data) {
            return data == 0 ? '' : 'font-grey';
        };
        juicer.register('font', font);
        common.img();

        function loadData() {
            common.ajax('GET', '/events/signup', {'per-page': pageSize, 'page': 1}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    nums = data.pagination.pageCount;

                    if (data.list.length < 1) {
                        showTips();
                        return;
                    }

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
            var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,您暂未参与活动!</h3>";
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

            common.ajax('GET', '/events/signup', {'per-page': pageSize, 'page': num}, true, function (rsp) {
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

        /**
         * 咨询
         */
        $(document).on('click', '.call-sponsor', function (e) {
            e.preventDefault();

            var _this = $(this),
                tel = _this.data('tel'),
                parents = _this.parents('.event-cover'),
                sponsor = parents.data('creater'),
                issponsor = parents.data('issponsor');

            if (issponsor) {
                $.alert('很抱歉,您是发起者,无法向自己咨询!', '咨询失败');
                return false;
            }
            location.href = 'tel://' + tel;
        });

        /**
         * 取消报名
         */
        $(document).on('click', '.cancel-involved', function (e) {
            e.preventDefault();

            var _this = $(this),
                parents = _this.parents('.event-cover'),
                e_id = parents.data('id');

            if (!status) return false;
            status = false;

            common.ajax('GET', '/events/apply-cancel', {'events_id': e_id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('取消报名成功!', '取消成功!', function () {
                        parents.remove();
                        if ($('.event-cover').length < 1) {
                            var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,您暂未参与活动!</h3>";
                            container.append(template);
                        }
                    });
                    status = true;
                } else {
                    $.alert('很抱歉,' + rsp.data.message, +',请重试!', '取消报名失败');
                    status = true;
                }
            })

        });

        $(document).on('click', '.event-content', function (e) {
            var _this = $(this),
                parents = _this.parents('.event-cover'),
                e_id = parents.data('id');

            location.href = 'event-detail.html?id=' + e_id + '&dir=involved' + '&type=1';
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function () {
            var path = (url.refer) ? '?id=' + url.refer : '';
            window.location.href = 'event-list.html' + path;
        });

        loadData();
        var pings = env.pings;
        pings();
    });

    $.init();
});