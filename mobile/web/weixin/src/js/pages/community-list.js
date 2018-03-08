require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#community-list', function (e, id, page) {
        var url = common.getRequest();

        //参数
        var container = $('#container'),
            tpl = $('#tpl').html();

        var pages,
            nums;

        function loadData() {
            common.ajax('GET', '/site/communtity', {'cityId': url.id, 'keywords': '', 'page': 1}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    pages = data.pagination.pageCount;

                    var html = juicer(tpl, data);
                    container.append(html);
                    $('#search').val('');

                    if(pages == 1) {
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        $('.infinite-scroll-preloader').remove();
                    }
                } else {
                    $('.infinite-scroll-preloader').remove();
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无小区信息!</h3>";
                    container.append(template);
                }
            });
        }

        loadData();

        var page = 2,
            loading = false;

        $('.infinite-scroll').on('infinite', function () {
            var str = $('#search').val().trim();
            if(str != '') return;

            if (loading) return;

            if (page > pages) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                $('.infinite-scroll-preloader').remove();
                return;
            }

            loading = true;

            common.ajax('GET', '/site/communtity', {
                'cityId': url.id,
                'keywords': str,
                'page': page
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    loading = false;

                    var html = juicer(tpl, data);
                    container.append(html);

                    page++;
                }
            });

            $.refreshScroller();
        });

        /**
         * 搜索列表
         */
        function load(param) {
            common.ajax('GET', '/site/communtity', {
                'cityId': url.id,
                'keywords': param,
                'page': 1
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    nums = data.pagination.pageCount;

                    var html = juicer(tpl, data);
                    container.empty().append(html);

                    if (nums == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        $('.infinite-scroll-preloader').hide();
                        return;
                    }
                } else {
                    $('.infinite-scroll-preloader').remove();
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无小区信息!</h3>";
                    container.empty().append(template);
                }
            })
        }

        /**
         * 搜索小区
         */
        $(document).on('click', '#submit', function () {
            var val = $('#search').val().trim();

            if (val == '') {
                $.alert('请填写要搜索的小区名称');
                return;
            }

            load(val);

            var loaded = false,
                num = 2;

            $('.infinite-scroll').on('infinite', function () {
                if($('#search').val().trim() == '') return;

                if (loaded) return;

                if (num > nums) {
                    // 加载完毕，则注销无限加载事件，以防不必要的加载
                    $.detachInfiniteScroll($('.infinite-scroll'));
                    $('.infinite-scroll-preloader').hide();
                    return;
                }

                loaded = true;

                common.ajax('GET', '/site/communtity', {
                    'cityId': url.id,
                    'keywords': val,
                    'page': num
                }, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        var data = rsp.data.info;

                        loaded = false;

                        var html = juicer(tpl, data);
                        container.append(html);

                        num++;
                    }
                });

                $.refreshScroller();
            });
        });

        /**
         * 监听input
         */
        $('#search').on('change', function () {
            var str = $(this).val().trim();

            var isAndroid = common.isAndroid();
            
            str = common.filterString(str, isAndroid);

            if (str == "") {
                container.empty();

                $.attachInfiniteScroll($('.infinite-scroll'));
                $('.infinite-scroll-preloader').show();
                page = 2;

                loadData();
            }
        });

        /**
         * 选择小区
         */
        $(document).on('click', '.community', function () {
            var self = $(this),
                id = self.data('id'),
                name = self.find('h2').text();

            var data = {};
            data.id = id;
            data.name = name;

            window.localStorage.setItem('community', JSON.stringify(data));

            window.location.href = 'estate-add.html?type=0';
        });

        /**
         * 新建小区
         */
        $(document).on('click', '#create', function () {
            window.location.href = 'estate-add.html?type=1&id='+url.id;
        })

        var pings = env.pings;pings();
    });

    $.init();
})