require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#library-book-return", function (e, id, page) {
        var content = $('#content'),
            tpl = $('#tpl').html(),
            status = true;

        var url = common.getRequest(),
            href = window.location.href,
            longitude, latitude;

        common.img();

        function loadData() {
            common.ajax('GET', '/library/borrow-book-list', {library_id: url.id}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    content.append(html);

                    longitude = parseFloat(data.library.longitude);
                    latitude = parseFloat(data.library.latitude);

                } else if (rsp.data.code == 101) {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无借阅书籍!</h3>";
                    content.append(template);
                } else if (rsp.data.code == 102) {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,该书架不存在!</h3>";
                    content.append(template);
                }
            })
        }

        $(document).on('click', '.return-btn', function(e) {
            e.preventDefault();

            var self = $(this),
                parent = self.parents('.user-item');
            id = $(this).data('id');

            balanceLocation(longitude, latitude, id, parent);
        });

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

        /** 调用地址接口 **/
        function balanceLocation(longitude, latitude, id, parent) {
            wx.getLocation({
                type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
                success: function (res) {
                    var lat = parseFloat(res.latitude); // 纬度，浮点数，范围为90 ~ -90
                    var long = parseFloat(res.longitude); // 经度，浮点数，范围为180 ~ -180。

                    var distance = common.getFlatternDistance(latitude, longitude, lat, long);
                    
                    if (distance > 50) {
                        $.alert('很抱歉,您距离书架太远,无法还书', '还书失败')
                    } else {
                        status = true;
                        common.ajax('GET', '/library/return-book', {book_id: id, library_id: url.id}, true, function(rsp) {
                            if (rsp.data.code == 0) {
                                $.alert('您借阅的书籍已成功归还!', '还书成功', function() {
                                    parent.remove();
                                });
                            } else {
                                $.alert('很抱歉,还书失败,请重试!', '还书失败')
                            }
                        })
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        }


        getConfig();
        loadData();

        var pings = env.pings;pings();

        var result = common.getFlatternDistance(118.841935, 32.122319, 118.796877, 32.060255);
        console.dir(result);
    });

    $.init();
});
