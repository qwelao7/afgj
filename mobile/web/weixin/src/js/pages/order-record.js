require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#order-record', function (e, id, page) {
        var url = common.getRequest();
        var nums = 1,
            lastPreiod,
            num = 2,
            loading = false,
            pageSize = 12,
            params = {},
            severMonth;

        //参数
        var group = $('#group').html(),
            list = $('#list').html(),
            container = $('#container');

        var time = function (data) {
            data = data.replace(/\-/g, '/');
            var date = new Date(data),
                Y = date.getFullYear() + '-',
                M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-',
                D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate()) + ' ';
            return Y + M + D;
        };
        var trans = function (data) {
            data = data.slice(0,6);
            if (data == severMonth) {
                return '本月';
            } else {
                var year = data.slice(0,4),
                    month = data.slice(5,6),
                    sever = severMonth.slice(0,4);

                if (year == sever) {
                    return month.replace(/^0+/,"") + '月';
                } else {
                    return year + '年' + month.replace(/^0+/,"") + '月';
                }
            }
        };
        juicer.register('time', time);
        juicer.register('trans', trans);

        function render(data) {
            var arr = [];

            for(var i in data) {
                arr.push(data[i]);
            }

            arr.forEach(function(item, index) {
                $('.points-record-include span').eq(index).text(item);
            })
        }


        function loadData() {
            common.ajax('GET', '/order/payment-list', {'page': 1, 'per-page': pageSize}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    severMonth = data.now_time;

                    if (data.list.length > 0) {
                        lastPreiod = data.list[data.list.length - 1]['month'];
                        data.list = common.group('month', data.list);
                        nums = data.pagination.pageCount;

                        var html = juicer(group, data);
                        container.append(html);
                        
                        render(rsp.data.info.total);
                    } else {
                        var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据信息!</h3>";
                        container.append(template);
                    }

                    if (nums <= 1) {
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

            common.ajax('GET','/order/payment-list', { 'page': num, 'per-page': pageSize }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        beforePeriod = data.list[0].month,
                        nextPeriod = data.list[data.list.length - 1]['month'];
                    data.list = common.groupBy('month', data.list);

                    if (beforePeriod == lastPreiod) {
                        params['list'] = data.list[lastPreiod];
                        var htm = juicer(list, params);
                        container.append(htm);
                        delete data.list[lastPreiod];
                    }
                    if (Object.keys(data.list).length > 0) {
                        data['type'] = url.type;
                        var html = juicer(group, data);
                        container.append(html);
                    }

                    lastPreiod = nextPeriod;
                    loading = false;
                    num++;

                    render(rsp.data.info.total);
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