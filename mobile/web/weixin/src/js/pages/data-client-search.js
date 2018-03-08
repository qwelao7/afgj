require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-client-search", function (e, id, page) {
        var url = common.getRequest(),
            tpl = $('#tpl').html(),
            content = $('#content'),
            data = {},
            page = 2,
            loading = false,
            pages,
            list = [],
            keywords = '';
        var token = '';
        token = url.token ? url.token : common.getCookie('openid');

        // 跨域ajax请求

        function loadClient() {
            $.ajax({
                url: env.ajax_data + "/pes/agent/customers?token=" + token + '&page=1',
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        console.log(rsp);
                        list = rsp.data.data;
                        data.list = list;
                        var html = juicer(tpl, data);
                        content.html('').append(html);
                        pages = Math.ceil(rsp.data.pages.totalCount / 20);
                        if (pages == 1) {
                            // 加载完毕，则注销无限加载事件，以防不必要的加载
                            $.detachInfiniteScroll($('.infinite-scroll'));
                            // 删除加载提示符
                            $('.infinite-scroll-preloader').remove();
                            return;
                        }
                    }
                },
                error: function () {
                    window.location.href = 'data-sales-login.html';
                },
            });
        };


        /**
         * 无限滚动
         */
        $(document).on('infinite', '.infinite-scroll', function () {
            keywords = $('#search').val();
            if (loading) return;

            loading = true;

            if (page > pages) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                // 删除加载提示符
                $('.infinite-scroll-preloader').remove();
                return;
            }
            $.ajax({
                url: env.ajax_data + "/pes/agent/customers?token=" + token + '&page=' + page + '&keywords=' + keywords,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        loading = false;
                        var list1 = [];
                        list1 = rsp.data.data;
                        $.each(list1, function (index, item) {
                            list.push(list1[index]);
                        })
                        var data1 = {};
                        data1.list = list1;
                        var html = juicer(tpl, data1);
                        content.append(html);
                        page++;
                    }
                },
                error: function () {
                    window.location.href = 'data-sales-login.html';
                },
            });
            $.refreshScroller();
        });


        //搜索历史
        $(document).on('click', '#to-search', function () {
            keywords = $('#search').val();
            list.length = 0;
            $.ajax({
                url: env.ajax_data + "/pes/agent/customers?token=" + token + "&keywords=" + keywords + '&page=1',
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        list = rsp.data.data;
                        data.list = list;
                        console.log(list);
                        var html = juicer(tpl, data);
                        content.html('').append(html);
                        $('#content').scrollTop();
                        page = 2;
                        pages = Math.ceil(rsp.data.pages.totalCount / 20);
                        if (pages == 1) {
                            // 加载完毕，则注销无限加载事件，以防不必要的加载
                            $.detachInfiniteScroll($('.infinite-scroll'));
                            // 删除加载提示符
                            $('.infinite-scroll-preloader').remove();
                            return;
                        }
                    }
                },
                error: function () {
                    alert('fail');
                }
            });
        });

        /**
         * 跳转编辑详情页
         */
        $(document).on('click', '.account-item-name', function () {
            var self = $(this),
                id = self.parent().parent().data('id');
            $.each(list, function (index, item) {
                if (list[index].customer_code == id) {
                    window.localStorage.setItem('data_owner_client', JSON.stringify(item));
                }
            });
            window.location.href = 'data-client-info.html?id=' + id;
        });

        /**
         * 关注客户
         */
        $(document).on('click', '.unfav_star', function () {
            var self = $(this),
                id = self.parent().parent().data('id');
            $.ajax({
                url: env.ajax_data + "/pes/agent/follow?token=" + token + "&customer_code=" + id,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        $.alert('关注成功', function () {
                            self.removeClass('unfav_star').addClass('fav_star');
                            self.children('.iconfont').removeClass('icon-hd-hll1').addClass('icon-hd-hll2').css('color', '#eda239')
                        });
                    } else {
                        alert('很抱歉！+rsp.msg');
                    }
                },
                error: function () {
                    alert('关注失败！');
                }
            });
        });

        /**
         * 取关客户
         */
        $(document).on('click', '.fav_star', function () {
            var self = $(this),
                id = self.parent().parent().data('id');
            $.ajax({
                url: env.ajax_data + "/pes/agent/unfollow?token=" + token + "&customer_code=" + id,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        $.alert('取消关注成功', function () {
                            self.removeClass('fav_star').addClass('unfav_star');
                            self.children('.iconfont').removeClass('icon-hd-hll2').addClass('icon-hd-hll1').css('color', '')
                        });
                    } else {
                        alert('很抱歉！+rsp.msg');
                    }
                },
                error: function () {
                    alert('关注失败！');
                }
            });
        });

        //返回
        $(document).on('click', '#back', function () {
            history.go(-1);
        });


        if (token) {
            loadClient();
        } else {
            common.ajax('GET', '/order/index', {}, true, function () {
                loadClient();
            });
        }
        var pings = env.pings;
        pings();
    });

    $.init();
});
