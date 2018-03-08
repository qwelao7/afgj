require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#neighbor-search", function (e, id, page) {
        var search = $('#search'),
            hint = $('#hint'),
            result = $('#result'),
            tpl = $('#tpl').html(),
            scroll = $('#scroll').html();

        /** url参数 **/
        var url = common.getRequest();

        /** 无限滚动参数 **/
        var pages,
            lastBuildingNum,
            pageSize = 10;

        /** 监控input输入 **/
        $('#search').on('input propertychange', function () {
            var str = $(this).val();
            if (str == "") {
                result.empty();
                hint.show();
            } else {
                hint.hide();
            }
        });

        /** 模板自定义函数 **/
        common.img();
        var split = function (data) {
            return data.split(' ')[1];
        };
        juicer.register('split', split);

        /** 查询结果 **/
        function loadData(data) {
            common.ajax('GET', '/community/contacts',
                {'per-page': pageSize, 'loupanId': url.id, 'keywords': data, 'page':1}, false, function (rsp) {
                    if (rsp.data.code == 0) {
                        var data = rsp.data.info;
                        pages = data.pagination.pageCount;

                        if (data.list.length > 0) {
                            //上一页最后一个数据的楼栋号
                            lastBuildingNum = data.list[data.list.length - 1].building_num;

                            data.list = common.groupBy('building_num', data.list);
                        }else {
                            $('.infinite-scroll-preloader').hide();
                            var tips = "<div style='text-align: center'><h3>很抱歉,本次搜索无结果!</h3></div>";
                            result.empty();
                            result.append(tips);
                            return;
                        }

                        var html = juicer(tpl, data);
                        //清空之前搜索(避免显示无结果)
                        result.empty();
                        result.append(html);

                        if (pages == 1) {
                            // 加载完毕，则注销无限加载事件，以防不必要的加载
                            $.detachInfiniteScroll($('.infinite-scroll'));
                            $('.infinite-scroll-preloader').remove();
                            return;
                        }

                        var loading = false,
                            page = 2;
                        /** 无限滚动 **/
                        $('.infinite-scroll').on('infinite', function () {
                            // 如果正在加载，则退出
                            if (loading) return;

                            if (page > pages) {
                                // 加载完毕，则注销无限加载事件，以防不必要的加载
                                $.detachInfiniteScroll($('.infinite-scroll'));
                                $('.infinite-scroll-preloader').remove();
                                return;
                            }
                            loading = true;
                            var str = $('#search').val();

                            common.ajax('GET', '/community/contacts',
                                {
                                    'per-page': pageSize,
                                    'loupanId': url.id,
                                    'keywords': str,
                                    'page': page
                                }, false, function (rsp) {
                                    if (rsp.data.code == 0) {
                                        var data = rsp.data.info;

                                        var last = data.list[data.list.length - 1].building_num;
                                        data.list = common.groupBy('building_num', data.list);

                                        loading = false;
                                        //是否存在索引为上一页最后楼栋号
                                        if (data.list[lastBuildingNum] != []) {
                                            //获取同一楼栋的数组
                                            var special = [];
                                            special['items'] = data.list[lastBuildingNum];
                                            var scrollHtml = juicer(scroll, special);
                                            result.append(scrollHtml);
                                            delete data.list[lastBuildingNum];
                                        }

                                        page++;
                                        if (data.list[last] != undefined) {
                                            var html = juicer(tpl, data);
                                            result.append(html);
                                            //更新lastBuildNum
                                            lastBuildingNum = last;
                                        }
                                    }
                                });

                            $.refreshScroller();
                        });
                    } else {
                        $('.infinite-scroll-preloader').hide();
                        var tips = "<div style='text-align: center'><h3>很抱歉,本次搜索无结果!</h3></div>";
                        result.empty();
                        result.append(tips);
                    }
                }
            )
        }

        /** 自执行 **/
        var init = $('#search').val();
        if (init != '') {
            hint.hide();
            $(document).ready(function () {
                loadData(init);
            })
        }

        /** 搜索功能 **/
        $('#btn').on('click', function () {
            var data = $('#search').val();
            if (data == '') {
                $.alert('请输入搜索内容');
                return;
            }
            loadData(data);
            $('.infinite-scroll-preloader').removeClass('unshow');
        });

        /** 跳转用户详情页 **/
        $('.neighbour-detail').live('click', function () {
            var userId = $(this).data('id');
            window.location.href = 'neighbor-detail.html?id=' + userId;
        });

        var pings = env.pings;pings();
    });

    $.init();
});
