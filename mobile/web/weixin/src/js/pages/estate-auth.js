require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#estate-auth", function (e, id, page) {

        /** 获取url参数 **/
        var url = common.getRequest(),
            href = window.location.href;

        var img,
            status = true;

        /**  获取微信配置 **/
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
                            'checkJsApi',
                            'chooseImage',
                            'previewImage',
                            'uploadImage',
                            'downloadImage',
                        ]
                    });
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        getConfig();

        /** 点击上传图片 **/
        $(page).on('click', '#imgs', function () {
            var self = $(this);
            var template = "<div class='box' style='overflow: visible;position: relative'> " +
                "<img src='http://pub.huilaila.net/fang_85cc9bcc4b8011106e1a6255abbfb663.jpg' style='width: 7rem;height: 7rem'> " +
                "<i class='iconfont icon-cancel' style='position: absolute;left: 6.4rem;top:-.8rem;color: red;z-index: 2;;' id='delete'></i>" +
                "</div>";
            wx.chooseImage({
                count: 1, // 默认9
                sizeType: ['compressed'], // 可以指定是原图还是压缩图，默认二者都有
                sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
                success: function (res) {
                    var localId = res.localIds[0]; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
                    wx.uploadImage({
                        localId: localId, // 需要上传的图片的本地ID，由chooseImage接口获得
                        success: function (res) {
                            var serverId = res.serverId; // 返回图片的服务器端ID
                            common.ajax('GET', '/wechat/upload', {mediaId: serverId}, true, function (rsp) {
                                if (rsp.data.code == 0) {
                                    var data = rsp.data.info,
                                        template = "<div class='box' style='overflow: visible;position: relative'> " +
                                            "<img src='" + data + "' style='width: 7rem;height: 7rem'> " +
                                            "<i class='iconfont icon-cancel' style='position: absolute;left: 6.4rem;top:-.8rem;color: red;z-index: 2;' id='delete'></i>" +
                                            "</div>";
                                    self.hide();
                                    self.parent().append(template);
                                    img = data.trim().split('/').pop();
                                } else {
                                    $.alert('图片上传失败,请重试!');
                                }
                            })
                        }
                    });
                }
            })
        });

        /** 点击删除图片 **/
        $(page).on('click', '#delete', function () {
            var self = $(this);
            img = '';
            self.parent().remove();
            $('#imgs').show();
        });

        /** 提交房产认证(管理员) **/
        $(page).on('click', '#submit', function () {
            var self = $(this);

            if (!status) return;
            status = false;

            if (img != undefined && img != '') {
                common.ajax('POST', '/estate/sys-auth', {addressId: url.id, imgs: img, type: url.type}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('认证已提交,请等待...', function () {
                            status = true;
                            window.location.href = 'estate-info.html?id=' + url.id + '&fang=' + url.type;
                        });
                    } else {
                        $.alert('认证请求失败,请重试!');
                        status = true;
                    }
                })
            } else {
                $.alert('请上传认证图片!');
                status = true;
            }
        });

        var pings = env.pings;
        pings();
    });

    $.init();
});