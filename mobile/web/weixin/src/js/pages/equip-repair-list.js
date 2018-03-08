require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#equip-repair-list", function (e, id, page) {
        var url = common.getRequest(),
            href = window.location.href;

        var tpl = $('#tpl').html(),
            extra = $('#extra').html(),
            container = $('#container'),
            path;

        var lat = 39.897445, //纬度
            long = 116.331398; //经度

        $(document).on('click', '.open-3-modal', function () {
            var self = $(this),
                parent = self.parents('.repair_item'),
                id = parent.data('id');

            common.ajax('GET', '/facilities/question-list', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data =rsp.data.info,
                        htm = juicer(extra, data);
                    
                    render(htm, id);
                }
            })

        });

        $('#back').on('click', function () {
            path = '?id=' + url.id + '&address=' + url.address;

           location.href = 'equip-detail.html' + path;
        });

        $('.repair_address').live('click', function () {
            var self = $(this),
                address = $.trim(self.data('address'));

            getLocation(address);
        });

        function loadData() {
            common.ajax('GET', '/facilities/equipment-fix', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据!</h3>";
                    container.append(template);
                }
            })
        }

        function render(html, id) {
            $.modal({
                title: '<h2 class="font-green" style="margin: 0">请您选择具体原因帮助我们改正: </h2>',
                text: html,
                buttons: [
                    {
                        text: '取消'
                    },
                    {
                        text: '确定',
                        bold: true,
                        onClick: function () {
                            var value = $.trim($('input[name=extra]:checked').val());
                            if (value != '') {
                                common.ajax('GET', '/facilities/equipment-apply', {'id': id, 'reason': value}, true, function (rsp) {
                                    if (rsp.data.code == 0) {
                                        $.closeModal('.open-3-modal');
                                        $.alert('您的反馈将帮助他人获得更好的服务。工作人员核实信息后，您将获得1友元奖励金。感谢您的反馈！');
                                    } else {
                                        $.closeModal('.open-3-modal');
                                        $.alert('很抱歉,报错失败,失败原因:' + rsp.data.message, '检验失败');
                                    }
                                })
                            } else {
                                $.closeModal('.open-3-modal');
                                $.alert('请选择错误原因!', '检验失败');
                            }
                        }
                    }
                ]
            })
        }

        /** 获取微信配置 **/
        function getConfig() {
            common.ajax('POST', '/wechat/config', {href: href}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data = JSON.parse(data);
                    wx.config({
                        debug: false,
                        appId: data.appId,
                        timestamp: data.timestamp,
                        nonceStr: data.nonceStr,
                        signature: data.signature,
                        jsApiList: [
                            'getLocation'
                        ]
                    });
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        /** 获取当前位置信息 **/
        function getLocation(address) {
            wx.getLocation({
                type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
                success: function (res) {
                    lat = parseFloat(res.latitude); // 纬度，浮点数，范围为90 ~ -90
                    long = parseFloat(res.longitude); // 经度，浮点数，范围为180 ~ -180。

                    trans(address);
                },
                error: function(error) {
                    console.log(error);

                    if (address != '') {
                        location.href = 'map.html?kw=' + address + '&lat=' + lat + '&long=' + long;
                    }
                }
            });
        }

        /** 转换为百度坐标 **/
        function trans(address) {
            var toUrl = 'http://api.map.baidu.com/geoconv/v1/?',
                path = 'coords=' + long + ',' + lat + '&from=1&to=5&ak=' + common.mapKey;

            $.ajax({
                url: toUrl + path,
                dataType: 'jsonp',
                processData: false,
                type: 'get',
                success: function(rsp) {
                    if (rsp.status == 0) {
                        long = rsp.result[0].x;
                        lat = rsp.result[0].y;

                        if (address != '') {
                            location.href = 'map.html?kw=' + address + '&lat=' + lat + '&long=' + long;
                        }
                    }
                }
            });
        }

        loadData();
        getConfig();

        var pings = env.pings;pings();
    });

    $.init();
});
