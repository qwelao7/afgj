require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#library-add', function (e, id, page) {
        var shareTypes = ['公开', '专属'],
            shareIds = [1, 2],
            status = true,
            href = window.location.href,
            lat,
            long;

        var url = common.getRequest();

        var transArr = ['library_name', 'share_type', 'library_phone', 'longitude', 'latitude'],
            chineseArr = ['书架名', '分享类型', '联系电话', '书架经度', '书架纬度'],
            skipArr = [];

        var url = common.getRequest();

        var pick = $("#picker");
        pick.val(shareTypes[0]);
        pick.next().val(shareIds[0]);

        pick.picker({
            toolbarTemplate: '<header class="bar bar-nav">\
                                <button class="button button-link pull-right close-picker">确定</button>\
                                <h1 class="title">分享类型</h1>\
                                </header>',
            cols: [
                {
                    textAlign: 'center',
                    values: shareTypes
                }
            ],
            onClose: function () {
                var val = $.trim(pick.val()),
                    index = shareTypes.indexOf(val);
                pick.next().val(shareIds[index]);
            }
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
                    wx.ready(function () {
                        getLocation();
                    })
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        /**
         * 获取地理位置
         */
        function getLocation() {
            wx.getLocation({
                type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
                success: function (res) {
                    lat = res.latitude; // 纬度，浮点数，范围为90 ~ -90
                    long = res.longitude; // 经度，浮点数，范围为180 ~ -180。

                    writePisition();
                },
                error: function (error) {
                    $.alert('很抱歉,当前无法获取您的位置信息,请重试!', '获取数据失败', function () {
                        location.reload();
                    })
                }
            });
        }

        /**
         * 写入经纬度
         */
        function writePisition() {
            $('input[name=latitude]').val(lat);
            $('input[name=longitude]').val(long);
        }

        $(document).on('click', '#submit', function () {
            if (!status) return false;
            status = false;

            var params = common.formToJson($('form').serialize()),
                key;
            params = JSON.parse(decodeURIComponent(params));

            //检验参数是否为空
            for(var i in params) {
                if (skipArr.indexOf(i) == -1) {
                    if (params[i] == '' || params[i] == undefined) {
                        key = transArr.indexOf(i);
                        $.alert('很抱歉,' + chineseArr[key] + '不能为空!', '验证失败', function() {
                            status = true;
                        });
                        return false;
                    }
                }
            }

            if (!common.check(params['library_phone'], 2)) {
                $.alert('很抱歉,请填写正确的手机号!', '验证失败', function() {
                    status = true;
                });
                return false;
            }

            common.ajax('POST', '/library/create-bookshelf', params, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('书架新增成功!', '新增成功', function() {
                        location.href = 'library-book-add.html?qr_code=' + url.qr_code;
                    })
                } else {
                    $.alert('很抱歉,新增书架失败,请重试!', '新增失败', function() {
                        status = true;
                    })
                }
            })
        });

        getConfig();

        var pings = env.pings;
        pings();
    });
    $.init();
});