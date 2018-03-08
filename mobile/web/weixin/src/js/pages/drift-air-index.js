require('../../css/style.css');
require('../../css/index.css');
require('../../css/fonts/iconfont.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#drift-air-index", function (e, id, page) {
        var container   = $('#container'),
            content     = $('#content'),
            show        = $('#show').html(),
            list        = $('#list').html(),
            bottom      = $('#bottom').html();

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

        //qualified -- 合格数
        //unqualified -- 不合格数
        function echartInit(qualified, unqualified) {
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
            //配置参数
            var unqual = {
                value: unqualified,
                name: '不合格',
                selected: true,
                itemStyle: {
                    normal: {color: '#ff6c00'}
                }
            };
            var qual =  {
                value: qualified,
                name: '合格',
                itemStyle: {
                    normal: {color: '#fff'}
                }
            };
            if (unqualified == 0 && qualified == 0) {
                option.series[0].data.push(qual);
            } else {
                if (unqualified != 0) {
                    option.series[0].data.push(unqual);
                }
                if (qualified != 0) {
                    option.series[0].data.push(qual);
                }
            }

            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);
        }

        //加载数据
        function loadData() {
            common.ajax('GET', '/hcho/index', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(list, data),
                        htm = juicer(show, data);

                    content.append(htm).append(html);

                    nums = data.pagination.pageCount;
                    if (nums == 1) removeInifite();

                    echartInit(data.statistics.perfect, data.statistics.not_perfect);
                } else {
                    var teml = juicer(show, {}),
                        template = "<h3 style='margin-top: 4rem;text-align: center;'>很抱歉,暂无数据</h3>";

                    content.append(teml).append(template);

                    removeInifite();
                    echartInit(100, 0);
                }

                var tem = juicer(bottom, {});
                container.after(tem);
            })
        }

        //无限滚动
        $('.infinite-scroll').on('infinite', function () {
            // 如果正在加载，则退出
            if (loading) return;
            if (num > nums) {
                removeInifite();
                return;
            }
            loading = true;

            common.ajax('GET', '/hcho/index', {'page': num}, true, function (rsp) {
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

        //返回
        $('#back').live('click', function() {
            location.href = 'event-detail.html?id=50&type=1';
        });

        //移除无限滚动事件
        function removeInifite () {
            $.detachInfiniteScroll($('.infinite-scroll'));
            $('.infinite-scroll-preloader').remove();
        }

        //跳转反馈
        $('#submit').live('click', function () {
            location.href = 'drift-air-feedback.html';
        });
        
        /**
         * 跳转详情页
         */
        $(document).on('click', '.air-item', function (e) {
            var self = $(this),
                id = self.data('id');

            location.href = 'drift-air-result.html?id=' + id;
        });

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
