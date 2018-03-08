require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#points-record', function (e, id, page) {
        //type 1-所有 2-获取 3-使用
        var url = common.getRequest(),
            getUrl = '';
        switch (url.type) {
            case '1': getUrl = 'all';break;
            case '2': getUrl = 'income';break;
            case '3': getUrl = 'expend';break;
            default: getUrl = 'all';
        }

        var nums,
            lastPreiod,
            num = 2,
            loading = false,
            pageSize = 10,
            params = {};

        //参数
        var group = $('#group').html(),
            list = $('#list').html(),
            title = $('#title').html(),
            header = $('#header'),
            container = $('#container');

        var time = function (data) {
            data = data.replace(/\-/g, '/');
            var date = new Date(data),
                Y = date.getFullYear() + '-',
                M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-',
                D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate()) + ' ';
            return Y + M + D;
        };
        juicer.register('time', time);
        
        
        function loadData() {
            common.ajax('GET', '/points/' + getUrl, {'page': 1, 'per-page': pageSize }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    if (data.list.length > 0) {
                        lastPreiod = data.period_stat[data.period_stat.length - 1]['period'];
                        data.list = common.groupBy('period', data.list);
                        nums = data.pagination.pageCount;

                        //模板渲染
                        data['type'] = url.type;
                        var html = juicer(group, data);
                        container.append(html);
                    } else {
                        var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据信息!</h3>";
                        container.append(template);
                    }
                    var htm = juicer(title, {'type': url.type});
                    header.append(htm);

                    if (nums == 1 || data.list.length < 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        $('.infinite-scroll-preloader').remove();
                    }
                } else {
                    $.detachInfiniteScroll($('.infinite-scroll'));
                    $('.infinite-scroll-preloader').remove();

                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据信息!</h3>";
                    container.append(template);
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

            common.ajax('GET', '/points/' + getUrl, { 'type': url.type, 'page': num, 'per-page': pageSize }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        beforePeriod = data.period_stat[0]['period'],
                        nextPeriod = data.period_stat[data.period_stat.length - 1]['period'];
                    data.list = common.groupBy('period', data.list);

                    if (beforePeriod == lastPreiod) {
                        params['list'] = data.list[lastPreiod];
                        var htm = juicer(list, params);
                        container.append(htm);
                        delete data.list[lastPreiod];
                        data.period_stat.shift();
                    }
                    if (Object.keys(data.list).length > 0) {
                        data['type'] = url.type;
                        
                        var html = juicer(group, data);
                        container.append(html);
                    }

                    lastPreiod = nextPeriod;
                    loading = false;
                    num++;
                }
            });

            $.refreshScroller();
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});