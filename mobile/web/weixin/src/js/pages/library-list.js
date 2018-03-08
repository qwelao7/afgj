require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#library-list", function (e, id, page) {
        var url = common.getRequest();

        var content = $('#content'),
            tpl = $('#tpl').html();

        var href = window.location.href,
            lat,
            long;

        common.img();

        var distance = function(data) {
            var re = /^(?:0\.\d+|[01](?:\.0)?)$/;
            data = (re.test(data)) ? parseFloat(data*1000).toFixed(0) + '米' : parseFloat(data).toFixed(1) + '公里';
            return data;
        };
        juicer.register('distance', distance);

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
                    wx.ready(function() {
                        getLocation();
                    })
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        /**
         * 调用地址接口
         */
        function getLocation() {
            wx.getLocation({
                type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
                success: function (res) {
                    lat = res.latitude; // 纬度，浮点数，范围为90 ~ -90
                    long = res.longitude; // 经度，浮点数，范围为180 ~ -180。

                    loadData();
                },
                error: function(error) {
                    $.alert('很抱歉,当前无法获取您的位置信息,请重试!', '获取数据失败', function() {
                        location.reload();
                    })
                }
            });
        }

        /**
         * 加载数据
         */
        function loadData() {
            common.ajax('GET', '/library/bookshelf-list', {
                'lat': lat,
                'long': long
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data,
                        html = juicer(tpl, data);

                    content.append(html);

                    $('.library-list').each(function(index, item) {
                        var that = $(item),
                            key = that.index();

                        if (that.find('.library-lock').length > 0) {
                            that.find('.library-lock')[0].style.backgroundImage= 'url(' + common.QiniuDamain + data.info[key].thumbnail + ')';
                        }
                    });
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无书架信息!</h3>";
                    content.append(template);
                }
            })
        }

        /**
         * 跳转书架书本列表
         */
        $(document).on('click','.library-list', function () {
            var self = $(this),
                id = self.data('id'),
                open = self.data('open'),
                name = self.data('name'),
                type = self.data('type');

            if (type == 2 && !open) {
                $.modal({
                    text: '此书架为<span>'+ name +'</span>业主专享，使用前请先认证<span>' + name + '</span>房产，谢谢！',
                    buttons: [
                        {
                            text: '取消'
                        },
                        {
                            text: '马上认证',
                            onClick: function() {
                              window.location.href = 'estate-manage.html';
                            }
                        }
                    ]
                })
            } else {
                window.location.href = 'library-book-list.html?id=' + id + '&type=0';
            }
        });

        /** back **/
        $(document).on('click', '#back', function() {
            window.location.href = common.ectouchPic;
        });

        /** 从书架搜索 **/
        $(document).on('focus', 'input[name=search]', function() {
            location.href = 'library-book-search.html';
        });

        getConfig();

        var pings = env.pings;pings();
    });

    $.init();
});
