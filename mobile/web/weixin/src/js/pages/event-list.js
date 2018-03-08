require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event-list", function (e, id, page) {
        var tpl = $('#tpl').html(),
            list = $('#list').html(),
            title = $('#title'),
            container = $('#container');

        var url = common.getRequest();

        var communities = [],
            ids = [],
            values = [],
            num = 2,
            status = true,
            loading = false,
            nums,
            community;

        common.img();

        var format = function (data) {
            data = data.replace(/\-/g, '/');
            var day = new Date(data),
                dayMonth = day.getMonth() + 1,
                dayDate = day.getDate(),
                dayHour = day.getHours(),
                dayMinute = day.getMinutes();

            dayHour = (dayHour < 10) ? '0' + dayHour : dayHour;
            dayMinute = (dayMinute < 10) ? '0' + dayMinute : dayMinute;
            dayMonth = (dayMonth < 10) ? '0' + dayMonth : dayMonth;
            dayDate = (dayDate < 10) ? '0' + dayDate : dayDate;

            return dayMonth + '-' + dayDate + ' ' + dayHour + ':' + dayMinute;
         };
        juicer.register('format', format);
        var font = function (data) {
            return data == 0 ? '' : 'font-grey';
        };
        juicer.register('font', font);


        // 点击tag
        $(document).on('click', '#tag', function () {
            $('#popup').css('display', 'block');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
        });
        $(document).on('click', '.modal-overlay', function () {
            $('#popup').css('display', 'none');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
        });


        /**
         * 跳转我发起的
         */
        $(document).on('click', '#started', function() {
            var path = (url.id) ? '?refer=' + url.id : '';
            window.location.href = 'event-started.html' + path;
        });

        /**
         * 跳转我参与的
         */
        $(document).on('click', '#involved', function() {
            var path = (url.id) ? '?refer=' + url.id : '';
            window.location.href = 'event-involved.html' + path;
        });

        /** 选择小区 **/
        function picker() {
            $("#picker").picker({
                toolbarTemplate: '<header class="bar bar-nav">' +
                '<button class="button button-link pull-right close-picker font-white">确定</button>' +
                '<h1 class="title font-white">请选择小区</h1></header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: values,
                        displayValues: communities
                    }
                ],
                onClose: function() {
                    var str = $('#picker').val();
                    str = $.trim(str);

                    var index = values.indexOf(str);

                    window.location.href = 'event-list.html?id=' + ids[index];
                }
            });
        }

        /** 加载小区列表 **/
        function loadCommunitys() {
            common.ajax('GET', '/ride-sharing/account-community', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        info = {};
                    communities = data.name;
                    ids = data.id;

                    if (url.id) {
                        var index = ids.indexOf(url.id);
                        info.name = communities[index];
                        community = ids[index];
                    } else {
                        info.name = communities[0];
                        community = ids[0];
                    }

                    /** picker选项内容 **/
                    $.each(communities, function (index, item) {
                        values.push(item + '▾');
                    });

                    var html = juicer(tpl, info);
                    title.append(html);

                    /** 后续操作 **/
                    loadData();
                    picker();
                } else {
                    var htm = juicer(tpl, {});
                    title.append(htm);

                    loadData();
                }
            });
        }

        /** 加载数据 **/
        function loadData() {
            common.ajax('GET', '/events/list', {'community_id': community, 'page': 1}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(list, data);

                    nums = data.pagination.pageCount;

                    container.append(html);

                    if (nums == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                    }
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无活动!</h3>";
                    container.append(template);
                    $('.infinite-scroll-preloader').remove();
                }
            })
        }

        /** 无限滚动 **/
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

            common.ajax('GET', '/events/list', {'community_id': community, 'page': num}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    loading = false;
                    var data = rsp.data.info,
                        html = juicer(list, data);
                    container.append(html);

                    num++;
                }
            });

            $.refreshScroller();
        });

        /** 跳转详情 **/
        $(document).on('click', '.event-cover', function () {
            var self = $(this),
                id = self.data('id');

            window.location.href = 'event-detail.html?id=' + id + '&refer=' + community + '&type=1';
        });

        loadCommunitys();

        common.renderNavs(2);
        var pings = env.pings;pings();
    });

    $.init();
});