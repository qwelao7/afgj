require('../../css/style.css');
require('../../css/index.css');
require('../../css/fonts/iconfont.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#credit-index", function (e, id, page) {
        function  loadData () {
            common.ajax('GET', '/zhima/index', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data.zm_score = data.zm_score / 100;

                    init(data.zm_score);
                    $('#zhima-level').html(data.level);
                } else if (rsp.data.code == 101) {
                    location.href = 'credit-auth.html';
                } else {
                    $.alert('很抱歉,查询失败,请重试!', '查询失败', function () {
                        history.back();
                    })
                }
            })
        }

        function init(params) {
            // 基于准备好的dom，初始化echarts实例
            var myChart = echarts.init(document.getElementById('credit-charts'));
            // 指定图表的配置项和数据
            var option = {
                series: [{
                    type: 'liquidFill',
                    radius: '80%',
                    data: [params, 0.45, 0.4, 0.3],
                    label: {
                        normal: {
                            textStyle: {
                                color: '#fff',
                                insideColor: '#fff',
                                fontSize: 40
                            }
                        }
                    }
                }]
            };
            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);
        }

        $('#library').live('click', function () {
            location.href = 'library-index.html';
        });

        $('#back').live('click', function () {
            location.href = common.ectouchUrl + '&c=user&a=index';
        });

        $('#air').live('click', function () {
            location.href = 'event-detail.html?id=50';
        });

        $('#lamp').live('click', function () {
            location.href = 'event-detail.html?id=49';
        });

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
