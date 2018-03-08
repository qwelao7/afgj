require('../../css/style.css');
require('../../css/index.css');
require('../../css/fonts/iconfont.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#drift-lamp-index", function (e, id, page) {
        var container   = $('#container'),
            content     = $('#content'),
            bottom      = $('#bottom').html(),
            show        = $('#show').html(),
            list        = $('#list').html();

        var loading     = false,
            num         = 2,
            nums;

        var math = function (num, total) {
            if (parseInt(total) == 0) return 0;
            return parseInt(parseInt(num) / parseInt(total) * 100);
        };
        common.img();
        juicer.register('dateFormat', common.dateFormat);
        juicer.register('math', math);

        //无限滚动
        $('.infinite-scroll').on('infinite', function () {
            // 如果正在加载，则退出
            if (loading) return;
            if (num > nums) {
                removeInifite();
                return;
            }
            loading = true;

            common.ajax('GET', '/light/index', {'page': num}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        htm = juicer(list, data);

                    content.append(htm);

                    loading = false;
                    num++;
                }
            });

            $.refreshScroller();
        });

        /**
         * echarts 初始化
         * perfect 优秀
         * fine    一般
         * bad     差
         *  status  -> 显示多个或单个
         */
        function echartInit(perfect, fine, bad, status) {
            var myChart = echarts.init(document.getElementById('drift-charts'));
            // 指定图表的配置项和数据
            var option = {
                tooltip: {
                    trigger: 'item',
                    formatter: "{a} <br/>{b}: {c} ({d}%)"
                },
                series: [
                    {
                        name: '合格率',
                        type: 'pie',
                        selectedMode: 'single',
                        radius: [0, '70%'],
                        label: {
                            normal: {
                                textStyle: {
                                    color: '#ddd'
                                }
                            }
                        },
                        labelLine: {
                            normal: {
                                lineStyle: {
                                    color: 'rgba(255, 255, 255, 0.3)'
                                }
                            }
                        },
                        data: []
                    }
                ]
            };

            var badItem = {
                value: bad,
                name: '不佳',
                selected: true,
                itemStyle: {
                    normal: {color: '#ff6c00'}
                }
            },
                fineItem = {
                    value: fine,
                    name: '良好',
                    itemStyle: {
                        normal: {color: '#C5CCD4'}
                    }
                },
                perItem = {
                    value: perfect,
                    name: '优秀',
                    itemStyle: {
                        normal: {color: '#fff'}
                    }
                };

            if (perfect == 0 && fine == 0 && bad == 0) {
                option.series[0].data.push(perItem);
            } else {
                if (perfect != 0) {
                    option.series[0].data.push(perItem);
                }

                if (fine != 0) {
                    option.series[0].data.push(fineItem);
                }

                if (bad != 0) {
                    option.series[0].data.push(badItem);
                }
            }

            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);
        }

        /**
         * 加载数据
         */
        function loadData() {
            common.ajax('GET', '/light/index', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(list, data),
                        htm = juicer(show, data);

                    content.append(htm).append(html);
                    echartInit(data.statistics.perfect, data.statistics.fine, data.statistics.bad);

                    nums = data.pagination.pageCount;
                    if (nums == 1) removeInifite();
                } else {
                    var teml = juicer(show, {}),
                        template = "<h3 style='margin-top: 4rem;text-align: center;'>很抱歉,暂无数据</h3>";

                    content.append(teml).append(template);
                    echartInit(0,0,0);

                    removeInifite();
                }

                var tem = juicer(bottom, {});
                container.after(tem);
            })
        }

        /**
         * 移除滚动事件
         */
        function removeInifite () {
            $.detachInfiniteScroll($('.infinite-scroll'));
            $('.infinite-scroll-preloader').remove();
        }

        //返回
        $('#back').live('click', function() {
            location.href = 'event-detail.html?id=49&type=1';
        });


        //跳转反馈
        $('#submit').live('click', function () {
            location.href = 'drift-lamp-feedback.html';
        });


        /**
         * 跳转详情页
         */
        $(document).on('click', '.lamp-item', function (e) {
            var self = $(this),
                id = self.data('id');

            location.href = 'drift-lamp-result.html?id=' + id;
        });

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
