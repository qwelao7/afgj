require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-statistics", function (e, id, page) {
        var url = common.getRequest(),
            header = $('#header').html(),
            title = $('#title');
        var data = {},
            info = {},
            info1 = {};

        var token = '';
        token = url.token ? url.token : common.getCookie('openid');
        var project = JSON.parse(window.localStorage.getItem('data_project'));
        info.name = [];
        info.id = [];
        info.show = [];
        $.each(project, function (index, item) {
            info.name.push(item);
            info.id.push(index);
            info.show.push(item + '▾')
            if (index == url.id) {
                info1.name = item;
                info1.id = index;
            }
        })
        var html = juicer(header, info1);
        title.append(html);

        // 跨域ajax请求

        function loadCommunity() {
            $.ajax({
                url: env.ajax_data + "/pes/stat/property?token=" + token + "&projectCode=" + url.id,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        console.log(rsp.data);
                        data.y_axis = [];
                        data.own = [];
                        data.tag = [];
                        data.y_axis = rsp.data.y;
                        $.each(rsp.data.x, function (index, item) {
                            data.own.push(item.own);
                            data.tag.push(item.tag);
                        })
                        console.log(data);
                        picker();
                        init(data);
                    } else {
                        $.alert('很抱歉！' + rsp.msg);
                    }

                },
                error: function () {
                    window.location.href = 'data-input-auth.html';
                },
            });
        };

        function init(data) {
            // 基于准备好的dom，初始化echarts实例
            var myChart = echarts.init(document.getElementById('main'));
            // 指定图表的配置项和数据
            var option = {
                title: {
                    text: '客户信息录入统计'
                },
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow'
                    }
                },
                legend: {
                    data: ['用户数', '标签数'],
                    right: 1
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                xAxis: {
                    type: 'value',
                    boundaryGap: [0, 0.01]
                },
                yAxis: {
                    type: 'category',
                    data: data.y_axis
                },
                series: [
                    {
                        name: '用户数',
                        type: 'bar',
                        data: data.own
                    },
                    {
                        name: '标签数',
                        type: 'bar',
                        data: data.tag
                    }
                ],
                dataZoom: [

                    {
                        type: 'slider',
                        yAxisIndex: 0,
                        filterMode: 'empty'
                    },

                    {
                        type: 'inside',
                        yAxisIndex: 0,
                        filterMode: 'empty'
                    }
                ]
            };
            // 使用刚指定的配置项和数据显示图表。
            myChart.setOption(option);
        }


        /**
         * 选择楼盘
         */
        function picker() {
            $("#picker").picker({
                toolbarTemplate: '<header class="bar bar-nav">' +
                '<button class="button button-link pull-right close-picker font-white">确定</button>' +
                '<h1 class="title font-white">请选择小区</h1></header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: info.show,
                        displayValues: info.name
                    }
                ],
                onOpen: function () {
                    var template = "<div class='modal-overlay modal-overlay-visible'></div>";
                    $('.page').append(template);
                },
                onClose: function () {
                    $(".modal-overlay").removeClass('modal-overlay-visible');
                    var str = $('#picker').val();
                    str = $.trim(str);
                    var id = info.id[info.show.indexOf(str)];
                    window.location.href = 'data-statistics.html?id=' + id;
                }
            });
        }


        /**
         * 点击遮罩层
         */
        $(document).on('click', '.modal-overlay', function () {
            $(this).removeClass('modal-overlay-visible');
            $(".picker").picker("close");
        });


        /**
         * 点击跳转排名页面
         **/
        $(document).on('click', '#rank', function () {
            window.location.href = 'data-ranking.html?id=' + url.id;
        });

        //返回
        $(page).on('click', '#back', function () {
            history.go(-1);
        });

        loadCommunity();
        var pings = env.pings;
        pings();
    });

    $.init();
});
