require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#car-brand', function (e, id, page) {
        //参数
        var pageSize = 30,
            pages,
            lastLetter;

        var tpl = $('#tpl').html(),
            special = $('#special').html(),
            cars = $('#cars').html(),
            container = $('#container'),
            side = $('#side');

        var url = common.getRequest(),
            path = '?type=' + url.type + '&refer=' + url.refer;
        path = (url.refer_id) ? path + '&refer_id=' + url.refer_id : path;

        function loadBrand() {
            common.ajax('GET', '/ride-sharing/car-brand', {'per-page': pageSize, 'page': 1}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    pages = data.pagination.pageCount;

                    if (data.list.length > 0) {
                        lastLetter = data.list[data.list.length - 1].bfirstletter;
                        data.list = common.groupBy('bfirstletter', data.list);
                    }
                    var html = juicer(tpl, data);
                    container.append(html);
                }
            })
        }

        loadBrand();

        var loading = false,
            page = 2,
            num = 1;
        $(document).on('infinite', '.infinite-scroll', function () {
            // 如果正在加载，则退出
            if (loading) return;

            if (page > pages) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                $('.infinite-scroll-preloader').remove();
                return;
            }

            loading = true;

            common.ajax('GET', '/ride-sharing/car-brand', {
                'per-page': pageSize,
                'page': page,
                'keywords': ''
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    if (data.list.length > 0) {
                        var last = data.list[data.list.length - 1].bfirstletter;
                        data.list = common.groupBy('bfirstletter', data.list);
                    }
                    loading = false;

                    //是否存在索引为上一页最后首字母
                    if (data.list[lastLetter] != []) {
                        num++;
                        var items = [];
                        items['list'] = data.list[lastLetter];
                        items['num'] = num;
                        var html = juicer(special, items);
                        container.append(html);

                        $('.special' + num).first().addClass('has-border');
                        delete data.list[lastLetter];
                    }

                    page++;
                    if (data.list[last] != undefined) {
                        var text = juicer(tpl, data);
                        container.append(text);
                        //更新lastLetter
                        lastLetter = last;
                    }
                }
            });
            $.refreshScroller();
        });

        /**
         * 车型列表
         */
        function loadCars(id) {
            common.ajax('GET', '/ride-sharing/car-series', {'id': id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    var html = juicer(cars, data);
                    side.empty().append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无相关车型!</h3>";
                    side.empty().append(template);
                }
            });
        }
        
        /**
         * 搜索
         */
        $(document).on('click', '#submit', function () {
            var str = $('#search').val();

            if (str == '') {
                $.alert('请填写要查询的内容');
                return;
            }

            $.detachInfiniteScroll($('.infinite-scroll'));
            $('.infinite-scroll-preloader').hide();

            common.ajax('GET', '/ride-sharing/car-brand', {
                'per-page': pageSize,
                'page': page,
                'keywords': str
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    if (data.list.length > 0) {
                        data.list = common.groupBy('bfirstletter', data.list);
                    }
                    var html = juicer(tpl, data);
                    container.empty().append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无相关品牌,请重新输入!</h3>";
                    container.empty().append(template);
                }
            });

        });

        /**
         * 监控input输入
         *
         **/
        $('#search').on('input propertychange', function () {
            var str = $(this).val();
            if (str == "") {
                container.empty();
                $.attachInfiniteScroll($('.infinite-scroll'));
                $('.infinite-scroll-preloader').show();
                page = 2;
                loadBrand();
            }
        });

        /**
         * 展开车型列表
         */
        $(document).on('click', '.car-brand', function () {
            var self = $(this),
                id = self.data('id');

            $.openPanel('#panel-right');
            loadCars(id);
        });

        /**
         * 选择车型
         */
        $(document).on('click', '.ride-item', function () {
            var self = $(this),
                val = self.children('span').text(),
                id = self.data('id'),
                brand = self.data('brand');

            self.siblings().removeClass('ride-item-active');
            self.addClass('ride-item-active');

            var data = {};
            data.name = val;
            data.brand_id = brand;
            data.series_id = id;
            localStorage.setItem('series', JSON.stringify(data));

            $.closePanel('#panel-right');

            window.location.href = 'freeride-setcar.html' + path;
        });

        var pings = env.pings;pings();
    });

    $.init();
})