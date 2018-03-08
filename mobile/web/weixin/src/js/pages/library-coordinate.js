require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#library_coor', function (e, id, page) {
        var container = $('#container'),
            tpl = $('#tpl').html(),
            library_id;

        var html = juicer(tpl, {});
        container.append(html);




        common.ajax('GET', '/library/bookshelf-list', null, true, function (rsp) {
            if (rsp.data.code == 0) {
                var data = rsp.data.info,
                    libName = [],
                    libId = [];

                data.forEach(function (item, index) {
                    libName.push(item.library_name);
                    libId.push(item.id);
                });

                pick(libName, libId);
            } else {
                alert('暂无书架数据,请稍后重试');
            }
        });


        function pick(params, arr) {
            $("#picker").picker({
                toolbarTemplate: '<header class="bar bar-nav">' +
                '<button class="button button-link pull-right close-picker font-white">确定</button>' +
                '<h1 class="title font-white">选择书架</h1>' +
                '</header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: params,
                    }
                ],
                onClose: function () {
                    var str = $('#picker').val(),
                        index = params.indexOf(str);
                    if (index != -1) {
                        library_id = arr[index];
                    }
                }
            });
        }


        /**  获取微信配置 **/

        common.ajax('POST', '/wechat/config', {href: window.location.href}, true, function (rsp) {
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
                        'checkJsApi',
                        'openLocation',
                        'getLocation'
                    ]
                });
                wx.ready(function () {

                    wx.checkJsApi({
                        jsApiList: [
                            'getLocation'
                        ],
                        success: function (res) {
                            if (res.checkResult.getLocation == false) {
                                alert('你的微信版本太低，不支持微信JS接口，请升级到最新的微信版本！');
                                return;
                            }
                        }
                    });
                    wx.getLocation({
                        success: function (res) {
                            var latitude = res.latitude; // 纬度，浮点数，范围为90 ~ -90
                            var longitude = res.longitude; // 经度，浮点数，范围为180 ~ -180。
                            $('.longitude').val(longitude);
                            $('.latitude').val(latitude);
                            console.log("latitude:"+latitude);
                            console.log("longitude:"+longitude);
                        },
                        cancel: function (res) {
                            alert('用户拒绝授权获取地理位置');
                        }
                    });

                });
            } else {
                $.alert('获取配置信息失败!');
            }
        })

        /**
         * 提交表单
         */
        $(document).on('click', '#submit', function () {
            var self = $(this);
            self.prop('disabled', true);

            var longitude = $('.longitude').val().trim();
            var latitude = $('.latitude').val().trim();


            if (!library_id || library_id == '' || library_id == undefined) {
                $.alert('请选择书架!');
                self.prop('disabled', false);
                return;
            }

            common.ajax('POST', '/library/library-coordinate', {
                'id': library_id,
                'longitude': longitude,
                'latitude': latitude
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.modal({
                        title: '温馨提示',
                        text: '书架地理位置信息更新成功',
                        buttons: [
                            {
                                text: '知道了'
                            }
                        ]
                    });
                } else {
                    $.alert('书架地理位置信息更新失败,请重试!', function () {
                        self.prop('disabled', false);
                    })
                }
            })

        })

        var pings = env.pings;pings();
    });

    $.init();
});